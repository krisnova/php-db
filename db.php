<?php

/**
 * php-db
 *
 * An object oriented, 0 dependency, lightweight databasing engine for php.
 *
 * This is the main static class used to interact with our database(s)
 *
 * @todo Abstract all file operations to individual methods
 * @todo Lock all file operations, so we can rely on caching
 *      
 */
class DB {

    /**
     * Another magic place in memory for all the things
     *
     * @var array $cache
     */
    protected static $cache;

    /**
     * Bool that determines if we have gone through init
     *
     * @var bool $built
     */
    protected static $built = FALSE;

    /**
     * A magic place in memory for php-db configuration values
     *
     * @var array
     */
    protected static $config = FALSE;

    /**
     * build
     *
     * Will build php-db if needed
     * Sets up config
     * If a db.json is found in the calling script's directory, will attempt to parse
     * Otherwise defaults to the db.json in php-db's directory
     */
    static public function build() {
        if (self::$built == TRUE) {
            return;
        }
        $lastFile = array_pop(debug_backtrace());
        $callingDirectory = dirname($lastFile['file']);
        $jsonPath = $callingDirectory . '/db.json';
        if (file_exists($jsonPath)) {
            $parsedJson = json_decode(file_get_contents($jsonPath), TRUE);
            print_r('Building with custom config ' . $jsonPath . PHP_EOL);
        }
        else {
            $jsonPath = __DIR__ . '/db.json';
            if (!file_exists($jsonPath)) {
                self::fail('Cannot locate default db.json file in ' . __DIR__);
            }
            $parsedJson = json_decode(file_get_contents($jsonPath), TRUE);
        }
        if (empty($parsedJson)) {
            self::fail('Invald Json found in ' . $jsonPath);
        }
        self::$config = $parsedJson;
        self::finalizeDbPath($callingDirectory);
    }

    /**
     * get
     *
     * The main method to get data from the DB
     *
     * @param string $type            
     * @param string $id            
     * @return stack || object
     */
    static public function get($type, $id = FALSE) {
        self::debug('*GET*');
        // Full collection
        if (!$id) {
            return self::_getStack($type);
        }
        // Id
        return self::_get($type, $id);
    }

    /**
     * add
     *
     * The main method to add data to the DB
     * Will upsert if the data does not exist
     * All objects need 'type' and 'id' public properties
     *
     * @param object $object            
     * @return string $id
     */
    static public function add($object) {
        self::debug('*ADD*');
        $object = self::checkRequirments($object);
        return self::_add($object);
    }

    /**
     * update
     *
     * The main method to update an object in the DB
     * Will insert it, if nothing exists
     * All objects need 'type' and 'id' public properties
     *
     * @param object $object            
     * @return string $id
     */
    static public function update($object) {
        self::debug('*UPDATE*');
        $object = self::checkRequirments($object);
        return self::_update($object);
    }

    /**
     * delete
     *
     * The main method to delete data from the DB
     * If the data does not exist, does nothing
     * All objects need 'type' and 'id' public properties
     *
     * @param object $object            
     * @return bool $success
     */
    static public function delete($object) {
        self::debug('*DELETE*');
        $object = self::checkRequirments($object);
        return self::_delete($object);
    }

    /**
     * doesExist
     *
     * The main method to check and see if an entry exists in the DB
     * All objects need 'type' and 'id' public properties
     *
     * @param object $object            
     * @return bool $success
     */
    static public function doesExist($object) {
        self::debug('*DOESEXIST*');
        $object = self::checkRequirments($object);
        return self::_doesExist($object);
    }

    /**
     * getStack
     *
     * Returns all objects for this type, with optional requirements
     *
     * @param string $type            
     * @param array $requirements            
     * @return array $stack array of objects that match requirements
     */
    static public function getStack($type, $requirements = array()) {
        self::debug('*GETSTACK*');
        return self::_getStack($type, $requirements);
    }

    /**
     * _get
     *
     * Will attempt to get an object from the DB
     *
     * @param string $object            
     * @param string $id            
     * @return object $object
     */
    static private function _get($type, $id) {
        $objects = self::getStack($type);
        if (empty($objects)) {
            return FALSE;
        }
        if (isset($objects[$id])) {
            return self::convertToOriginalObject($objects[$id]);
        }
        return FALSE;
    }

