<?php
require_once __DIR__ . '/../db.php';

$object = new stdClass();
$id = uniqid();
$object->id = $id;

$id = DB::add($object);

$results = DB::get('stdClass', $id);

print_r($results);



