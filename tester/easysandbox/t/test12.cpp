// Test that C++ destructors work

#include <iostream>

class Test {
public:
	Test() { }
	~Test() {
		std::cout << "Hello from the destructor!" << std::endl;
	}
};

namespace {
	Test s_instance;
}

int main() {
	std::cout << "Here we are in main()" << std::endl;
	return 0;
}
