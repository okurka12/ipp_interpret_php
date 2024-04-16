#!/bin/bash

# originally from repository for the parse.py assignment
# (https://github.com/okurka12/ipp_project)

# o k u r k a 1 2

RED='\033[0;31m'
YELLOW='\033[0;33m'
COLOR_RESET='\033[0m'

TEST_COUNT=0
PASSED_TESTS=0

SHOW_STDOUT=true

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
    if $SHOW_STDOUT; then
        STDOUT=$(cat "$1" | php interpret.php --source=$1 2> /dev/null)
    else
        STDOUT=$(cat "$1" | php interpret.php --source=$1 > /dev/null 2> /dev/null)
    fi
    test_val $? $2 "$1"
    if $SHOW_STDOUT; then
        echo -n "    stdout: "
        echo -n -e "$YELLOW"
        echo "$STDOUT" | sed "s/^/    /"
        echo -n -e "$COLOR_RESET"
    fi
}

################################################################################
test_file test_source00.xml 31
test_file test_source01.xml 0
test_file test_source02.xml 54
test_file test_source03.xml 32  # invalid xml
test_file test_source04.xml 52  # unknown label
test_file test_source05.xml 52  # unknown label
test_file test_source06.xml 52  # unknown label
test_file test_source07.xml 0
test_file test_source08.xml 52  # variable redefinition
test_file test_source09.xml 54  # access to non-existent variable
test_file test_source10.xml 0
test_file test_source11.xml 55  # non-existent local frame
test_file test_source12.xml 55  # non-existent temporary frame
test_file test_source13.xml 54  # non-existent global variable
test_file test_source14.xml 54  # non-existent variable in non-existent frame
test_file test_source15.xml 54  # non-existent variable in non-existent frame
test_file test_source16.xml 32  # invalid xml
test_file test_source17.xml 32  # invalid xml
test_file test_source18.xml 32  # invalid xml
test_file test_source19.xml 0
test_file test_source20.xml 0
test_file test_source21.xml 54  # access to non-existent variable
test_file test_source22.xml 0
test_file test_source23.xml 0
test_file test_source24.xml 0
test_file test_source25.xml 0
test_file test_source26.xml 0
test_file test_source27.xml 0
test_file test_source28.xml 0
test_file test_source29.xml 0
test_file test_source30.xml 0
test_file test_source31.xml 0
test_file test_source32.xml 53  # type error for ADD
test_file test_source33.xml 53  # type error for ADD
test_file test_source34.xml 0
test_file test_source35.xml 0
test_file test_source36.xml 0
test_file test_source37.xml 0
test_file test_source38.xml 0
test_file test_source39.xml 0
test_file test_source40.xml 0
test_file test_source41.xml 0
test_file test_source42.xml 53  # jumpifeq incompatible types
test_file test_source43.xml 53  # jumpifneq incompatible types
test_file test_source44.xml 0
test_file test_source45.xml 0
test_file test_source46.xml 0
test_file test_source47.xml 0
test_file test_source48.xml 0
test_file test_source49.xml 56


echo "all tests done (passed $PASSED_TESTS/$TEST_COUNT)"
