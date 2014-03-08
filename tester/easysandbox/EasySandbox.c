/*
 * EasySandbox: an extremely simple sandbox for untrusted C/C++ programs
 * Copyright (c) 2012,2013 David Hovemeyer <david.hovemeyer@gmail.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/*
 * Resources:
 *
 * - http://justanothergeek.chdir.org//2010/03/seccomp-as-sandboxing-solution/
 *
 *   This is where the idea (and code) to use __libc_start_main as a hook
 *   into the startup process came from.  However, my implementation is
 *   slightly different, in that I enable SECCOMP before any of the
 *   constructor functions run. (Without this modification, constructor
 *   functions would run with full privileges.)
 *
 * - http://www.win.tue.nl/~aeb/linux/lk/lk-14.html
 *
 *   Very practical advice on using SECCOMP.
 */

#include <unistd.h>
#include <fcntl.h>
#include <dlfcn.h>
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <errno.h>
#include <sys/prctl.h>
#include <sys/syscall.h>
#include <sys/mman.h>

/* Default heap size is 8MB */
#define DEFAULT_HEAP_SIZE 8388608

#define DLOPEN_FAILED  120
#define SECCOMP_FAILED 121
#define EXIT_FAILED    122  /* should not happen */
#define MMAP_FAILED    123

/* We implement our own atexit and __cxa_atexit. */
struct CxaAtexitHandler {
	union {
		void (*atexit_fn)(void);
		void (*cxa_atexit_fn)(void *);
	} f;
	void *arg;
	int type; /* 0 for atexit function, 1 for __cxa_atexit function */
};

/* Table of atexit handlers. */
#define MAX_ATEXIT_HANDLERS 1024
static struct CxaAtexitHandler s_atexit_handlers[MAX_ATEXIT_HANDLERS];
static int s_atexit_handler_count;

/* Saved pointers to the real init, main, destructor, and runtime loader destructor functions. */
static void (*real_init)(void);
static int (*real_main)(int, char **, char **);
static void (*real_fini)(void);
static void (*real_rtld_fini)(void);

/* Prototypes for our idempotent wrapper destructor and runtime loader destructor functions */
static void wrapper_fini(void);
static void wrapper_rtld_fini(void);

/* Keep track of whether destructor functions have been run. */
static int s_ran_fini;
static int s_ran_rtld_fini;

/*
 * Preallocated region of memory with which to
 * implement a custom sbrk() routine.  This is used by
 * the memory allocator in malloc.c to implement
 * malloc/free and friends.  This approach allows us
 * to support malloc/free without any system calls.
 */
static char *s_heap;
static size_t s_heapsize;
static char *s_brk;

/*
 * Custom implementation of sbrk() that allocates from a fixed-size
 * array of bytes.  This avoids the need for malloc/free and
 * friends to make any system calls.
 */
void *sbrk(intptr_t incr)
{
	intptr_t used, remaining;
	void *newbrk;

	if (s_brk == 0) {
		s_brk = s_heap;
	}

	used = s_brk - s_heap;
	remaining = s_heapsize - used;
	
	if (remaining < incr) {
		errno = ENOMEM;
		return (void*) -1;
	}
	newbrk = s_brk;
	s_brk += incr;
	return newbrk;
}

/*
 * Re-implementation of exit.
 * Flushes stdout and stderr, and exits using the exit system
 * call.  glibc's exit function is unusable in SECCOMP mode because
 * it invokes the exit_group system call.
 */
void exit(int exit_code)
{
	/* Invoke atexit handlers in reverse order. */
	while (s_atexit_handler_count > 0) {
		struct CxaAtexitHandler *handler;
		s_atexit_handler_count--;
		handler = &s_atexit_handlers[s_atexit_handler_count];
		switch (handler->type) {
		case 0:
			handler->f.atexit_fn();
			break;
		case 1:
			handler->f.cxa_atexit_fn(handler->arg);
			break;
		}
	}

	/* This is probably a good time to call destructor functions */
	wrapper_fini();
	wrapper_rtld_fini();

	/* Flush output streams */
	fflush(stdout);
	fflush(stderr);

	/* The loop is because gcc doesn't know that syscall doesn't return
	 * in this particular case */
	while (1) {
		syscall(SYS_exit, exit_code);
	}
}

#define IMPL_ATEXIT(func_,field_,arg_,type_) \
	struct CxaAtexitHandler *handler; \
	if (s_atexit_handler_count >= MAX_ATEXIT_HANDLERS) { \
		return -1; \
	} \
	handler = &s_atexit_handlers[s_atexit_handler_count]; \
	handler->f.field_ = func_; \
	handler->arg = arg_; \
	handler->type = type_; \
	s_atexit_handler_count++; \
	return 0

