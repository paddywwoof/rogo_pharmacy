import csv, re
from xml_globals import *
print('here')

symptom = 'combined'
with open(symptom + '.csv', 'r') as f:
  reader = csv.reader(f)
  csv_list = [i for i in reader]

######## generate xml file
with open('head.xml', 'r') as f:
  f_head = f.read()
with open('tail.xml', 'r') as f:
  f_tail = f.read()

with open('combineda.xml', 'w') as f:
  f.write(f_head)
  loc_list = {} # to keep track of Boolean_Attributes
  ## go through csv_list
  n = len(csv_list)
  for iq, line in enumerate(csv_list):
    if line[0] != '': #question aka variable line
      v_name = 'Ind{:04}'.format(iq) # TODO stem of csv file
      for ind in range(1, len(line)): # ind is the indent level for variable
        if line[ind] != '':
          break
      v_desc = line[ind].strip()
      #v_desc = line[ind].split('#')[0].strip() # remove comments
      v_desc = v_desc.replace(" )", ")") # tidy floating braces
      v_desc = v_desc.replace("( ", "(")
      #v_desc = re.sub(r'\[.\]', ' ' + symptom, v_desc) # replace [x]
      v_name += ''.join([i.capitalize() for i in v_desc.split()])[0:24]
      v_name = re.sub(r'\W|[aeiou]', '', v_name)
      v_you = YOU_TEXT.format(line[QOFF]) if line[QOFF] != '' else ''
      v_alt = ALT_TEXT.format(line[ROFF]) if line[ROFF] != '' else ''
      v_sig = SIG_TEXT if line[SOFF] != '' else ''
      if 'N' in line[0]: # numeric
        f.write(NUMERIC_TD_Q.format(v_name, v_desc, v_you, v_alt))
      elif 'T' in line[0]: # text
        f.write(TEXT_TD_Q.format(v_name, v_desc, v_you, v_alt))
      elif 'L' in line[0] or 'H' in line[0]: # L (list) or H (hotspot)
        h_line = True if 'H' in line[0] else False
        if 'M' in line[0]:
          v_mult = 'true' # multiselect
          v_mult2 = '<multiSelect>true</multiSelect>'
        else:
          v_mult = 'false'
          v_mult2 = ''
        v_vol = '<allowBlank>true</allowBlank>' if 'V' in line[0] else '' 
        f.write(LIST_TD_Q_HEAD.format(v_name, v_mult, v_desc, v_mult2, v_you, v_alt, v_sig, v_vol))
        # find next variable at this indent
        nextq = n
        for i in range(iq + 1, n):
          for j in range(1, ind + 1):
            if csv_list[i][j] != '':
              nextq = i
              break
          if nextq < n:
            break
        if h_line:
          h_list_a = ''
        # get list of values
        for i in range(iq + 1, nextq):
          v_val = csv_list[i][ind + 1]
          if v_val != '':
            v_key = 'H_' if h_line else 'V_'
            v_key = v_key + str(i)
            v_val = v_val.strip()
            #v_val = v_val.split('#')[0].strip()
            f.write(LIST_TD_Q_ITEM.format(v_key, v_val))
            if h_line:
              loc_list[v_val] = v_key
              left = csv_list[i][ind + 2]
              top = csv_list[i][ind + 3]
              width = csv_list[i][ind + 4]
              height = csv_list[i][ind + 5]
              h_list_a = h_list_a + HOTSPOT_A.format(v_key, left, top, width, height, v_name, v_key)
        f.write(LIST_TD_Q_TAIL)
        if h_line:
          f.write(DIALOG.format(v_name + 'D', v_desc, v_desc, h_list_a, v_name, v_desc))
        
  f.write(f_tail)

