/* Test reading stdin using scanf */

#include <stdio.h>

int main(void) {
	int a, b;
	scanf("%i %i", &a, &b);
	printf("%i\n", a+b);
	return 0;
}
