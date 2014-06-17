<?php
session_start();
$hn = 'localhost';
$un = 'eldwick_rogo';
$pw = 'RogoOn1ine';
$db = 'eldwick_rogo';
//////////////////////////////////////  connect to db ==> dbase handle $dbh
$dbh = mysqli_connect($hn, $un, $pw, $db, '3306');
if (!$dbh) {
  echo 'could not connect to MySQL: '.mysqli_connect_errno();
}

/////////////// general purpose functions
function wordgen() {
  global $dbh;
  $r_val = array();
  $q = 'SELECT word FROM words ORDER BY RAND() LIMIT 1,2';
  if ($link = mysqli_query($dbh, $q)) {
    while ($obj = mysqli_fetch_object($link)) {
        $r_val[] = $obj->word;
    }
    mysqli_free_result($link);
    return $r_val;
  }
  return '((('.mysqli_errno($link).'|'.mysqli_error($link).')))';
}

///////////////
function add_consultation($report) {
  global $dbh;
  $q = 'LOCK TABLES consultation WRITE, words READ';
  $res = mysqli_query($dbh, $q);
  $todo = true;
  $r_val = json_encode(array('error'=>-3));
  $date = date('Y-m-d');
  $esc_report = mysqli_real_escape_string($dbh, $report);
  for ($i = 0; ($i < 100) && $todo; $i++) { //will have 100 attempts to find new key combo
    $wordpair = wordgen();
    $words = $wordpair[0].$wordpair[1];
    $q = "SELECT id FROM consultation WHERE date = '".$date."' AND id = '".$words."'";
    $res = mysqli_query($dbh, $q);
    if (mysqli_num_rows($res) == 0) { //this key doesnt already exist so insert it
      $q = "INSERT INTO consultation (id, date, report) VALUES
          ('".$words."', '".$date."', '".$esc_report."')";
      if (!$res = mysqli_query($dbh, $q))
        echo mysqli_error($res);
      $r_val = json_encode(array('word1'=>$wordpair[0], 'word2'=>$wordpair[1], 'date'=>$date));
      $_SESSION['word1'] = $wordpair[0];
      $_SESSION['word2'] = $wordpair[1];
      $_SESSION['date'] = $date;
      $todo = false;
    }
    //mysqli_free_result($res);
  } 
  $q = 'UNLOCK TABLES';
  $res = mysqli_query($dbh, $q);
  return $r_val;
}
?>
