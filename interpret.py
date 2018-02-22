#!/usr/bin/env python3

import sys

#if __name__ == '__main__':
#	print("jsem v pythonu")
#	file = open(sys.argv[1][9::],'r')
#	print(file.read())


print("jsem v pythonu")
file = open(sys.argv[1][9::],'r')
print(file.read())

for line in sys.stdin:
    print(line)