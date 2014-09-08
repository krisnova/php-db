php-db
==

An object oriented, no dependency, lightweight databasing engine for php.

Inspiration for this repository came from a need to store a small* amount of data ( <10,000 records) on a Raspberry Pi. The thesis for this repostiroy is simple : To provide a lightweight, and simple way of storing open-ended data with PHP. With php-db, there are no dependencies other than PHP V 5.0+. With php-db, a user can simply and quickly store OO driven data, for small-time applications.

`Usuage : require_once '/path/to/db.php';`

#####add
 - Primary method to add an object to php-db
 - Checks if the object exists, and will update accordingly
 - Designed to be open ended, so custom objects can be added
 - php-db only recognizes public properties
 
````php
$object = new stdClass();
$object->key = 'value';
$id = DB::add($object);
 ````

#####update
 - Primary method to update an object in php-db
 - Checks if the object exists, will fail if it cannot be found
 - WARNING : Currently does no delta detection, and will replace every time
 
````php
$object = new stdClass();
$id = uniqid();
$object->_id = $id;
$result = DB::add($object);
$object->update = 'update';
$result = DB::update($object);
 ```` 

#####get
 - Primary method to get data from php-db
 - Will return FALSE if nothing is found
 - The type is found by the object's class name, if no type was set
 
````php
$object = new stdClass();
$id = uniqid();
$object->_id = $id;
$id = DB::add($object);
$results = DB::get('stdClass', $id);
 ````

#####delete
 - Primary method to delete something from php-db
 - Currently does not support type and id, so the user needs to have the object instantiated before calling delete
 
````php
$object = new stdClass();
$id = DB::add($object);
$results = DB::delete($object);
````

#####doesExist
 - Simple method to check if an object exists in php-db
 
````php
$object = new stdClass();
$id = DB::add($object);
$exists = DB::doesExist($object);
````

#####getStack
 - Primary method to get a stack (array) of objects that match any specified requirements
 - This is the best way to query the data, but is far from complete
 - Requirements is an associative array indexed my object property name, and our attempted value
 
````php
$object = new stdClass();
$id = uniqid();
$object->_id = $id;
$object->uid = $id;
$id = DB::add($object);
$stack = DB::getStack('stdClass', array('uid' => $id));
````

#####build
 - This method will initialize php-db and is called require to bootstrap the static class
 - WARNING : The user should never have to call this method

Verbage
==

#####object
 - When referring to an object, php-db can accept any object type.
 - There are special properties called `_type` and `_id` that php-db uses, feel free to set these to whatever you want
 - If `_type` is empty or not set, php-db will use the class name of the object for type
 - If `_id` is empty or not set, php-db will generate a unique id based on unix time
 
#####db.json
 - There is a `db.json` file that comes with php-db out of the box. This is considered the default config file, and will fallback on this
 - The user can optionally include a `db.json` file in the directory that calls php-db (php-db will let you know if it has found one)
 - *debug* : **bool** Used to control debug outputting in the command line
 - *throwException* : **bool** Used to control if php-db will throw exceptions or just die
 - *delimiter* : **string** Delimiter to be used in the text files that store data
 - *allowBlank* : **bool** Should php-db allow empty or blank objects to be saved
 - *dbPath* : **string** The absolute or relative path of the root database directory
  * "db" Would nest the database in php-db's directory, in a folder called "db"
  * "/db" Would nest the database in the root filesystem, or absolute path "/db"
  * "./db" Would nest the database in the relative path of the calling script. Would create a folder called "db" in the same directory as the script that is using php-db
 
#####pipline

Here is the current list of "wants" for the project. Anyone is welcome to help out with these.

 - `delete` by object type and id
 - `doesExist` by object type and id
 - `update` needs to be clever, and modify accordingly. Currently is not using delta detection. This is going to be fun to write.
 - `getStack` query operators need to be defined, and built
 
 

