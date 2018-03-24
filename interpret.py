#!/usr/bin/env python3
import sys
import getopt
import xml.etree.ElementTree as ET
import re

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
	regex_var = r'^(GF|LF|TF)\x40([a-zA-Z]|\x5F|\x2D|\x24|\x25|\x26|\x2A)(\w|\x5F|\x2D|\x24|\x25|\x26|\x2A)*$'
	regex_label = r'^([a-zA-Z]|\x5F|\x2D|\x24|\x25|\x26|\x2A)(\w|\x5F|\x2D|\x24|\x25|\x26|\x2A)*$'
	regex_int = r'^(\x2D|\x2B)?\d+$'
	regex_bool = r'^(true|false)$'
	regex_string = r'^((\x5C\d{3})|[^\x23\s\x5C])*$'
	regex_type = r'^(int|bool|string)$'

	if(get_non_term == 'string') and (value == None):
		value = ""
	regex = None
	if(get_non_term == ref_non_terminal):
		
		if(ref_non_terminal == 'label'):
			regex = re.match(regex_label,value)
		elif(ref_non_terminal == 'var'):
			regex = re.match(regex_var,value)
		elif(ref_non_terminal == 'string'):
			regex = re.match(regex_string,value)
		elif(ref_non_terminal == 'int'):
			regex = re.match(regex_int,value)
		elif(ref_non_terminal == 'bool'):
			regex = re.match(regex_bool,value)
		elif(ref_non_terminal == 'type'):
			regex = re.match(regex_type,value)
	else:
		if(ref_non_terminal == 'symb'):
			if(get_non_term == 'var'):
				regex = re.match(regex_var,value)
			elif(get_non_term == 'int'):
				regex = re.match(regex_int,value)
			elif(get_non_term == 'string'):
				regex = re.match(regex_string,value)
			elif(get_non_term == 'bool'):
				regex = re.match(regex_bool,value)
						
	if regex == None:
		print("Err->31")
		sys.exit(31)

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
			print('Err->31')
			sys.exit(31)
		counter +=1
	return True	

def parse_inst(inst,order,inst_dict):
	if(('order' not in inst.attrib) or ('opcode' not in inst.attrib)):
		print("Err->31")
		sys.exit(31)
	elif(inst.attrib['order'] != str(order)) or (inst.attrib['opcode'] not in inst_dict):
		print("Err->31")
		sys.exit(31)
	else:
		return parse_args(inst_dict[inst.attrib['opcode']],inst)

def get_labels(inst,order,labels):

	if(inst.attrib['opcode'] == 'LABEL'):
		if(inst[0].text in labels):
			print("Err->52")
			sys.exit(52)
		else:
			labels.update({inst[0].text:int(inst.attrib['order'])})
	return True

def check_var(name,frame,global_f,local_f,tmp_f):
	if (frame == 'GF'):
		if not name in global_f:
			print("Err->54")
			sys.exit(54)			
	if (frame == 'LF'):
		if local_f == None:
			print("Err->55")
			sys.exit(55)
		elif not name in local_f:
			print("Err->54")
			sys.exit(54)
	if (frame == 'TF'):
		if tmp_f == None:
			print("Err->55")
			sys.exit(55)
		elif not name in tmp_f:
			print("Err->54")
			sys.exit(54)
			
def set_var(value,name,frame,global_f,local_f,tmp_f):
	if(frame == "GF"):
		global_f[name] = value
	elif(frame == "LF"):
		local_f[name] = value
	elif(frame == "TF"):
		tmp_f[name] = value

def get_var(name,frame,global_f,local_f,tmp_f):
	if(frame == "GF"):
		return global_f[name]
	elif(frame == "LF"):
		return local_f[name]
	elif(frame == "TF"):
		return tmp_f[name]

def move(dest,value,typ,global_f,local_f,tmp_f):

	check_var(dest[3:],dest[0:2],global_f,local_f,tmp_f)
	if(value == None) and (typ == "string"):
		value = ""	
	if(typ == "var"):
		check_var(value[3:],value[0:2],global_f,local_f,tmp_f)
		value = get_var(value[3:],value[0:2],global_f,local_f,tmp_f)
	elif(typ == "int"):
		value = int(value)
	elif(typ == "bool"):
		value = str_to_bool(value)
	else:
		value = remove_backslash(value)	
		
	set_var(value,dest[3:],dest[0:2],global_f,local_f,tmp_f)

