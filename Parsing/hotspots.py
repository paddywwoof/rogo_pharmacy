flds = ['x', 'y', 'w', 'h']
fout = open('hs_update.sql', 'w')
f = open('hotspotextract.xml', 'r')
for l in f:
  if '<control name="H_' in l:
    hotspot = l.split('"')[1]
    sql = 'UPDATE hotspots SET '
    for i in flds:
      l = f.readline()
      val = l.split('>')[1].split('<')[0]
      sql += i + '=' + val + ','
    sql += 'flg=1 WHERE id = \'' + hotspot + '\';\n'
    fout.write(sql)
f.close()
fout.close()

