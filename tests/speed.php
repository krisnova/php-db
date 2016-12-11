<?php
require_once __DIR__ . '/../db.php';

$limit = 1000;
if(isset($argv[1])){
   $limit = $argv[1];
}
$total = $limit;
$start = time();
while($limit > 0){
    $object = new stdClass();
    $id = DB::add($object);
    $limit--;
}
$stop = time();
$diff = $stop - $start;
print_r('Took '.$diff.' sec to add '.$total.' objects to php-db'.PHP_EOL);

$start = time();
$object = DB::get('stdClass', $id);
$stop = time();
$diff = $stop - $start;
print_r('Took '.$diff.' sec to get last object by id'.PHP_EOL);

$start = time();
$stack = DB::getStack('stdClass', array('_id' => $id));
$stop = time();
$object = array_pop($stack);
$diff = $stop - $start;
print_r('Took '.$diff.' sec to get last object by id with getStack'.PHP_EOL);

$start = time();
$stack = DB::getStack('stdClass');
$stop = time();
$diff = $stop - $start;
print_r('Took '.$diff.' sec to get a full stack of '.count($stack).' objects'.PHP_EOL);