#TODO - zjistit jestli redefinice je problem
def defvar(variable,global_f,local_f,tmp_f):
	where = variable[0:2]
	name = variable[3:]
	if(where == 'GF'):
		global_f[name] = None
	elif(where == 'LF'):
		if(local_f != None):
			local_f[name] = None
		else:
			print("Err->55")
			sys.exit(55)	
	elif(where == 'TF'):
		if(tmp_f != None):
			tmp_f[name] = None
		else:
			print("Err->55")
			sys.exit(55)
	else:
		print("Err->32")
		sys.exit(32)

def bool_lower(value):
	if(value == False):
		return 'false'
	else:
		return 'true'	

def str_to_bool(value):
	if(value == 'true'):
		return True
	else:
		return False

def remove_backslash(text):
	result = ""
	if text == None:
		return result
	i = 0
	while i < len(text):
		if(text[i] == '\\'):
			num = text[i+1]+text[i+2]+text[i+3]
			result += chr(int(num))
			i +=4
		else:
			result += text[i]
			i+=1
	return result

def get_value(oper,global_f,local_f,tmp_f):
	
	if(oper.attrib['type'] == 'var'):
		check_var(oper.text[3:],oper.text[0:2],global_f,local_f,tmp_f)
		return get_var(oper.text[3:],oper.text[0:2],global_f,local_f,tmp_f)		
	elif(oper.attrib['type'] == 'int'):
		return int(oper.text)
	elif(oper.attrib['type'] == 'bool'):
		return str_to_bool(oper.text)
	else:
		return remove_backslash(oper.text)	

