<?php
require_once __DIR__ . '/../db.php';

$object = new stdClass();
$id = uniqid();
$object->id = $id;

$result = DB::add($object);

print_r($result . PHP_EOL);

$result = DB::add($object);

print_r($result . PHP_EOL);

$result = DB::update($object);

print_r($result . PHP_EOL);

//var_dump($result . PHP_EOL);


