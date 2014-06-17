<html><head></head><body>
<?php
$mid = array('some', 'other', 'the', 'many', 'most', 'all', 'no');
$adj = array('clever', 'simple', 'stupid', 'red', 'orange', 'free', 'honest', 'clean', 'dirty', 'dull', 'fast', 'pointed');
$noun = array('spiders', 'cats', 'rabbits', 'dogs', 'goldfish', 'monkeys', 'lions', 'zebras', 'unicorns', 'ants', 'snakes', 'people');
$adv = array('slowly', 'quickly', 'easily', 'sadly', 'happily', 'cheerfully', 'frequently', 'often', 'never', 'hopefully');
$verb = array('clean', 'eat', 'bite', 'chew', 'lick', 'scrape', 'attack', 'fight', 'escape');
$i = rand(0, count($mid)-1);
$j = rand(0, count($adj)-1);
$k = rand(0, count($noun)-1);
$l = rand(0, count($adv)-1);
$m = rand(0, count($verb)-1);
$n = rand(0, count($adj)-1);
$o = rand(0, count($noun)-1);
print $adj[$j].' '.$noun[$k].' '.$adv[$l].' '.$verb[$m].' '.$mid[$i].' '.$noun[$o];
?></body></html>
