# Shield

Shield is an extremely simple mechanism to forbid running of potentially harmful codes.

Shield is not a sandboxing solution. Shield provides only a partial protection against trivial attacks. Real protection against untrusted code comes only by enabling [Sandbox](sandboxing.md).

## Shield for C/C++

By enabling Shield for C/C++, Sharif Judge just adds some `#define`s at the beginning of submitted C/C++ code before running.

For example we can forbid using `goto` by adding this line at the beginning of submitted code:

```c
#define goto YouCannotUseGoto
```

With this line at the beginning of files, all submitted codes which use `goto` will get a compilation error.

If you enable Shield, any code that contains `#undef` will get a compilation error.

### Enabling Shield for C or C++

You can enable or disable Shield in `Settings` page.

### Adding Rules for C/C++

List of `#define` rules is located in files `tester/shield/defc.h` (for C) and `tester/shield/defcpp.h` (for C++). You can add new `#define` rules in these files. The contents of these files is editable in `Settings` page.

The syntax used in these files is like this:

```c
/*

@file defc.h
There should be a newline at end of this file.
Put the message displayed to user after // in each line

e.g. If you want to disallow goto, add this line:
#define goto errorNo13    //Goto is not allowd

*/

#define system errorNo1      //"system" is not allowed
#define freopen errorNo2     //File operation is not allowed
#define fopen errorNo3       //File operation is not allowed
#define fprintf errorNo4     //File operation is not allowed
#define fscanf errorNo5      //File operation is not allowed
#define feof errorNo6        //File operation is not allowed
#define fclose errorNo7      //File operation is not allowed
#define ifstream errorNo8    //File operation is not allowed
#define ofstream errorNo9    //File operation is not allowed
#define fork errorNo10       //Fork is not allowed
#define clone errorNo11      //Clone is not allowed
#define sleep errorNo12      //Sleep is not allowed
```

There should be a newline at the end of files `defc.h` and `defcpp.h`.

Note that lots of these rules are not usable in g++. For example we cannot use `#define fopen errorNo3` for C++. Because it results in compile error.

## Shield for Python

By enabling Shield for Python, Sharif Judge just adds some code at the beginning of submitted Python code before running to prevent using dangerous functions. These codes are located in files `tester/shield/shield_py2.py` and `tester/shield/shield_py3.py`.

You can enable/disable Shield for Python in Settings.

There are ways to escape from Shield in python and use blacklisted functions!

```python
# @file shield_py3.py

import sys
sys.modules['os']=None

BLACKLIST = [
  #'__import__', # deny importing modules
  'eval', # eval is evil
  'open',
  'file',
  'exec',
  'execfile',
  'compile',
  'reload',
  #'input'
  ]
for func in BLACKLIST:
  if func in __builtins__.__dict__:
    del __builtins__.__dict__[func]
```
