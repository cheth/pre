<?php
/*******************************
* pre.php
* (c) Copyright 1997-2016 Cheth Rowe Consulting. All Rights Reserved.
* developed by: Cheth Rowe Consulting: cheth@cheth.com
*------------------------------
* General purpose preprocessor
*------------------------------
* 1.1.0 2004-Jun-11 (cheth) initial implementation of php version (was Perl).
* 1.1.1 2004-Jul-12 (cheth) set Verbose argument in preprocess call
* 1.2.0 2016-Aug-24 (cheth) preprocessor now a class
*******************************/

//--find myself-----
$include_path = dirname(__FILE__) . DIRECTORY_SEPARATOR; // trailing slash

//--initialize preprocessor class-----
require_once ($include_path . 'pre.class.php');
$pre = new Preprocess_Helper;

//--initialize database class-----
require_once ($include_path . 'pre.db.interface.php');
require_once ($include_path . 'pre.db.implementation.php');
require_once ($include_path . 'MySQL_PDO_Model.php');
$db = new Database_Helper;

//--send command line params to preprocessor class---------
$pre->preprocess($argv, TRUE); // TRUE turns on verbosity

// eof: pre.php
