<?php
require_once 'common.inc';
if (isset($_GET['word1']) && isset($_GET['word2']) && isset($_GET['date'])) {
  $word1 = $_GET['word1'];
  $word2 = $_GET['word2'];
  $date = $_GET['date'];
}
else {
  $word1 = 'whom';
  $word2 = 'fear';
  $date = '2014-05-14';
}
if (isset($_GET['nb'])) $nb = false;
else $nb = true;
preg_replace('[^a-z]', '', $word1);
preg_replace('[^a-z]', '', $word2);
preg_replace('[^0-9\-]', '', $date);
//make head and tail of report page TODO style info
if (!$nb) {
  $onrun = ' onload="toggle(\'endDiv\')"';
}
else {
  $onrun = '';
}
$head = '
<html>
  <head>
    <link href="zen.css" rel="stylesheet" type="text/css">
  </head>
  <body id="css-zen-garden"' . $onrun . '>';
  
if ($nb) {
  $head .= '<strong>NB. Write down</strong> this key consisting of two random words and the date (year-month-day):<br />
<span style="font-family:arial;color:red;font-size:20px;">' . $word1 . ' ' . $word2 . ' ' . $date . '</span><br />
this will allow you, or a medical professional, to view the anonymous information later.<br />';
}
$head .= '<h2>Rogo Online - Report:</h2>';
$tail = '</body></html>';
$q = "SELECT report FROM consultation WHERE  word1 = '" . $word1 . "' AND word2 = '" . $word2 . "' AND date = '" . $date . "'";
if ($res = mysqli_query($dbh, $q)) {
  $o = mysqli_fetch_object($res);
  echo $head . $o->report . $tail;
}
?>
