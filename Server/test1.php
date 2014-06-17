<?php
echo 'you sent '.$_SERVER['REQUEST_URI'].'<br />';
echo 'word1 '.(isset($_GET['word1']) ? $_GET['word1'] : 'not set').'<br />';
echo 'word2 '.(isset($_GET['word2']) ? $_GET['word2'] : 'not set').'<br />';
echo 'date '.(isset($_GET['date']) ? $_GET['date'] : 'not set').'<br />';
?>
