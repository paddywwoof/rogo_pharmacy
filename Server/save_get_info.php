<?php
require_once 'common.inc';
if (isset($_GET['word1']) && isset($_GET['word2']) && isset($_GET['date'])) {
  $word1 = $_GET['word1'];
  $word2 = $_GET['word2'];
  $date = $_GET['date'];
  preg_replace('[^a-z]', '', $word1);
  preg_replace('[^a-z]', '', $word2);
  preg_replace('[^0-9\-]', '', $date);
  $q = "SELECT info FROM consultation WHERE  word1 = '" . $word1 . "' AND word2 = '" . $word2 . "' AND date = '" . $date . "'";
  if ($res = mysqli_query($dbh, $q)) {
    $o = mysqli_fetch_object($res);
    $info1 = $o->info;
    if (isset($_GET['info1'])) { // putting info into database
      $info1 = $_GET['info1'];
      preg_replace('[^n-zA-Z0-9!\,\.\(\)\?\+\-]', ' ', $info1);
      if (strlen($info1) > 0) {
        $q1 = "UPDATE consultation SET info = '" . $info1 ."' WHERE word1 = '" . $word1 . "' AND word2 = '" . $word2 . "' AND date = '" . $date . "'";
        $res2 = mysqli_query($dbh, $q1);
        echo $info1;
      }
    }
    else {
      echo $info1;
    }
  }
}
?>
