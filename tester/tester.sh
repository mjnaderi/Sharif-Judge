#!/bin/bash

#    In the name of ALLAH
#    Sharif Judge
#    Copyright (C) 2013  Mohammad Javad Naderi <mjnaderi@gmail.com>
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.


##################### Example Usage #####################
# tester.sh /home/mohammad/judge/homeworks/hw6/p1 mjn problem problem c 1 1 50000 1000000 diff -bB 1 1 1 0 1
# In this example judge assumes that the file is located at:
# /home/mohammad/judge/homeworks/hw6/p1/mjn/problem.c
# And test cases are located at:
# /home/mohammad/judge/homeworks/hw6/p1/in/  {input1.txt, input2.txt, ...}
# /home/mohammad/judge/homeworks/hw6/p1/out/ {output1.txt, output2.txt, ...}


####################### Output #######################
# Output is just one line. One of these:
#   a number (score form 10000)
#   Compilation Error
#   Syntax Error
#   Invalid Tester Code
#   File Format Not Supported
#   Judge Error

# Get Current Time (in milliseconds)
START=$(($(date +%s%N)/1000000));

################### Getting Arguments ###################
# problem directory
PROBLEMPATH=${1}
# username
UN=${2}
# main file name (used only for java)
MAINFILENAME=${3}
# file name without extension
FILENAME=${4}
# file extension
EXT=${5}
# time limit in seconds
TIMELIMIT=${6}
# integer time limit in seconds (should be an integer greater than TIMELIMIT)
TIMELIMITINT=${7}
# memory limit in kB
MEMLIMIT=${8}
# output size limit in Bytes
OUTLIMIT=${9}
# diff tool (default: diff)
DIFFTOOL=${10}
# diff options (default: -bB)
DIFFOPTION=${11}
# enable/disable judge log
if [ ${12} = "1" ]; then
	LOG_ON=true
else
	LOG_ON=false
fi
# enable/disable easysandbox
if [ ${13} = "1" ]; then
	SANDBOX_ON=true
else
	SANDBOX_ON=false
fi
# enable/disable C/C++ shield
if [ ${14} = "1" ]; then
	C_SHIELD_ON=true
else
	C_SHIELD_ON=false
fi
# enable/disable Python shield
if [ ${15} = "1" ]; then
	PY_SHIELD_ON=true
else
	PY_SHIELD_ON=false
fi
# enable/disable java security manager
if [ ${16} = "1" ]; then
	JAVA_POLICY="-Djava.security.manager -Djava.security.policy=java.policy"
else
	JAVA_POLICY=""
fi

# DIFFOPTION can also be "ignore" or "exact".
# ignore: In this case, before diff command, all newlines and whitespaces will be removed from both files
# identical: diff will compare files without ignoring anything. files must be identical to be accepted
DIFFARGUMENT=""
if [[ "$DIFFOPTION" != "identical" && "$DIFFOPTION" != "ignore" ]]; then
	DIFFARGUMENT=$DIFFOPTION
fi


LOG="$PROBLEMPATH/$UN/log"; echo "" >>$LOG
function shj_log {
	if $LOG_ON; then
		echo -e "$@" >>$LOG 
	fi
}


function shj_finish {
	# Get Current Time (in milliseconds)
	END=$(($(date +%s%N)/1000000));
	shj_log "\nTotal Execution Time: $((END-START)) ms"
	echo $@
	exit 0
}





shj_log "Starting tester..."

#################### Initialization #####################
# detecting existence of perl
PERL_EXISTS=true
hash perl 2>/dev/null || PERL_EXISTS=false

if ! $PERL_EXISTS; then
	shj_log "Warning: perl not found. We continue without perl..."
fi

TST="$(ls $PROBLEMPATH/in | wc -l)"  # Number of Test Cases

JAIL=jail-$RANDOM
if ! mkdir $JAIL; then
	shj_log "Error: Folder 'tester' is not writable! Exiting..."
	shj_finish "Judge Error"
fi
cd $JAIL
cp ../timeout ./timeout
chmod +x timeout

cp ../runcode.sh ./runcode.sh
chmod +x runcode.sh

shj_log "$(date)"
shj_log "Language: $EXT"
shj_log "Time Limit: $TIMELIMIT s"
shj_log "Memory Limit: $MEMLIMIT kB"
shj_log "Output size limit: $OUTLIMIT bytes"
if [[ $EXT = "c" || $EXT = "cpp" ]]; then
	shj_log "EasySandbox: $SANDBOX_ON"
	shj_log "C/C++ Shield: $C_SHIELD_ON"
