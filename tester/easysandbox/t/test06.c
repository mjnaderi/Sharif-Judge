/* Make sure that a constructor function can not
 * execute any privileged operations. */

#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

__attribute((constructor))
void myinit(void)
{
	open("t/test06.c", O_RDONLY); /* should not be permitted */
	printf("If you see this, you lose!\n");
}

int main(void)
{
	/* Just return without producing any output */
	return 0;
}
