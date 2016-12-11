<?php
require_once __DIR__ . '/../db.php';

$object = new stdClass();
$id = uniqid();
$object->id = $id;
$object->uid = $id;

$id = DB::add($object);

$stack = DB::getStack('stdClass', array('uid' => $id));

print_r($stack);



