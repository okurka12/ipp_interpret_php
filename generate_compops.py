tru = """LT string@a string@b
LT int@69 int@420
LT bool@false bool@true
GT string@b string@a
GT int@420 int@69
GT bool@true bool@false
EQ string@a string@a
EQ int@69 int@69
EQ bool@true bool@true"""

fals = """LT string@b string@a
LT int@420 int@69
LT bool@true bool@false
GT string@a string@b
GT int@69 int@420
GT bool@false bool@true
EQ string@a string@b
EQ int@69 int@420
EQ bool@true bool@false
EQ bool@true nil@nil
EQ string@a nil@nil
EQ int@69 nil@nil
EQ nil@nil bool@true
EQ nil@nil string@a
EQ nil@nil int@69
"""

vcount = 0

def f(s, exp):
    global vcount
    for line in s.splitlines():
        opcode, s1, s2 = line.split()
        print(f"""DEFVAR GF@variable{vcount}
DEFVAR GF@variable{vcount + 1}
DEFVAR GF@variable{vcount + 2}
MOVE GF@variable{vcount + 1} {s1}
MOVE GF@variable{vcount + 2} {s2}
{opcode} GF@variable{vcount} GF@variable{vcount + 1} GF@variable{vcount + 2}
WRITE string@{vcount:02}-tady-by-melo-byt-{exp}:\\032
WRITE GF@variable{vcount}
WRITE string@\\010

""")
        vcount += 3

print(".ippcode24")
f(tru, "true")
f(fals, "false")
