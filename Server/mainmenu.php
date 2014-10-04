<?php
require_once 'common.inc';
?>
<html>
  <head>
    <link href="zen.css" rel="stylesheet" type="text/css">
  </head>
  <body id="css-zen-garden" onload="setdate();">
    <div class="page-wrapper">
      <h2>Rogo Online main menu</h2>
      <p>
        <a href="https://cloud.xpertrule.com/companies/RogoOnline/Test1/d/main.php?c=RogoOnline&a=Test1&t=6e56b589153111cd3b33897616221bd9">Start a Consultation Session</a>
      </p>
      <p>
        <a href="viewconsultation.php">View and add comments to a Consultation Report</a>
      </p>
      <h3>most recent reports:</h3>
<?php
$q = 'SELECT word1, word2, date, entered FROM consultation ORDER BY entered DESC LIMIT 20';
if ($res = mysqli_query($dbh, $q)) {
  while ($o = mysqli_fetch_object($res)) {
    echo '<p><a href="viewconsultation.php?word1=' . $o->word1 . '&word2=' . $o->word2 . '&date=' . $o->date . '">ref: ' . $o->word1 . ' ' . $o->word2 . ' ' . $o->entered . '</a></p>';
  }
}
?>
    </div>
  </body>
</html>
