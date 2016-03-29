#!/bin/bash
t="sample_template.cpp"
f="sample_code.cpp"
banned=`sed -n -e '/###Begin banned keyword/,/###End banned keyword/p' $t | sed -e '1d' -e '$d'`
code=`sed -e '1,/###End banned keyword/d' $t`
#echo "$banned"
#echo "$code"

while read -r line
do
	#echo grep -q "$line" $f 
	if grep -q "$line" $f ;then
		echo "$line is banned"
		exit
	fi
done <<< "$banned"