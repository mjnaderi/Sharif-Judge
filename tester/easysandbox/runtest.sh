#! /bin/bash

testexe=$1
testname=`basename ${testexe}`

echo -n "Executing ${testexe}..."

input=oracle/${testname}.in
actual=/tmp/actual$$
expected=oracle/${testname}.out

if [ ! -r oracle/${testname}.in ]; then
	# Test does not expect input
	LD_PRELOAD=./EasySandbox.so ./${testexe} > ${actual} 2> /dev/null
	testexe_rc=$?
else
	# Test expects input from stdin
	LD_PRELOAD=./EasySandbox.so ./${testexe} < ${input} > ${actual} 2> /dev/null
	testexe_rc=$?
fi
diff ${actual} ${expected}
diff_rc=$?

actual_output=`cat ${actual}`
rm -f ${actual}
if [ $diff_rc != 0 ]; then
	echo "failed (output mismatch, expected [`cat ${expected}`], got [${actual_output}])"
	exit 1
fi

expected_rc=`cat oracle/${testname}.exit`
if [ $testexe_rc != $expected_rc ]; then
	echo "failed (exit code mismatch, expected ${expected_rc}, got ${testexe_rc})"
	exit 1
fi

echo "passed!"
exit 0
