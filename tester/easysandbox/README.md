# EasySandbox

An easy way to sandbox untrusted C and C++ programs on Linux.
Allows them only to allocate memory (up to a fixed amount),
read and write via stdin, stdout, and stderr,
and exit.  Sandboxing is done using
[SECCOMP](http://lwn.net/Articles/332974/).

The intended use is being able to safely execute student
code submissions for the [CloudCoder](http://cloudcoder.org)
programming exercise system, although it could be useful in
other contexts.

You can run

```bash
make runtests
```

to run the test programs.  If you see "All tests passed!", then
EasySandbox is working on your system.

EasySandbox is distributed under the [MIT license](http://opensource.org/licenses/MIT).

If you have questions about EasySandbox, [send me an email](mailto:david.hovemeyer@gmail.com).
If you have improvements that you would like to share, send me a pull request on
GitHub.

# Using EasySandbox

Run the `make` command to build the EasySandbox shared library.

Run the program you want to sandbox, using the **LD_PRELOAD** environment
variable to load the EasySandbox shared library before the untrusted executable
is executed:

```bash
LD_PRELOAD=/path/to/EasySandbox.so ./untrustedExe
```

EasySandbox defines its own implementation of `malloc` and `free`, to ensure
that the program will not need to call `sbrk` or `mmap` to allocate memory
while in SECCOMP mode.  The heap is a fixed size, and cannot grow while the
program is running.  You can control the size of the heap by setting
the **EASYSANDBOX_HEAPSIZE** environment variable to the size of the heap
in bytes.  The default heap size is 8MB.

**Note**: EasySandbox uses [__libc_start_main](http://refspecs.linuxbase.org/LSB_3.1.1/LSB-Core-generic/LSB-Core-generic/baselib---libc-start-main-.html)
to hook into the startup process.  If the untrusted executable defines its own entry
point (rather than the normal Linux/glibc one), it could execute untrusted code.
In my intended application (compiling and executing student code
submissions), I control the compilation process, 
and I _believe_ that as long as gcc/g++ is invoked without the `-nostdlib` option,
any attempt by the untusted code to define an entry point (`_start` function)
will result in a linker error,
because the name `_start` will conflict with the real `_start` function defined in
`crt1.o`.

# Limitations

When you execute a program using EasySandbox, it will print the message

```text
<<entering SECCOMP mode>>
```

followed by a newline character
to both stdout and stderr.  The reason is that the first call to print
to an output stream causes glibc to invoke `fstat`, which is not permitted
when in SECCOMP mode.  So, the EasySandbox shared library must print some output
to stdout and stderr before entering SECCOMP mode in order for these streams
to be usable.  It is fairly easy to filter out
this output as a post-processing step.

Similarly, reading from stdin also triggers a call to `fstat`.
The EasySandbox shared library works around this by putting the stdin
file descriptor into nonblocking mode, attempting to read a single
character using the `fgetc` function, and then using `ungetc` function
to put the character back if one was read.  This should not cause any
problems for programs that use C library functions to read from stdin,
but programs that use the `read` system call to read from the stdin
file descriptor may not be able to read the first byte of input.

The EasySandbox shared library implements its own `exit` function,
because glibc's invokes the `exit_group` system call, which is not allowed
by SECCOMP.  The behavior of this custom exit function attempts to
emulate glibc's: it runs atexit functions, which includes destructors for
static C++ objects.

EasySandbox is not intended to be used for multithreaded programs.
SECCOMP will surely kill any process that attempts to create an additional
thread, since creating a thread would require an invocation of the `clone`
system call, which isn't allowed by SECCOMP.

EasySandbox is designed to work with glibc, and may or may not
work with other libc variants.  It is entirely possible that future changes
to glibc could break EasySandbox.
