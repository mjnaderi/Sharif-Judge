#! /bin/bash

failed=0

for t in $@; do
	./runtest.sh $t 2> /dev/null
	if [ $? != 0 ]; then
		failed=`expr $failed + 1`
	fi
done

if [ $failed = 0 ]; then
	echo "All tests passed!"
	exit 0
else
	echo "$failed test(s) failed"
	exit 1
fi