/*
 * Custom implementation of __cxa_atexit.
 * Note that the dso_handle is ignored, and we don't
 * attempt to hook into dynamic unloading.
 */
int __cxa_atexit(void (*func)(void *), void *arg, void *dso_handle)
{
	IMPL_ATEXIT(func, cxa_atexit_fn, arg, 1);
}

/*
 * Custom implementation of atexit.
 */
int atexit(void (*func)(void))
{
	IMPL_ATEXIT(func, atexit_fn, 0, 0);
}

static void wrapper_init(void)
{
	int stdin_flags;
	int c;

	/* The first call to print to a stream will cause glibc to
	 * invoke the fstat system call, which will cause SECCOMP
	 * to kill the process. There does not seem to be any way
	 * of working around this problem except to print some output
	 * on the stdout and strerr streams before entering SECCOMP mode.
	 * Unfortunately, a printf call that generates no output doesn't
	 * work, so some extraneous output seems unavoidable. Fortunately,
	 * this is easy to filter out as a post-processing step. */
	fprintf(stdout, "<<entering SECCOMP mode>>\n");
	fflush(stdout);
	fprintf(stderr, "<<entering SECCOMP mode>>\n");
	fflush(stderr);

	/* The first call to read from stdin will also result in a
	 * call to fstat.  Work around this by setting the stdin
	 * file descriptor to nonblocking, then reading a single character
	 * from stdin. */
	stdin_flags = fcntl(0, F_GETFL, 0);
	fcntl(0, F_SETFL, stdin_flags | O_NONBLOCK); /* make stdin nonblocking */
	c = fgetc(stdin);
	if (c != EOF) {
		/* We read a character, so put it back */
		ungetc(c, stdin);
	}
	fcntl(0, F_SETFL, stdin_flags); /* restore original stdin flags */

#if 1
	/* Enter SECCOMP mode */
	if (prctl(PR_SET_SECCOMP, 1, 0, 0) == -1) {
		_exit(SECCOMP_FAILED);
	}
#endif

	/* Call the real init function */
	real_init();
}

static int wrapper_main(int argc, char **argv, char **envp)
{
	/* Call the real main function.
	 * Note that we call our reimplementation of the exit function,
	 * because returning would cause glibc to invoke the exit_group
	 * system call, which is not allowed in SECCOMP mode. */
	int n;
	n = real_main(argc, argv, envp);
	exit(n);
	return EXIT_FAILED;
}

static void wrapper_fini(void)
{
	if (!s_ran_fini) {
		/*printf("Running destructors...\n");*/
		fflush(stdout);
		s_ran_fini = 1;
		real_fini();
	}
}

static void wrapper_rtld_fini(void)
{
	if (!s_ran_rtld_fini) {
		/*printf("Running runtime loader destructors...\n");*/
		fflush(stdout);
		s_ran_rtld_fini = 1;
		real_rtld_fini();
	}
}

int __libc_start_main(
	int (*main)(int, char **, char **),
	int argc,
	char ** ubp_av,
	void (*init)(void),
	void (*fini)(void),
	void (*rtld_fini)(void),
	void (* stack_end))
{
	void *libc_handle;
	const char *heapenv;

	int (*real_libc_start_main)(
		int (*main) (int, char **, char **),
		int argc,
		char ** ubp_av,
		void (*init)(void),
		void (*fini)(void),
		void (*rtld_fini)(void),
		void (* stack_end));

	/* Save pointers to the real init, main, destructor, and runtime loader destructor functions */
	real_init = init;
	real_main = main;
	real_fini = fini;
	real_rtld_fini = rtld_fini;

	/* Use mmap to allocate a region of memory to serve as the heap.
	 * This must be done early since dlopen/dlsym will call malloc. */
	heapenv = getenv("EASYSANDBOX_HEAPSIZE");
	s_heapsize = (size_t) ((heapenv != 0) ? atol(heapenv) : DEFAULT_HEAP_SIZE);
	s_heap = mmap(0, s_heapsize, PROT_READ|PROT_WRITE, MAP_PRIVATE|MAP_ANONYMOUS, -1, 0);
	if (s_heap == MAP_FAILED) {
		_exit(MMAP_FAILED);
	}

	/* explicitly open the glibc shared library */
	libc_handle = dlopen("libc.so.6", RTLD_LOCAL | RTLD_LAZY);
	if (libc_handle == 0) {
		_exit(DLOPEN_FAILED);
	}

	/* get a pointer to the real __libc_start_main function */
	*(void **) (&real_libc_start_main) = dlsym(libc_handle, "__libc_start_main");

	/* Delegate to the real __libc_start_main, but provide our
	 * wrapper init, main, destructor, and runtime loader destructor functions */
	return real_libc_start_main(wrapper_main, argc, ubp_av,
		wrapper_init, wrapper_fini, wrapper_rtld_fini, stack_end);
}
