<?php
require_once 'common.inc';
if (isset($_POST['xmldata'])) {
  $xml_str = $_POST['xmldata'];
  file_put_contents('temp_string.txt', $xml_str);
}
else {
  $xml_str = '<?xml version="1.0" encoding="ISO-8859-1" ?>
  <data>
    <Tree_Dialog1 uid="25219072">__XR__EMPTY__</Tree_Dialog1>
    <Ind0226PlsGvDtls uid="25211904" alttext="Antacid details" description="Please give details"><![CDATA[not known number of white pills, bad taste]]></Ind0226PlsGvDtls>
    <Ind0180WhchSrtsOfLqdsMkT uid="25209600" description="Which sorts of liquids make the pain worse" listDesc="Fizzy Drinks, Fruit Juces, Other drinks">182|183|184</Ind0180WhchSrtsOfLqdsMkT>
    <Ind0008WhtGndrIsThPtnt uid="25180672" description="What gender is the patient" youtext="What gender are you" listDesc="female">10</Ind0008WhtGndrIsThPtnt>
    <H_260 uid="25206272" description="right hand fingers and thumb" question="Rash location">Yes</HS112>
    <H_261 uid="25206272" description="tummy button" question="Rash location">Yes</HS112>
    <H_262 uid="25206272" description="left foot" question="Rash location">__XR__EMPTY__</HS112>
    <Ind0125DsThPnSprdTThJ uid="29401600" description="Does the pain spread to the jaw, back or arms" listDesc="don\'t know">135</Ind0125DsThPnSprdTThJ>
    <Ind0126DsThPnSprdTThJ uid="29401600" description="Does the tingling spread to the rest of your body" sigFlag="x" listDesc="don\'t know">135</Ind0125DsThPnSprdTThJ>
  </data>';
}
$xml_parser = xml_parser_create();
xml_parse_into_struct($xml_parser, $xml_str, $values, $index);
xml_parser_free($xml_parser);
file_put_contents('temp_xmlstr.txt', print_r($values, true));
$report = '';
$hot_list = array();
$last_instance = '';
$list_str1 = '';
$list_str2 = '<BR />';
foreach ($values as $val) {
  unset($desc);
  if ($val['tag'] == 'INSTANCES' && $val['type'] == 'open') {
    $report .= '<UL>';
    $list_str1 = '<LI>';
    $list_str2 = '</LI>';
  }
  else if ($val['tag'] == 'INSTANCES' && $val['type'] == 'close') {
    $report .= '</UL>';
    $list_str1 = '';
    $list_str2 = '<BR />';
  }
  else if (isset($val['value'])) {
    $valstr = '' . $val['value'];
    if ($valstr != '__XR__EMPTY__') {
      if (isset($val['attributes'])) {
        $attr = $val['attributes'];
        if (isset($attr['ALTTEXT']))
          $desc = $attr['ALTTEXT'];
        else if (isset($attr['DESCRIPTION']))
          $desc = $attr['DESCRIPTION'];
        if (isset($desc)) {
          if (isset($attr['LISTDESC'])) {
            $valstr = $attr['LISTDESC'];
            // filter out negatives unless significant
            if (($valstr == "no" || $valstr == "don't know") && !isset($attr['SIGFLAG'])) {
              unset($desc);
            }
            else if (substr($val['value'], 0, 2) == 'H_') { // add to hotspot list
              // build info for images
              $hotspot_list = explode('|', $val['value']);
              foreach($hotspot_list as $hotspot) {
                $hot_list[] = $hotspot;
              }
            }
          }
          // filter out zero ages
          if ($desc == 'Years old' && $valstr == '0') unset($desc);
          else if ($desc == 'Months old' && $valstr == '0') unset($desc);
          else if ($desc == 'Weeks old' && $valstr == '0') unset($desc);
          // add to report
          if (isset($desc)) {
            if ($val['tag'] != 'INSTANCE' || $desc != $last_instance) 
              $report .= $list_str1 . "<strong>" . $desc . ":</strong> " . $valstr . $list_str2;
            if ($val['tag'] == 'INSTANCE')
              $report .= '<BR />';
              $last_instance = $desc;
          }
        }
      }
    }
  }
}
$q = 'SELECT image, title, x, y, w, h FROM hotspots WHERE id IN (';
foreach ($hot_list as $i=>$tag) {
  $q .= ($i > 0 ? ",'" : "'").$tag."'";
}
$q .= ') ORDER BY image, title';
$last_img = '';
$imgs_str = '';
if ($res = mysqli_query($dbh, $q)) {
  while ($o = mysqli_fetch_object($res)) {
    $this_img = $o->image . $o->title; // reuse images so split on title as well
    if ($this_img != $last_img) { //
      if ($last_img != '') $imgs_str .= '">';
      $imgs_str .= '<h2>'.$o->title.'</h2><img alt="img missing" src="img_gen.php?image='.$o->image.'&x[]='.$o->x.'&y[]='.$o->y.'&w[]='.$o->w.'&h[]='.$o->h;
      $last_img = $this_img;
    }
    else {
      $imgs_str .= '&x[]='.$o->x.'&y[]='.$o->y.'&w[]='.$o->w.'&h[]='.$o->h;
    }
  }
  $imgs_str .= '">';
  mysqli_free_result($res);
}
$report .= $imgs_str;
echo add_consultation($report);
?>
