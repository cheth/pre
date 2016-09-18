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

//--define interfaces for optional features-----
$pre->requireIfExists($include_path . 'pre.db.interface.php'); // database interface
$pre->requireIfExists($include_path . 'pre.md.interface.php'); // Markdown interface

//--load user-specified optional features-----
$pre->requireIfExists($include_path . 'pre.config.php');

//--send command line params to preprocessor class---------
$pre->preprocess($argv, TRUE); // TRUE turns on verbosity

// eof: pre.php
