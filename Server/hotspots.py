import csv, re
#with ('hotspotextract.xml', 'r') as f:
with ('error_log', 'r') as f:
  for l in f:
    if '<control name="H_' in l:
      hotspot = l.split('"')[1]
      print(hotspot)
'''
with open('combined.xml', 'w') as f:
  f.write(f_head)
  loc_list = {} # to keep track of Boolean_Attributes
  ## go through csv_list
'''
