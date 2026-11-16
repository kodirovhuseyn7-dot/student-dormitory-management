
#include <iostream>
using namespace std;

int main() {
    int number = 25;

    int* ptr = &number;

    cout << "Value: " << number << endl;
    cout << "Address: " << ptr << endl;
    cout << "Value from memory: " << *ptr << endl;

    *ptr = 50;

    cout << "New value: " << number << endl;

    return 0;
}
