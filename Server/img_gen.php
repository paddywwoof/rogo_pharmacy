<?php
$imgstr = $_GET['image'];
$x = $_GET['x'];
$y = $_GET['y'];
$w = $_GET['w'];
$h = $_GET['h'];
$im = @imagecreatefromjpeg('images/'.$imgstr.'.jpg');
if ($im) {
  $red = imagecolorallocate($im, 255, 0, 0);
  if (!is_array($x)){ //only one rectangle
    imagerectangle($im, $x, $y, $x + $w, $y + $h, $red);
  }
  else {  
    foreach ($x as $i=>$val) {
      imagerectangle($im, $x[$i], $y[$i], $x[$i] + $w[$i], $y[$i] + $h[$i], $red);
    }
  }
  header('Content-Type: image/jpeg');
  imagejpeg($im);
  imagedestroy($im);
}
?>

