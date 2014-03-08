/* Test that printf works
 * You'd be amazed how many systems calls are used in modern
 * libcs just to print text to stdout. */

#include <stdio.h>

int main(void) {
	printf("Hello, world\n");
	return 0;
}