    protected static $testers;

    /**
     * _add
     *
     * Will attempt to add an object to the database
     *
     * @param object $object            
     * @return string $id
     */
    static private function _add($object) {
        $object->_className = get_class($object);
        $exists = self::doesExist($object);
        if ($exists) {
            return self::update($object);
        }
        $fileName = self::getFileNameByObject($object);
        if (!file_exists($fileName)) {
            @mkdir(dirname($fileName), '0755', TRUE);
            file_put_contents($fileName, NULL);
        }
        $data = json_encode($object);
        $data = $data . self::getConfig('delimiter') . PHP_EOL;
        if (file_put_contents($fileName, $data, FILE_APPEND)) {
            return $object->_id;
        }
        self::fail('Can not add ' . $object->_id);
    }

    /**
     * _update
     *
     * Updates an object in the DB
     *
     * @param object $object            
     * @return string $id
     */
    static private function _update($object) {
        $type = $object->_type;
        $id = $object->_id;
        // Load cache if we havent already
        if (!isset(self::$cache[$type])) {
            self::$cache[$type] = self::getStack($type);
        }
        // Can't update, nothing found
        if(!isset(self::$cache[$type][$id])){
            return;
        }
        
        $objects = self::$cache[$type];
        $fileName = self::getFileNameByObject($object);
        $id = $object->_id;
        $objects[$id] = $object;
        $stringToWrite = '';
        foreach ($objects as $object) {
            $stringToWrite .= json_encode($object) . self::getConfig('delimiter') . PHP_EOL;
        }
        if (file_put_contents($fileName, $stringToWrite)) {
            return $id;
        }
        self::fail('Failed while updating ' . $id);
    }

    /**
     * _delete
     *
     * Deletes an object from the DB
     *
     * @param object $object            
     * @return bool $success
     */
    static private function _delete($object) {
        $objects = self::getStack($object->_type);
        $fileName = self::getFileNameByObject($object);
        if (isset($objects[$object->_id])) {
            unset($objects[$object->_id]);
        }
        else {
            // self::fail('Can not find object id '.$object->_id);
        }
        $stringToWrite = '';
        foreach ($objects as $object) {
            $stringToWrite .= json_encode($object) . self::getConfig('delimiter') . PHP_EOL;
        }
        if (file_put_contents($fileName, $stringToWrite)) {
            return TRUE;
        }
        self::fail('Failure on delete, unable to write file');
    }

    /**
     * _doesExist
     *
     * Returns a bool if the object exists
     * Heavy caching for speed
     *
     * @param object $object            
     * @return bool $exists
     */
    static private function _doesExist($object) {
        $id = $object->_id;
        $type = $object->_type;
        if (!isset(self::$cache[$type])) {
            self::$cache[$type] = self::getStack($type);
        }
        if(isset(self::$cache[$type][$id])){
            return TRUE;
        }
        return FALSE;
    }

    /**
     * getStack
     *
     * Will return an array of all the objects in the DB
     *
     * @param object $type            
     * @return array $stack An array of objects
     */
    static private function _getStack($type, $requirements = array()) {
        $fileName = self::$config['dbPath'] . '/' . $type . '/dbFile';
        if (!file_exists($fileName)) {
            @mkdir(dirname($fileName), '0755', TRUE);
            file_put_contents($fileName, NULL);
        }
        $contents = file_get_contents($fileName);
        if (empty($contents)) {
            return array();
        }
        $array = explode(self::getConfig('delimiter') . PHP_EOL, $contents);
        $objects = array();
        foreach ($array as $key => $line) {
            if (empty($line)) {
                unset($array[$key]);
                continue;
            }
            $object = json_decode($line);
            if (empty($object)) {
                continue;
            }
            $id = $object->_id;
            $convertedObject = self::convertToOriginalObject($object);
            if (self::assertRequirementsObject($convertedObject, $requirements)) {
                $objects[$id] = $convertedObject;
            }
        }
        return $objects;
    }

