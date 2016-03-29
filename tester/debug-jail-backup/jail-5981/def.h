/*

@file defcpp.h
There should be a newline at end of this file.
Put the message displayed to user after // in each line
Please note:
Lots of "define"s that work in C, don't work correctly in C++.

e.g. If you want to disallow goto, add this line:
#define goto errorNo4    //Goto is not allowd

*/

#define fork errorNo1       //Fork is not allowed
#define clone errorNo2      //Clone is not allowed
#define sleep errorNo3      //Sleep is not allowed
