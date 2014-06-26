<?php
set_time_limit (600);
include_once "common.inc";
$datelist = array(); $pricelist = array();
?>
<HTML>
<HEAD>
<LINK REL="stylesheet" HREF="accounts.css" TYPE="text/css">
</HEAD>
<BODY>
<?php
if (isset($_GET['all'])) $all_filter = ""; else $all_filter = "AND mdate > '2011/12/31'";
if (isset($_GET['stockid'])) $stock_filter = " AND ticker = '".$_GET['stockid']."'"; else $stock_filter = "";
$q = "SELECT ID, ticker, name, mdate FROM sh_stock AS s 
    LEFT JOIN (SELECT stockID, MAX(qdate) AS mdate FROM sh_close GROUP BY stockID) AS l ON s.id = l.stockID 
    WHERE flag = 0 " . $all_filter . $stock_filter . " ORDER BY mdate, RAND() LIMIT 4, 65";
echo "<br />".$q."<br />";
echo "<br />GET variables:? all=something & stockid=CEY.L<br />";
$res = mysql_query($q, $dbh);
$nj = mysql_num_rows($res); // error if no stocks input!
for ($j=0; $j<$nj; $j++) {
  $fromdate = 0;
  $v = mysql_fetch_row($res);
//  $q2 = "select max(qdate) as maxdate from sh_close where stockID=" . $v[0] . ";";
//  $res2 = mysql_query($q2, $dbh);
//  $maxdate = mysql_result($res2,0,"maxdate");
  if ($v[3] == null) { // ie no prices so startdate is earliest
    $fromdate = $firststart;
  }
  else {
    $fromdate = strtotime($v[3]);
//    echo $fromdate." ".time()." ";
    if ($fromdate < (time() - 345600)) { // ie do every few days
      $fromdate += 86400; // use the next day after the latest date in the table
    }
    else $fromdate = 0;
  }
  echo "ID=".$v[0]." ticker=".$v[1]." name=".$v[2]." mdate=".$v[3];
  $last_price = 0;
  $last_price_date = "2000-01-01";
  if ($fromdate > 0) { // ie one of the above conditions met
    $todate = time() - 86400;
    // fill in some prices
    getprice($v[1], $fromdate, $todate, $datelist, $pricelist);
    $ni = count($datelist);
    for ($i=0; $i<$ni; $i++) {
#echo $datelist[$i]."-".$v[3]."<br>\n\r";
      if ($datelist[$i] > "" && (strtotime($datelist[$i]) > strtotime($v[3]))) {
#echo "inserted it<br>\n\r";
        if (strtotime($datelist[$i]) > strtotime($last_price_date) &&
          $last_price > 0.0) {
          $last_price_date = $datelist[$i];
          $last_price = $pricelist[$i];
        }
        $q3 = "INSERT INTO sh_close SET stockID=" . $v[0] . ", qdate='" . $datelist[$i]."', price=". number_format($pricelist[$i], 4, '.', '').";";
        $res3 = mysql_query($q3, $dbh);
      }
    }
    echo "inserted ".$ni." records";
  }
  echo "<br>\n\r";
    echo "last price=".$last_price;
    if ($last_price > 0.0) {
    $q2 = "UPDATE sh_stock SET price=" . number_format($last_price, 4, '.', '') . " WHERE id=" . $v[0]. ";";
    $res2 = mysql_query($q2, $dbh);
  }
}
echo "completed checking for stock prices<br>\n\r";
// get rid of error log
@unlink("error_log");

//////////////////////////////////////////////////////////////////////////////////////////
// getprice function
//////////////////////////////////////////////////////////////////////////////////////////
function getprice($stock, $fromdate, $todate, &$datelist, &$pricelist) {
// fromdate and todate are timestamps
  global $tablemarker1, $tablemarker2, $tablenumber, $urlRoot, $swaplist;

  $datelist = ""; $pricelist = ""; $k=0;
  $fdt = $fromdate; $tdt = $fdt + 74*86400;
  if ($tdt > $todate) $tdt = $todate;
  while ($fdt < $tdt) {
    $dtarray = getdate($fdt);
    $df = $dtarray["mday"]; $mf = $dtarray["mon"]-1; $yf = $dtarray["year"];
    $dtarray = getdate($tdt);
    $dt = $dtarray["mday"]; $mt = $dtarray["mon"]-1; $yt = $dtarray["year"];
    $urlDate = "b=".$df."&a=".$mf."&c=".$yf."&e=".$dt."&d=".$mt."&f=".$yt."&g=d&s=";
    $fhandle = fopen ($urlRoot . $urlDate . $stock, "r");
    echo "<p>".$urlRoot . $urlDate . $stock."</p>";
    if ($fhandle) {
      $contents = '';
      while (!feof($fhandle)) {
        $contents .= fread ($fhandle, 8192);
      }
      fclose ($fhandle);
    }
    $contents = explode ($tablemarker1, $contents);
    $contents = $contents[$tablenumber];
    $contents = explode ($tablemarker2, $contents);
    $contents = $contents[0];
    $contents = str_replace("\"", "", $contents);
    foreach ($swaplist as $key => $value) $contents = str_replace($key, $value, $contents);
    $contents = explode ("<tr>", $contents);
    $ni = count($contents);
    for ($i=0; $i<$ni; $i++) {
      $thisline = explode("<td>", $contents[$i]);
//      $dtarray = getdate(strtotime($thisline[1]));
      $thisprice = str_replace(",", "", $thisline[7]);
      if ($thisprice > 0.0) {
        $datelist[$k] = sqldate(strtotime($thisline[1]));
//        $datelist[$k] = $dtarray["year"]."-".$dtarray["mon"]."-".$dtarray["mday"];
        $pricelist[$k] = $thisprice;
        $k++;
      }
    }
        $fdt = $tdt + 86400; //next day
        $tdt = $fdt + 74*86400; //74 days, ie approx max records per page
    if ($tdt > $todate) $tdt = $todate;
    echo $stock." ".$ni." ";
  }
}
include_once "menu.php";
?>
</BODY>
</HTML>
