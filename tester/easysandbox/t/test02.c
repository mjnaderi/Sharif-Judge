/* Print some stuff to stdout using write: should work. */

#include <unistd.h>

int main(void) {
	write(1, "Hello, world\n", 13);
	return 0;
}
