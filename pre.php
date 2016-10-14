<?php
/*******************************
* pre.php
* (C) Copyright 1997-2016 Cheth Rowe Consulting. All Rights Reserved.
* developed by: Cheth Rowe Consulting: cheth@cheth.com
*------------------------------
* General purpose preprocessor
*------------------------------
* 1.2.1 2016-Sep-17 (cheth) optional features defined as interfaces and loaded via strategy pattern
*******************************/

//--find myself-----
$include_path = dirname(__FILE__) . DIRECTORY_SEPARATOR; // trailing slash

//--initialize preprocessor class-----
require_once ($include_path . 'pre.class.php');
$pre = new Preprocess_Helper;

//--OPTIONAL: load user-specified optional features-----
$extensions_path = $include_path . 'extensions' . DIRECTORY_SEPARATOR; // trailing slash
$extensions_config = $extensions_path . 'pre.config.php';
if (file_exists($extensions_config)) {
    include_once($extensions_config);
}    

//--send command line params to preprocessor class---------
$pre->preprocess($argv, TRUE); // TRUE turns on verbosity

// eof: pre.php
