// Test that privileged operations in C++ constructors
// aren't allowed

#include <iostream>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>

class Test {
public:
	Test() {
		open("t/test11.cpp", O_RDONLY);
	}
};

namespace {
	Test s_instance;
}

int main(void) {
	// main should not be reached
	std::cout << "If you can see this, you lose" << std::endl;
	return 0;
}