elif [[ $EXT = "py2" || $EXT = "py3" ]]; then
	shj_log "Python Shield: $PY_SHIELD_ON"
elif [[ $EXT = "java" ]]; then
	shj_log "JAVA_POLICY: \"$JAVA_POLICY\""
fi



COMPILE_BEGIN_TIME=$(($(date +%s%N)/1000000));


########################################################################################################
############################################ COMPILING JAVA ############################################
########################################################################################################
if [ "$EXT" = "java" ]; then
	cp ../java.policy java.policy
	cp $PROBLEMPATH/$UN/$FILENAME.java $MAINFILENAME.java
	shj_log "Compiling as Java"
	javac $MAINFILENAME.java >/dev/null 2>cerr
	EXITCODE=$?
	COMPILE_END_TIME=$(($(date +%s%N)/1000000));
	shj_log "Compiled. Exit Code=$EXITCODE  Execution Time: $((COMPILE_END_TIME-COMPILE_BEGIN_TIME)) ms"
	if [ $EXITCODE -ne 0 ]; then
		shj_log "Compile Error"
		shj_log "$(cat cerr|head -10)"
		echo '<span class="shj_b">Compile Error</span>' >$PROBLEMPATH/$UN/result.html
		echo '<span class="shj_r">' >> $PROBLEMPATH/$UN/result.html
		#filepath="$(echo "${JAIL}/${FILENAME}.${EXT}" | sed 's/\//\\\//g')" #replacing / with \/
		(cat cerr | head -10 | sed 's/&/\&amp;/g' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g' | sed 's/"/\&quot;/g') >> $PROBLEMPATH/$UN/result.html
		#(cat $JAIL/cerr) >> $PROBLEMPATH/$UN/result.html
		echo "</span>" >> $PROBLEMPATH/$UN/result.html
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "Compilation Error"
	fi
fi





########################################################################################################
########################################## COMPILING PYTHON 2 ##########################################
########################################################################################################
if [ "$EXT" = "py2" ]; then
	cp $PROBLEMPATH/$UN/$FILENAME.py $FILENAME.py
	shj_log "Checking Python Syntax"
	python -O -m py_compile $FILENAME.py >/dev/null 2>cerr
	EXITCODE=$?
	COMPILE_END_TIME=$(($(date +%s%N)/1000000));
	shj_log "Syntax checked. Exit Code=$EXITCODE  Execution Time: $((COMPILE_END_TIME-COMPILE_BEGIN_TIME)) ms"
	if [ $EXITCODE -ne 0 ]; then
		shj_log "Syntax Error"
		shj_log "$(cat cerr | head -10)"
		echo '<span class="shj_b">Syntax Error</span>' >$PROBLEMPATH/$UN/result.html
		echo '<span class="shj_r">' >> $PROBLEMPATH/$UN/result.html
		(cat cerr | head -10 | sed 's/&/\&amp;/g' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g' | sed 's/"/\&quot;/g') >> $PROBLEMPATH/$UN/result.html
		echo "</span>" >> $PROBLEMPATH/$UN/result.html
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "Syntax Error"
	fi
	if $PY_SHIELD_ON; then
		shj_log "Enabling Shield For Python 2"
		# adding shield to beginning of code:
		cat ../shield/shield_py2.py | cat - $FILENAME.py > thetemp && mv thetemp $FILENAME.py
	fi
fi





########################################################################################################
########################################## COMPILING PYTHON 3 ##########################################
########################################################################################################
if [ "$EXT" = "py3" ]; then
	cp $PROBLEMPATH/$UN/$FILENAME.py $FILENAME.py
	shj_log "Checking Python Syntax"
	python3 -O -m py_compile $FILENAME.py >/dev/null 2>cerr
	EXITCODE=$?
	COMPILE_END_TIME=$(($(date +%s%N)/1000000));
	shj_log "Syntax checked. Exit Code=$EXITCODE  Execution Time: $((COMPILE_END_TIME-COMPILE_BEGIN_TIME)) ms"
	if [ $EXITCODE -ne 0 ]; then
		shj_log "Syntax Error"
		shj_log "$(cat cerr | head -10)"
		echo '<span class="shj_b">Syntax Error</span>' >$PROBLEMPATH/$UN/result.html
		echo '<span class="shj_r">' >> $PROBLEMPATH/$UN/result.html
		(cat cerr | head -10 | sed 's/&/\&amp;/g' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g' | sed 's/"/\&quot;/g') >> $PROBLEMPATH/$UN/result.html
		echo "</span>" >> $PROBLEMPATH/$UN/result.html
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "Syntax Error"
	fi
	if $PY_SHIELD_ON; then
		shj_log "Enabling Shield For Python 3"
		# adding shield to beginning of code:
		cat ../shield/shield_py3.py | cat - $FILENAME.py > thetemp && mv thetemp $FILENAME.py
	fi
fi





########################################################################################################
############################################ COMPILING C/C++ ###########################################
########################################################################################################

# Compile options for C/C++
OPTIONS="-fno-asm -Dasm=error -lm -O2"

# Warning Options for C/C++
# -w: Inhibit all warning messages
# -Werror: Make all warnings into errors
# -Wall ...
# Read more: http://gcc.gnu.org/onlinedocs/gcc/Warning-Options.html
WARNING_OPTION="-w"


if [ "$EXT" = "c" ] || [ "$EXT" = "cpp" ]; then
	COMPILER="gcc"
	if [ "$EXT" = "cpp" ]; then
		COMPILER="g++"
	fi
	EXEFILE="s_$(echo $FILENAME | sed 's/[^a-zA-Z0-9]//g')" # Name of executable file
	cp $PROBLEMPATH/$UN/$FILENAME.$EXT code.c
	shj_log "Compiling as $EXT"
	if $SANDBOX_ON; then
		shj_log "Enabling EasySandbox"
		if cp ../easysandbox/EasySandbox.so EasySandbox.so; then
			chmod +x EasySandbox.so
		else
			shj_log 'EasySandbox is not built. Disabling EasySandbox...'
			SANDBOX_ON=false
		fi
	fi
	if $C_SHIELD_ON; then
		shj_log "Enabling Shield For C/C++"
		# if code contains any 'undef', raise compile error:
		if tr -d ' \t\n\r\f' < code.c | grep -q '#undef'; then
			echo 'code.c:#undef is not allowed' >cerr
			EXITCODE=110
		else
			cp ../shield/shield.$EXT shield.$EXT
			cp ../shield/def$EXT.h def.h
			# adding define to beginning of code:
			echo '#define main themainmainfunction' | cat - code.c > thetemp && mv thetemp code.c
			$COMPILER shield.$EXT $OPTIONS $WARNING_OPTION -o $EXEFILE >/dev/null 2>cerr
			EXITCODE=$?
		fi
	else
		mv code.c code.$EXT
		$COMPILER code.$EXT $OPTIONS $WARNING_OPTION -o $EXEFILE >/dev/null 2>cerr
		EXITCODE=$?
	fi
	COMPILE_END_TIME=$(($(date +%s%N)/1000000));
	shj_log "Compiled. Exit Code=$EXITCODE  Execution Time: $((COMPILE_END_TIME-COMPILE_BEGIN_TIME)) ms"
	if [ $EXITCODE -ne 0 ]; then
		shj_log "Compile Error"
		shj_log "$(cat cerr | head -10)"
		echo '<span class="shj_b">Compile Error<br>Error Messages: (line numbers are not correct)</span>' >$PROBLEMPATH/$UN/result.html
		echo '<span class="shj_r">' >> $PROBLEMPATH/$UN/result.html
		SHIELD_ACT=false
		if $C_SHIELD_ON; then
			while read line; do
				if [ "`echo $line|cut -d" " -f1`" = "#define" ]; then
					if grep -wq $(echo $line|cut -d" " -f3) cerr; then
						echo `echo $line|cut -d"/" -f3` >> $PROBLEMPATH/$UN/result.html
						SHIELD_ACT=true
						break
					fi
				fi
			done <def.h
		fi
		if ! $SHIELD_ACT; then
			echo -e "\n" >> cerr
			echo "" > cerr2
			while read line; do
				if [ "`echo $line|cut -d: -f1`" = "code.c" ]; then
					echo ${line#code.c:} >>cerr2
				fi
				if [ "`echo $line|cut -d: -f1`" = "shield.c" ]; then
					echo ${line#shield.c:} >>cerr2
				fi
				if [ "`echo $line|cut -d: -f1`" = "shield.cpp" ]; then
					echo ${line#shield.cpp:} >>cerr2
				fi
			done <cerr
			(cat cerr2 | head -10 | sed 's/themainmainfunction/main/g' ) > cerr;
			(cat cerr | sed 's/&/\&amp;/g' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g' | sed 's/"/\&quot;/g') >> $PROBLEMPATH/$UN/result.html
		fi
		echo "</span>" >> $PROBLEMPATH/$UN/result.html
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "Compilation Error"
	fi
fi





########################################################################################################
################################################ TESTING ###############################################
########################################################################################################
shj_log "\nTesting..."
shj_log "$TST test cases found"

echo "" >$PROBLEMPATH/$UN/result.html

PASSEDTESTS=0


if [ -f "$PROBLEMPATH/tester.cpp" ] && [ ! -f "$PROBLEMPATH/tester.executable" ]; then
	shj_log "Tester file found. Compiling tester..."
	TST_COMPILE_BEGIN_TIME=$(($(date +%s%N)/1000000));
	g++ $PROBLEMPATH/tester.cpp -lm -O2 -o $PROBLEMPATH/tester.executable
	EC=$?
	TST_COMPILE_END_TIME=$(($(date +%s%N)/1000000));
	if [ $EC -ne 0 ]; then
		shj_log "Compiling tester failed."
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "Invalid Tester Code"
	else
		shj_log "Tester compiled. Execution Time: $((TST_COMPILE_END_TIME-TST_COMPILE_BEGIN_TIME)) ms"
	fi
fi

if [ -f "$PROBLEMPATH/tester.executable" ]; then
	shj_log "Copying tester executable to current directory"
	cp $PROBLEMPATH/tester.executable shj_tester
	chmod +x shj_tester
fi



for((i=1;i<=TST;i++)); do
	shj_log "\n=== TEST $i ==="
	echo "<span class=\"shj_b\">Test $i</span>" >>$PROBLEMPATH/$UN/result.html
	
	touch err
	
	if [ "$EXT" = "java" ]; then
		if $PERL_EXISTS; then
			./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "./timeout --just-kill -nosandbox -l $OUTLIMIT -t $TIMELIMIT java -mx${MEMLIMIT}k $JAVA_POLICY $MAINFILENAME"
		else
			./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "java -mx${MEMLIMIT}k $JAVA_POLICY $MAINFILENAME"
		fi
		EXITCODE=$?
		if grep -iq "Too small initial heap" out || grep -iq "java.lang.OutOfMemoryError" err; then
			shj_log "Memory Limit Exceeded"
			echo "<span class=\"shj_o\">Memory Limit Exceeded</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		fi
		if grep -iq "java.lang.InternalError" err; then
			shj_log "Runtime Error: java.lang.InternalError"
			echo "<span class=\"shj_o\">Runtime Error (java.lang.InternalError)</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		fi
		if grep -iq "java.lang.StackOverflowError" err; then
			shj_log "Runtime Error: java.lang.StackOverflowError"
			echo "<span class=\"shj_o\">Runtime Error (java.lang.StackOverflowError)</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		fi
		if grep -iq "java.lang.UnknownError" err; then
			shj_log "Runtime Error: java.lang.UnknownError"
			echo "<span class=\"shj_o\">Runtime Error (java.lang.UnknownError)</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		fi
	elif [ "$EXT" = "c" ] || [ "$EXT" = "cpp" ]; then
		#$TIMEOUT ./$FILENAME <$PROBLEMPATH/in/input$i.txt >out 2>/dev/null
		if $SANDBOX_ON; then
			#LD_PRELOAD=./EasySandbox.so ./$FILENAME <$PROBLEMPATH/in/input$i.txt >out 2>/dev/null
			if $PERL_EXISTS; then
				./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "./timeout --just-kill --sandbox -l $OUTLIMIT -t $TIMELIMIT -m $MEMLIMIT ./$EXEFILE"
			else
				./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "LD_PRELOAD=./EasySandbox.so ./$EXEFILE"
			fi
			EXITCODE=$?
			# remove <<entering SECCOMP mode>> from beginning of output:
			tail -n +2 out >thetemp && mv thetemp out
		else
			#./$FILENAME <$PROBLEMPATH/in/input$i.txt >out 2>/dev/null
			if $PERL_EXISTS; then
				./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "./timeout --just-kill -nosandbox -l $OUTLIMIT -t $TIMELIMIT -m $MEMLIMIT ./$EXEFILE"
			else
				./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "./$EXEFILE"
			fi
			EXITCODE=$?
		fi

	elif [ "$EXT" = "py2" ]; then
		if $PERL_EXISTS; then
			./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "./timeout --just-kill -nosandbox -l $OUTLIMIT -t $TIMELIMIT -m $MEMLIMIT python -O $FILENAME.py"
		else
			./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "python -O $FILENAME.py"
		fi
		EXITCODE=$?

	elif [ "$EXT" = "py3" ]; then
		if $PERL_EXISTS; then
			./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "./timeout --just-kill -nosandbox -l $OUTLIMIT -t $TIMELIMIT -m $MEMLIMIT python3 -O $FILENAME.py"
		else
			./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt "python3 -O $FILENAME.py"
		fi
		EXITCODE=$?

	else
		shj_log "File Format Not Supported"
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "File Format Not Supported"
	fi

	shj_log "Exit Code = $EXITCODE"

	if ! grep -q "FINISHED" err; then
		if grep -q "SHJ_TIME" err; then
			t=`grep "SHJ_TIME" err|cut -d" " -f3`
			shj_log "Time Limit Exceeded ($t s)"
			echo "<span class=\"shj_o\">Time Limit Exceeded</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		elif grep -q "SHJ_MEM" err; then
			shj_log "Memory Limit Exceeded"
			echo "<span class=\"shj_o\">Memory Limit Exceeded</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		elif grep -q "SHJ_HANGUP" err; then
			shj_log "Hang Up"
			echo "<span class=\"shj_o\">Process hanged up</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		elif grep -q "SHJ_SIGNAL" err; then
			shj_log "Killed by a signal"
			echo "<span class=\"shj_o\">Killed by a signal</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		elif grep -q "SHJ_OUTSIZE" err; then
			shj_log "Output Size Limit Exceeded"
			echo "<span class=\"shj_o\">Output Size Limit Exceeded</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		fi
	else
		t=`grep "FINISHED" err|cut -d" " -f3`
		shj_log "Time: $t s"
	fi
	
	if [ $EXITCODE -eq 137 ]; then
		#shj_log "Time Limit Exceeded (Exit code=$EXITCODE)"
		#echo "<span style='color: orange;'>Time Limit Exceeded</span>" >>$PROBLEMPATH/$UN/result.html
		shj_log "Killed"
		echo "<span class=\"shj_o\">Killed</span>" >>$PROBLEMPATH/$UN/result.html
		continue
	fi


	if [ $EXITCODE -ne 0 ]; then
		shj_log "Runtime Error"
		echo "<span class=\"shj_o\">Runtime Error</span>" >>$PROBLEMPATH/$UN/result.html
		continue
	fi
	
	# checking correctness of output
	ACCEPTED=false
	if [ -f shj_tester ]; then
		./shj_tester $PROBLEMPATH/in/input$i.txt $PROBLEMPATH/out/output$i.txt out
		EC=$?
		if [ $EC -eq 0 ]; then
			ACCEPTED=true
		fi
	else
		cp $PROBLEMPATH/out/output$i.txt correctout
		if [ "$DIFFOPTION" = "ignore" ];then
			# Removing all newlines and whitespaces before diff
			tr -d ' \t\n\r\f' <out >tmp1 && mv tmp1 out;
			tr -d ' \t\n\r\f' <correctout >tmp1 && mv tmp1 correctout;
		fi
		# Add a newline at the end of both files
		echo "" >> out
		echo "" >> correctout
		# Compare output files
		if $DIFFTOOL $DIFFARGUMENT out correctout >/dev/null 2>/dev/null
		then
			ACCEPTED=true
		fi
	fi

	if $ACCEPTED; then
		shj_log "ACCEPTED"
		echo "<span class=\"shj_g\">ACCEPT</span>" >>$PROBLEMPATH/$UN/result.html
		((PASSEDTESTS=$PASSEDTESTS+1))
	else
		shj_log "WRONG"
		echo "<span class=\"shj_r\">WRONG</span>" >>$PROBLEMPATH/$UN/result.html
	fi
done

cd ..
rm -r $JAIL >/dev/null 2>/dev/null # removing files

((SCORE=PASSEDTESTS*10000/TST)) # give score from 10,000
shj_log "\nScore from 10000: $SCORE"

shj_finish $SCORE