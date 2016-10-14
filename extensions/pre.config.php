<?php
/*******************************
* pre.config.php
* (C) Copyright 1997-2016 Cheth Rowe Consulting. All Rights Reserved.
* developed by: Cheth Rowe Consulting: cheth@cheth.com
*------------------------------
* configuration file for the preprocessor pre
*------------------------------
* 1.0.1 2016-Oct-04 (cheth) move to extensions directory
*******************************/

//--define interfaces for optional features-----
$pre->requireIfExists($extensions_path . 'pre.db.interface.php'); // database interface
$pre->requireIfExists($extensions_path . 'pre.md.interface.php'); // Markdown interface

//--optional database class-----
$pre->requireIfExists($extensions_path . 'pre.db.implementation.php'); // between interface and PDO helper
$pre->requireIfExists($extensions_path . 'MySQL_PDO_Model.php'); // PDO helper
$pre->setDatabaseHelper(new Database_Helper);

//--optional Markdown class-----
$pre->requireIfExists($extensions_path . 'pre.prettifierTEST.php');
$pre->setMarkdownHelper(new Prettification_Helper);

// eof: pre.config.php
