<?php
include_once 'common.inc';
echo '++++';
if (!isset($_POST['report'])) {
  echo json_encode(array('error'=>-2));
}
else {
  echo 'here';
  $q = 'LOCK TABLES consultation WRITE, words READ';
  $res = mysqli_query($dbh, $q);
  echo 'there';
  $todo = true;
  $r_val = json_encode(array('error'=>-3));
  $date = date('Y-m-d');
  for ($i = 0; ($i < 100) && $todo; $i++) {
    echo $i;
    $wordpair = wordgen(); //defined in common.inc
    $q = "SELECT id FROM consultation WHERE date = '".$date."' AND word1 = '".$wordpair[0]."' AND word2 = '".$wordpair[1]."'";
    $res = mysqli_query($dbh, $q);
    if (mysqli_num_rows($res) == 0) {
      $q = "INSERT INTO consultation (word1, word2, date, report) VALUES
          ('".$wordpair[0]."', '".$wordpair[1]."', '".$date."', '".$_POST['report']."')";
      $r_val = json_encode(array('word1'=>$wordpair[0], 'word2'=>$wordpair[1], 'date'=>$date))
      $todo = false
    }
  }
  $q = 'UNLOCK TABLES';
  $res = mysqli_query($dbh, $q);
  echo $r_val;
}
?>
