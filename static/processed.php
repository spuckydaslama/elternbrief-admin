<?php
$file = "../ebg-postkarten/already_processed.txt";
$content = file_get_contents($file);
header('Content-Type: application/text');
echo $content;
