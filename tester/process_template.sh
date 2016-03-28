#!/bin/bash
f="sample_template.cpp"
banned=`sed -n -e '/###Begin banned keyword/,/###End banned keyword/p' $f | sed -e '1d' -e '$d'`
sed -e '1,/###End banned keyword/d' $f