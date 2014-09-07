<?php
require_once __DIR__ . '/../db.php';

$object = new stdClass();

$result = DB::add($object);

print_r($result . PHP_EOL);

//var_dump($result . PHP_EOL);


