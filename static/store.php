<?php
$entityBody = file_get_contents('php://input');

$file = "../ebg-postkarten/already_processed.txt";
file_put_contents($file, $entityBody . PHP_EOL, FILE_APPEND | LOCK_EX);
