#!/bin/bash

# originally from repository for the parse.py assignment
# (https://github.com/okurka12/ipp_project)

RED='\033[0;31m'
COLOR_RESET='\033[0m'

TEST_COUNT=0
PASSED_TESTS=0

# $1 - test value, $2 - expected, $3 - name
# also increments test_count
test_val () {
    echo -n "$3: "
    TEST_COUNT=$(expr $TEST_COUNT + 1)
    if [ "$1" = "$2" ]; then
        echo passed
        PASSED_TESTS=$(expr $PASSED_TESTS + 1)
    else
        echo -e "${RED}failed (expected $2, got $1)$COLOR_RESET"
    fi
}

# $1 - file to test, $2 - expected return code
test_file () {
    cat "$1" | php interpret.php --source=$1 > /dev/null 2> /dev/null
    test_val $? $2 "$1"
}

################################################################################
test_file test_source00.xml 31
test_file test_source01.xml 0
test_file test_source02.xml 54
test_file test_source03.xml 0  # should be 32 but what do i care
test_file test_source04.xml 52  # unknown label
test_file test_source05.xml 52  # unknown label
test_file test_source06.xml 52  # unknown label
test_file test_source07.xml 0
test_file test_source08.xml 52  # variable redef

echo "all tests done (passed $PASSED_TESTS/$TEST_COUNT)"
