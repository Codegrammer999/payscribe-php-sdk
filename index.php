<?php
require __DIR__."/vendor/autoload.php";



$payscribe = Payscribe::createFromEnv();
$result = $payscribe->account();

var_dump($result);
