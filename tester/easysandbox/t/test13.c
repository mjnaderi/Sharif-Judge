/* Test that gcc destructor functions work */

#include <stdio.h>

__attribute__((destructor))
void dtor(void)
{
	printf("Hello from the destructor!\n");
}

int main(void) {
	/* Do nothing */
	return 0;
}
