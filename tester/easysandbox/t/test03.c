/* Try an illegal system call: process should be killed */

#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

int main(void) {
	open("t/test03.c", O_RDONLY); /* should not be permitted */
	printf("Uh-oh: we should not have been able to open a file\n");
	return 0;
}
