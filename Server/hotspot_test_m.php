<?php
require_once 'common.inc';
$imgstr = 'male1';
$im = @imagecreatefromjpeg('images/'.$imgstr.'.jpg');
$red = imagecolorallocate($im, 255, 0, 0);
$q = "SELECT image, title, x, y, w, h FROM hotspots WHERE id LIKE 'HS%'";
if ($res = mysqli_query($dbh, $q)) {
  while ($o = mysqli_fetch_object($res)) {
    imagerectangle($im, $o->x, $o->y, $o->x + $o->w, $o->y + $o->h, $red);
  }
}
header('Content-Type: image/jpeg');
imagejpeg($im);
imagedestroy($im);
?>

