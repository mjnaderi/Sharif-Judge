// Test that C++ constructor functions work

#include <iostream>

class Test {
public:
	Test() { std::cout << "Hello from the constructor!" << std::endl; }
};

namespace {
	Test s_instance;
}

int main() {
	// Do nothing
	return 0;
}
