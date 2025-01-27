<?php
$file = "../ebg-postkarten/ebg-stage-postkarten.txt";
$content = file_get_contents($file);
header('Content-Type: application/text');
echo $content;
