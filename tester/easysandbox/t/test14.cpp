// Test that allocation with new and new[] operators
// in C++ works

#include <iostream>
#include <cstdlib>

#define N 1000

int main(void) {
	int **ptrs;
	int i, sum;

	srand(123);

	// This code is just a fancy way of computing the sum of
	// the integers 1..N
	ptrs = new int*[N];
	for (i = 0; i < N; i++) {
		ptrs[i] = new int;
		*(ptrs[i]) = (i+1);
	}
	for (sum = 0, i = 0; i < N; i++) {
		sum += *(ptrs[i]);
	}

	// As a stress test for the malloc implementation,
	// shuffle the pointers in the array 
	for (i = N-1; i >= 1; i--) {
		int j = rand() % (i+1);
		int *tmp = ptrs[i];
		ptrs[i] = ptrs[j];
		ptrs[j] = tmp;
	}

	// Free all of the memory
	for (i = 0; i < N; i++) {
		delete ptrs[i];
	}
	delete[] ptrs;

	std::cout << sum << std::endl;

	return 0;
}
