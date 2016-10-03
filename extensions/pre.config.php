<?php
/*******************************
* pre.config.php
* (C) Copyright 1997-2016 Cheth Rowe Consulting. All Rights Reserved.
* developed by: Cheth Rowe Consulting: cheth@cheth.com
*------------------------------
* configuration file for the preprocessor pre
*------------------------------
* 1.0.0 2016-Sep-17 (cheth) initial implementation
*******************************/

//--optional database class-----
require_once ($this->include_path . 'pre.db.implementation.php');
require_once ($this->include_path . 'MySQL_PDO_Model.php');
$this->setDatabaseHelper(new Database_Helper);

// eof: pre.config.php
