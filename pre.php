<?php
/*******************************
* pre.php
* (C) Copyright 1997-2016 Cheth Rowe Consulting. All Rights Reserved.
* developed by: Cheth Rowe Consulting: cheth@cheth.com
*------------------------------
* General purpose preprocessor
*------------------------------
* 1.2.0 2016-Aug-24 (cheth) preprocessor now a class
*******************************/

//--find myself-----
$include_path = dirname(__FILE__) . DIRECTORY_SEPARATOR; // trailing slash
echo("include_path=$include_path");

//--initialize preprocessor class-----
require_once ($include_path . 'pre.class.php');
$pre = new Preprocess_Helper;

//--define interfaces for optional features-----
require_once ($include_path . 'pre.db.interface.php');

//--load user-specified optional features-----
include_once ($include_path . 'pre.config.php');

//--define interface for optional database class-----
//require_once ($include_path . 'pre.db.implementation.php');
//require_once ($include_path . 'MySQL_PDO_Model.php');
//$db = new Database_Helper;

//--send command line params to preprocessor class---------
$pre->preprocess($argv, TRUE); // TRUE turns on verbosity

if($pre instanceof Database_Interface) {
    echo "It is! It is!";
}

//$array = class_implements($db);
//print_r($array);

// eof: pre.php
