#!/usr/bin/env python3
import sys
import getopt
import xml.etree.ElementTree as ET

def get_descriptor(file):
	"""For bound root i xml from STDIN or external file, 
	take one argument -> bool 
	file is true if input is from external file else false """
	try:
		if file:
			tree = ET.parse(arg)
			return tree.getroot()
		else:
			tree = ET.parse(sys.stdin)
			return tree.getroot()

	except IOError: # file doesnt exists
			print("Err->11")
			sys.exit(11)
	except: # check if xml has  well structure. 
			print("Err->31")
			sys.exit(31)

def regex_match(get_non_term,value, ref_non_terminal):
	return True


def parse_args(formal_args, inst):
	if(len(formal_args) != len(inst)):
		print("Err->31")
		sys.exit(31)
	counter = 1
	for arg,form_arg in zip(inst,formal_args):
		if(arg.tag != 'arg'+str(counter)) or ('type' not in arg.attrib) or (len(arg.attrib) != 1):
			print("Err->31")
			sys.exit(31)
		elif (not regex_match(arg.attrib['type'],arg.text,form_arg)):	
			print('Err->31asdad')
			sys.exit(31)
		counter +=1
	return True	

def parse_instruction(inst,order,inst_dict):
	if(('order' not in inst.attrib) or ('opcode' not in inst.attrib)):
		print("Err->31")
		sys.exit(31)
	elif(inst.attrib['order'] != str(order)) or (inst.attrib['opcode'] not in inst_dict):
		print("Err->31")
		sys.exit(31)
	else:
		parse_args(inst_dict[inst.attrib['opcode']],inst)

def parse_xml(file):
	instruction_dict = {'MOVE':['var','symb'],'CREATEFRAME':[],'PUSHFRAME':[],'POPFRAME':[],'DEFVAR':['var'],'CALL':['label'],
	'RETURN':[],'PUSHS':['symb'],'POPS':['var'],'ADD':['var','symb','symb'],'SUB':['var','symb','symb'],'MUL':['var','symb','symb'],
	'IDIV':['var','symb','symb'],'LT':['var','symb','symb'],'GT':['var','symb','symb'],'EQ':['var','symb','symb'],
	'AND':['var','symb','symb'],'OR':['var','symb','symb'],'NOT':['var','symb'],'INT2CHAR':['var','symb'],'STRI2INT':['var','symb','symb'],
	'READ':['var','type'],'WRITE':['symb'],'CONCAT':['var','symb','symb'],'STRLEN':['var','symb'],'GETCHAR':['var','symb','symb'],
	'SETCHAR':['var','symb','symb'],'TYPE':['var','symb'],'LABEL':['label'],'JUMP':['label'],'JUMPIFEQ':['label','symb','symb'],
	'JUMPIFNEQ':['label','symb','symb'],'DPRINT':['symb'],'BREAK':[]
	}

	program = get_descriptor(file)
	# check header
	#<program language="IPPcode18">
	if(program.tag != "program") or ('language' not in program.attrib) or(program.attrib['language']  !=  "IPPcode18"):
		print("Err->31")
		return 31
	
	order = 1
	for instruction in program: 
		parse_instruction(instruction,order,instruction_dict)
		order += 1
		
	return 0


if __name__ == '__main__':

	file = False
	stats = False
	stats_inst = 0
	stats_var = 0

	try:
		opts, args = getopt.getopt(sys.argv[1:],"",["help","source=","stats=","insts","vars"])
	except getopt.GetoptError:
		print("Err->10")
		sys.exit(10);
	count = 0
	for opt,arg in opts:
		count += 1
		if (opt == "--help" and len(opts) == 1):
			print("""Napoveda
Spousti se ve verzi 3.6
python3.6 ./interpret.py
[--help] [--source=file] [--stats=file] [--insts] [--vars]
kde file je vstupni soubor jinak cte ze STDIN
stats udava soubor do ktereho se budou ukladat statistiky
isnts - program bude pocitat instrukce
vars - priogram bude pocitat promene""")
			sys.exit(0);
		elif opt == "--source":
			file = True
		elif opt == "--stats":
			stats_file = arg
			stats = True	
		elif (opt =="--insts"):
			stats_inst = count	
		elif opt =="--vars":
			stats_var = count
		else:
			print("Err->10")
			sys.exit(10)	

	# mam tady poradi inst a var v nejakym relativnim cisle
	#nebo 0 pokud neni zadano
	if(stats_inst > 0 or stats_var > 0) and stats == False:
		print("Err->10")
		sys.exit(10)
		
	rv = parse_xml(file)	
	print(rv)
	print("OK")
	sys.exit(rv)
