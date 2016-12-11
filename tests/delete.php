<?php
require_once __DIR__ . '/../db.php';

$object = new stdClass();

$id = DB::add($object);

$results = DB::delete($object);

print_r($results);


