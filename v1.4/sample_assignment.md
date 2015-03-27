# Sample Assignment

Here is a sample assignment for testing Sharif Judge. Add this assignment by clicking on `Add` in `Assignments` page.

## Problems

This assignment has three problems:

### Problem 1 (Sum):
  
Read integer n, and then read n integers. Print sum of these n numbers.

| Sample Input      | Sample Output |
| ----------------- | ------------- |
| 5<br/>54 78 0 4 9  | 145           |

### Problem 2 (Max):

Read integer n, and then read n integers. Print sum of the two largest numbers between these n numbers.

| Sample Input                       | Sample Output |
| ---------------------------------- | ------------- |
| 7<br/>162 173 159 164 181 158 175   | 356           |

### Problem 3 (Upload!):

Upload a `c` or `zip` file! (This problem is "Upload Only", and will not be judged)

## Tests

Tests follow [this structure](tests_structure.md).

You can find the zip file of the tests in Assignments folder.

The tree of this file is:

    .
    ├── p1
    │   ├── in
    │   │   ├── input1.txt
    │   │   ├── input2.txt
    │   │   ├── input3.txt
    │   │   ├── input4.txt
    │   │   ├── input5.txt
    │   │   ├── input6.txt
    │   │   ├── input7.txt
    │   │   ├── input8.txt
    │   │   ├── input9.txt
    │   │   └── input10.txt
    │   ├── out
    │   │   └── output1.txt
    │   ├── tester.cpp
    │   └── desc.md
    ├── p2
    │   ├── in
    │   │   ├── input1.txt
    │   │   ├── input2.txt
    │   │   ├── input3.txt
    │   │   ├── input4.txt
    │   │   ├── input5.txt
    │   │   ├── input6.txt
    │   │   ├── input7.txt
    │   │   ├── input8.txt
    │   │   ├── input9.txt
    │   │   └── input10.txt
    │   ├── out
    │   │   ├── output1.txt
    │   │   ├── output2.txt
    │   │   ├── output3.txt
    │   │   ├── output4.txt
    │   │   ├── output5.txt
    │   │   ├── output6.txt
    │   │   ├── output7.txt
    │   │   ├── output8.txt
    │   │   ├── output9.txt
    │   │   └── output10.txt
    │   ├── desc.md
    │   └── Problem2.pdf
    ├── p3
    │   └── desc.md
    └── SampleAssignment.pdf


Problem 1 uses "Tester" method for checking output. So it has a file `tester.cpp` (the Tester Script)

Problem 2 uses "Output Comparison" method for checking output. So it has two folders `in` and `out` containing test cases.

Problem 3 is an "Upload-Only" problem.

## Sample Solutions

### Sample solutions for problem 1:

#### C

```c
#include<stdio.h>
int main(){
	int n;
	scanf("%d",&n);
	int i;
	int sum =0 ;
	int k;
	for(i=0 ; i<n ; i++){
		scanf("%d",&k);
		sum+=k;
	}
	printf("%d\n",sum);
	return 0;
}
```

#### C++

```cpp
#include <iostream>
using namespace std;
int main(){
	int n, sum=0;
	cin >> n;
	for (int i=0 ; i<n ; i++){
		int a;
		cin >> a;
		sum += a;
	}
	cout << sum << endl;
	return 0;
}
```

#### Java

```java
import java.util.Scanner;
class sum
{
	public static void main(String[] args)
	{ 
		Scanner sc = new Scanner(System.in);
		int n = sc.nextInt();
		int sum =0;
		for (int i=0 ; i<n ; i++)
		{
			int a = sc.nextInt();
			sum += a;
		}
		System.out.println(sum); 
	}
}
```

### Sample Solution for problem 2:

#### C

```c
#include<stdio.h>
int main(){
	int n , m1=0, m2=0;
	scanf("%d",&n);
	for(;n--;){
		int k;
		scanf("%d",&k);
		if(k>=m1){
			m2=m1;
			m1=k;
		}
		else if(k>m2)
			m2=k;
	}
	printf("%d",m1+m2);
	return 0;
}
```

#### C++

```cpp
#include<iostream>
using namespace std;
int main(){
	int n , m1=0, m2=0;
	cin >> n;
	for(;n--;){
		int k;
		cin >> k;
		if(k>=m1){
			m2=m1;
			m1=k;
		}
		else if(k>m2)
			m2=k;
	}
	cout << (m1+m2) << endl ;
	return 0;
}
```