def aritmetic_instr(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	if(type(var1) != int):
		print("Err->53")
		sys.exit(53)		

	var2 = get_value(inst[2],global_f,local_f,tmp_f)
	if(type(var2) != int):
		print("Err->53")
		sys.exit(53)		

	if(inst.attrib['opcode'] == 'ADD'):
		result = var1 + var2
	elif(inst.attrib['opcode'] == 'SUB'):
		result = var1 - var2
	elif(inst.attrib['opcode'] == 'MUL'):
		result = var1 * var2
	elif(inst.attrib['opcode'] == 'IDIV'):
		if(var2 == 0):
			print("Err->57")
			sys.exit(57)
		else:
			result = var1 // var2

	set_var(result,inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)						


def logic_instr(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
	
	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	
	if(inst.attrib['opcode'] != 'NOT'):
		var2 = get_value(inst[2],global_f,local_f,tmp_f)
	
	if(inst.attrib['opcode'] in ['LT','GT','EQ']):
		if(type(var1) == type(var2)):
			if(inst.attrib['opcode'] == 'LT'):
				result = var1 < var2
			elif(inst.attrib['opcode'] == 'GT'):
				result = var1 > var2
			else:
				result = var1 == var2
		else:
			print("Err->53")
			sys.exit(53)	
	elif(inst.attrib['opcode'] in ['AND','OR']):
		
		if(type(var1) == type(var2) == bool):
			if(inst.attrib['opcode'] == 'AND'):
				result = var1 and var2
			else:
				result = var1 or var2
		else:
			print("Err->53")
			sys.exit(53)	
	else:
		if(type(var1) == bool):
			result = not var1
		else:
			print("Err->53")
			sys.exit(53)	

	set_var(result,inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
		
def int2char(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	if(type(var1) != int):
		print("Err->53")
		sys.exit(53)

	try:
		set_var(chr(var1),inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
	except ValueError:
		print("Err->58")
		sys.exit(58)	

def char2int(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	if(type(var1) != str):
		print("Err->53")
		sys.exit(53)
	
	var2 = get_value(inst[2],global_f,local_f,tmp_f)
	if(type(var2) != int):
		print("Err->53")
		sys.exit(53)

	if(len(var1) <= var2):
		print("Err->58")
		sys.exit(58)	
	else:
		set_var(ord(var1[var2]),inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

def concat(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	if(type(var1) != str):
		print("Err->53")
		sys.exit(53)
	
	var2 = get_value(inst[2],global_f,local_f,tmp_f)
	if(type(var2) != str):
		print("Err->53")
		sys.exit(53)

	set_var(var1+var2,inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

def strlen(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	if(type(var1) != str):
		print("Err->53")
		sys.exit(53)

	set_var(len(var1),inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

def getchar(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	if(type(var1) != str):
		print("Err->53")
		sys.exit(53)
	
	var2 = get_value(inst[2],global_f,local_f,tmp_f)
	if(type(var2) != int):
		print("Err->53")
		sys.exit(53)

	if(len(var1) <= var2):
		print("Err->58")
		sys.exit(58)	

	set_var(var1[var2],inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)


def setchar(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
	dest = get_value(inst[0],global_f,local_f,tmp_f)
	if(type(dest) != str):
		print("Err->53")
		sys.exit(53)

	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	if(type(var1) != int):
		print("Err->53")
		sys.exit(53)
	
	var2 = get_value(inst[2],global_f,local_f,tmp_f)
	if(type(var2) != str):
		print("Err->53")
		sys.exit(53)

	if(len(dest) <= var1) or (len(var2) == 0):
		print("Err->58")
		sys.exit(58)	

	dest = dest[:var1] + var2[0] + dest[var1+1:]
	set_var(dest,inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

def get_typ(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
	var1 = get_value(inst[1],global_f,local_f,tmp_f)
	if(type(var1) == int):
		result = "int"
	elif(type(var1) == bool):
		result = "bool"
	else:
		result = "string"
	set_var(result,inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

def jump_instr(inst,global_f,local_f,tmp_f,labels):

	if(inst[0].text not in labels):
		print("Err->52")
		sys.exit(52)

	if(inst.attrib['opcode'] == 'JUMP'):
		return [True, labels[inst[0].text]]
	else:
		var1 = get_value(inst[1],global_f,local_f,tmp_f)		
		var2 = get_value(inst[2],global_f,local_f,tmp_f)
		
		if(type(var1) == type(var2)):
			if(inst.attrib['opcode'] == 'JUMPIFEQ') and (var1 == var2):
				return [True,labels[inst[0].text]]
			elif(inst.attrib['opcode'] == 'JUMPIFNEQ') and (var1 != var2):
				return [True,labels[inst[0].text]]
		else:
			print("Err->53")
			sys.exit(53)	
			
	return [False,0]

def read(inst,global_f,local_f,tmp_f):
	check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
	
	try:
		result = input()
	except:
		if(inst[1].text == 'int'):
			result = 0
		elif(inst[1].text == 'bool'):
			result = False
		else:
			result = ""	

	if(inst[1].text == 'int'):
		result = int(result)
	elif(inst[1].text == 'bool'):
		result = str_to_bool(result)
	else:
		reuslt = remove_backslash(result)
	set_var(result,inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)

def write(inst,global_f,local_f,tmp_f):
	var1 = get_value(inst[0],global_f,local_f,tmp_f)
	if(type(var1) == bool):
		var1 = bool_lower(var1)
	if(inst.attrib['opcode'] == 'WRITE'):
		print(var1)
	else:
		print(var1,file=sys.stderr)		

def do_inst(inst,global_f,local_f,tmp_f,labels,call_stack,inst_pointer,local_frames,value_stack,counter):
	#print("DO => "+inst.attrib['opcode'])
	#		FRAMES AND CALLS FUNCTION 
	if(inst.attrib['opcode'] == 'CREATEFRAME'):
		tmp_f = {}
	elif(inst.attrib['opcode'] == 'PUSHFRAME'):
		if(tmp_f != None):
			local_frames.append(tmp_f)
			local_f = local_frames[len(local_frames)-1]
			tmp_f = None
		else:
			print("Err->55")
			sys.exit(55)	

	elif(inst.attrib['opcode'] == 'POPFRAME'):
		if(local_f != None):
			tmp_f = local_frames.pop()
			if(local_frames == []):
				local_f = None
			else:
				local_f = local_frames[len(local_frames)-1]	
		else:
			print("Err->55")
			sys.exit(55)	

	elif(inst.attrib['opcode'] == 'DEFVAR'):
		defvar(inst[0].text,global_f,local_f,tmp_f)
	elif(inst.attrib['opcode'] == 'MOVE'):
		move(inst[0].text,inst[1].text,inst[1].attrib['type'],global_f,local_f,tmp_f)

	elif(inst.attrib['opcode'] == 'CALL'):
			call_stack.append(inst_pointer+1)
			return [True,labels[inst[0].text]],local_f,tmp_f
	elif(inst.attrib['opcode'] == 'RETURN'):
			if(call_stack == []):
				print("Err->56")
				sys.exit(56)
			else:
				return [True,call_stack.pop()],local_f,tmp_f
	
	#  DATA STACK
	elif(inst.attrib['opcode'] == 'PUSHS'):
		if(inst[0].attrib['type'] == "var"):
			check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
			value_stack.append(get_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f))
		else:
			value_stack.append(inst[0].text)
	elif(inst.attrib['opcode'] == 'POPS'):
		if(value_stack != []):
			check_var(inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
			set_var(value_stack.pop(),inst[0].text[3:],inst[0].text[0:2],global_f,local_f,tmp_f)
		else:
			print("Err->56")
			sys.exit(56)
	#	ARITMETIC INTRUCTION
	elif(inst.attrib['opcode'] in ['ADD','SUB','MUL','IDIV']):
		aritmetic_instr(inst,global_f,local_f,tmp_f)
	
	#	LOGIC INTRUCTION
	elif(inst.attrib['opcode'] in ['LT','GT','EQ','AND','OR','NOT']):
		logic_instr(inst,global_f,local_f,tmp_f)		

	# STRING
	elif(inst.attrib['opcode'] == 'INT2CHAR'):
		int2char(inst,global_f,local_f,tmp_f)

	elif(inst.attrib['opcode'] == 'STRI2INT'):
		char2int(inst,global_f,local_f,tmp_f)

	elif(inst.attrib['opcode'] == 'CONCAT'):
		concat(inst,global_f,local_f,tmp_f)

	elif(inst.attrib['opcode'] == 'STRLEN'):
		strlen(inst,global_f,local_f,tmp_f)	

	elif(inst.attrib['opcode'] == 'GETCHAR'):
		getchar(inst,global_f,local_f,tmp_f)
		
	elif(inst.attrib['opcode'] == 'SETCHAR'):
		setchar(inst,global_f,local_f,tmp_f)
	# I/O INSTRUCTION + DEBAG DPRINT
	elif(inst.attrib['opcode'] == 'READ'):
		read(inst,global_f,local_f,tmp_f)

	elif(inst.attrib['opcode'] in ['WRITE','DPRINT']):
		write(inst,global_f,local_f,tmp_f)
	
	# TYPE
	elif(inst.attrib['opcode'] == 'TYPE'):
		get_typ(inst,global_f,local_f,tmp_f)
	
	# CONTROL INSTRUCTION
	elif(inst.attrib['opcode'] in ['JUMP','JUMPIFNEQ','JUMPIFEQ']):
		return jump_instr(inst,global_f,local_f,tmp_f,labels),local_f,tmp_f
	
	# DEBUG INSTRUCTION
	elif(inst.attrib['opcode'] == 'BREAK'):
		print("Pozition=>"+inst_pointer,file=sys.stderr)
		print("Global frame=>"+global_f,file=sys.stderr)		
		print("Local frame=>"+local_f,file=sys.stderr)		
		print("Temporery frame=>"+tmp_f,file=sys.stderr)		
		print("Exexuted instruction=>"+counter,file=sys.stderr)	
	return [False,0],local_f,tmp_f

def max_alloc(global_f,local_f,tmp_f):
	result = 0
	if(local_f != None):
		result = len(local_f)
	if(tmp_f != None):
		result += len(tmp_f)
	return result + len(global_f)

def interpretation(code,labels,max_ip):
	
	local_frames = []
	local_f = None
	global_f = {}
	tmp_f = None
	call_stack = []
	value_stack = []

	inst_pointer = 0
	counter = 0
	label = [False,0]
	max_vars = 0

	while(inst_pointer < max_ip):
		counter += 1
		label,local_f,tmp_f = do_inst(code[inst_pointer],global_f,local_f,tmp_f,labels,call_stack,inst_pointer,local_frames,value_stack,counter)
	
		if(label[0]):
			inst_pointer = label[1]
			label = [False,0]
		else:
			inst_pointer +=1

		max_vars = max(max_vars,max_alloc(global_f,local_f,tmp_f))

	print("\n\n#####FRAMES#####")
	print("local=>	"+str(local_f))
	print("global=>	"+str(global_f))
	print("tmp=>	"+str(tmp_f))
	print("stack_of_frames=>"+str(local_frames))
	print("\n\n")

	return counter,max_vars

def parse_xml(file):
	inst_dict = {'MOVE':['var','symb'],'CREATEFRAME':[],'PUSHFRAME':[],'POPFRAME':[],'DEFVAR':['var'],'CALL':['label'],
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
		sys.exit(31)
	
	order = 1
	labels = {}
	for inst in program: 
		parse_inst(inst,order,inst_dict)
		get_labels(inst,order,labels)
		order += 1
	
	return interpretation(program,labels,order-1)

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
		
	num_inst,max_vars = parse_xml(file)	

	if(stats == True):
		try:
			out = open(stats_file,'w')
		except IOError:
			print("Err->12")
			sys.exit(12)
		else:
			if(stats_inst > 0) and (stats_var > 0):
				if(stats_inst > stats_var):
					out.write(max_vars)
					out.write(num_inst)
				else:
					out.write(num_inst)
					out.write(max_vars)
			elif(stats_inst > 0):
				out.write(num_inst)
			elif(stats_var > 0):
				out.write(max_vars)
		finally:
			out.close()				

#	print("instruction=>"+str(num_inst))
#	print("vars=>"+str(max_vars))
#	print("OK")
	sys.exit(0)