    /**
     * Will check if an object matches requirements
     *
     * @param object $object            
     * @param array $requirements            
     * @return bool $match
     */
    static private function assertRequirementsObject($object, $requirements) {
        if (empty($requirements)) {
            // No requirements, everybody wins!
            return TRUE;
        }
        // Innocent until proven guilty
        $match = TRUE;
        foreach ($requirements as $key => $value) {
            if (isset($object->$key) && $object->$key === $value) {
                $match = TRUE;
            }
            else {
                $match = FALSE;
            }
        }
        return $match;
    }

    /**
     * convertToOriginalObject
     *
     * Every time an object is added to the DB, the _className property is populated
     * This will return a new object of that type
     *
     * @param object $object            
     * @return object $originalObject
     */
    static private function convertToOriginalObject($object) {
        $objectType = $object->_className;
        if (get_class($object) == $objectType) {
            return $object;
        }
        $newObject = new $objectType();
        $vars = get_object_vars($object);
        foreach ($vars as $property => $value) {
            $newObject->$property = $value;
        }
        return $newObject;
    }

    /**
     * checkRequirments
     *
     * Validates objects and returns all the things required
     *
     * @todo This method is called extremely often, switch statement here
     *      
     * @param object $object            
     * @return object $object
     */
    static private function checkRequirments($object) {
        if (!is_object($object)) {
            self::fail('Must pass an object');
        }
        if (!self::getConfig('allowBlank') && empty((array)$object)) {
            self::fail('Empty object, and allowBlank is disabled');
        }
        if (!isset($object->_id) || empty($object->_id)) {
            $object->_id = self::getId();
        }
        if (!isset($object->_type) || empty($object->_type)) {
            $object->_type = self::getType($object);
        }
        return $object;
    }

    /**
     * getType
     *
     * Used to get the type of the object
     *
     * @param object $object            
     * @return string $type
     */
    static private function getType($object) {
        return get_class($object);
    }

    /**
     * getId
     *
     * Logic to get a unique id for objects that do not have one
     *
     * @param string $prefix            
     * @return string $id
     */
    static private function getId($prefix = NULL) {
        return uniqid($prefix);
    }

    /**
     * getFileNameByObject
     *
     * @param object $object            
     * @return string $path
     */
    static private function getFileNameByObject($object) {
        return self::$config['dbPath'] . '/' . $object->_type . '/dbFile';
    }

    /**
     * debug
     *
     * Simple way to debug a message
     *
     * @param string $message            
     */
    static private function debug($message) {
        if (self::getConfig('debug')) {
            print_r($message);
            print_r(PHP_EOL);
        }
    }

    /**
     * fail
     *
     * Called if something goes wrong
     * Uses config to determine what we do
     *
     * @param string $message            
     */
    static private function fail($message) {
        if (self::getConfig('throwException')) {
            throw new DbException($message);
        }
        die($message . PHP_EOL);
    }

    /**
     * Once this method is ran, 'dbPath' should be a working file pointer
     *
     * 'db' --- Will point to the repository path
     * '/db' -- Will point to "/db" in the file system
     * './db' - Will point to the relative path of the calling script
     *
     * @param string $callingDirectory
     *            dirname of the file that called php-db
     * @return string
     */
    static private function finalizeDbPath($callingDirectory) {
        $dbPath = self::getConfig('dbPath');
        if (empty($dbPath)) {
            $dbPath = './db';
        }
        // Absolute Path
        if ($dbPath[0] == '/') {
            return self::$config['dbPath'] = $dbPath;
        }
        // Relative to calling file Path
        if ($dbPath[0] == '.') {
            $dbPath = str_replace('./', '/', $dbPath);
            return self::$config['dbPath'] = $callingDirectory . $dbPath;
        }
        // Relative to php-db file Path
        return self::$config['dbPath'] = __DIR__ . '/' . $dbPath;
    }

    /**
     * getConfig
     *
     * Attempts to get a configuration property
     *
     * @param string $property            
     * @return unknown|boolean
     */
    static private function getConfig($property) {
        if (isset(self::$config[$property])) {
            return self::$config[$property];
        }
        return FALSE;
    }
}

/**
 * DbException
 *
 * Custom Exception class for DB
 *
 * <Kris Childress>
 *
 * Sep 7, 2014
 */
class DbException extends Exception {
}

/*
 * Bootstrap
 */
DB::build();
