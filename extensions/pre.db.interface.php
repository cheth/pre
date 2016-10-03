<?php
/**************************
* private/models/pre.db.interface.php
*--------------------------
* define database handling interface for "pre" preprocessor
*--------------------------
* 1.0.0 (cheth) 2016-Aug-31 convert to class
**************************/

interface Database_Interface {

    /**********************************************
    * createConnection()
    *------------------
    * Purpose: establish connection to database server
    *------------------
    * accepts associative array of database credentials
    * DB_HOSTNAME [OPTIONAL] default=localhost
    * DB_DATABASE [REQUIRED]
    * DB_USERNAME [REQUIRED]
    * DB_PASSWORD [REQUIRED]
    * DB_PORT [OPTIONAL] default=3306
    * DB_CHARSET [OPTIONAL] default=utf8 (note that utf8_mb4 is required for "real" utf8 support)
    *------------------
    * returns: <Boolean> indicating connectivity success
    ***********************************************/
    public function createConnection($credentials);
    
    /**********************************************
    * query()
    *------------------
    * Purpose: execute SELECT query
    * The sql statement is not parameterized. The implementation may make it so.
    *------------------
    * params: $sql [REQUIRED] sql statement
    *------------------
    * returns: <Boolean> TRUE if query ran successfully (even if produces no rows)
    ***********************************************/
    public function query ($sql);

    /**********************************************
    * get_row()
    *------------------
    * Purpose: get single row from query; subsequent calls, without $sql param, get next row
    * The sql statement is not parameterized. The implementation may make it so.
    *------------------
    * params: $sql [OPTIONAL] sql statement; if not specified returns next row from last query
    *------------------
    * returns: single row as object -or- FALSE if no more rows
    ***********************************************/
    public function getRow ($sql='');
}
