<?php
/**************************
* private/models/pre.db.interface.php
*--------------------------
* database handling for KeepItCurrent preprocessor
*--------------------------
* 1.0.0 (cheth) 2016-Aug-31 convert to class
**************************/

class Database_Helper implements Database_Interface {

    /**
    * Private vars
    */
    private $db;        // PHP-assigned link for MySQL access

    /**
    * Public vars
    */
    public $error_found = FALSE;
    public $error_test = '';

    /**********************************************
    * createConnection()
    *------------------
    * Purpose: establish connection to database server
    *------------------
    * requires DB_DATABASE, DB_USERNAME, and DB_PASSWORD to be provided by associative array param
    * allows  DB_HOST, DB_PORT, and DB_CHARSET to also be so provided
    *------------------
    * returns: <Boolean> indicating connectivity success
    ***********************************************/
    public function createConnection($credentials) {

        //--credentials required-----------
        if(!$credentials) {
            $this->error_found = TRUE;
            $this->error_text = 'missing database credentials';
            return (FALSE);
        }
        
        //--credentials must be array-----------
        if(!is_array($credentials)) {
            $this->error_found = TRUE;
            $this->error_text = 'credentials must be array';
            return (FALSE);
        }

        //--convert credentials to KIC namespace-----------
        global $KIC;
        if($credentials) foreach($credentials as $key => $val) {
            $key = str_replace('DB_','',$key);
            $key = strtolower($key);
            $KIC['database'][$key] = $val;
        }
        //print_r($KIC);

        //--expects database credentials to have been externally defined-------
        //if(!defined('DB_HOST') 
        //|| !defined('DB_DATABASE') 
        //|| !defined('DB_USERNAME') 
        //|| !defined('DB_PASSWORD') ) {
        //    $this->error_found = TRUE;
        //    $this->error_text = 'missing database credentials';
        //    return (FALSE);
        //}

        //--db charset optional-----------
        if (!isset($KIC['database']['charset'])) {
            $KIC['database']['charset'] = 'utf8';
        }

        //--initialize database access-----------
        //echo("trying\n");
        $this->db = new MySQL_PDO_Helper;
        //echo("tried\n");

        //--report success-----------
        return (TRUE);
    }
    
    /**********************************************
    * query()
    *------------------
    * Purpose: run query
    *------------------
    * params: $sql [REQUIRED] sql statement
    *------------------
    * returns: <Boolean> TRUE if query ran
    ***********************************************/
    public function query ($sql) {
        
        //--initialize database if necessary----------
        if (!$this->db) {
            $this->createConnection();
        }

        //--prepare PDO----------

        //--execute query----------
        $result = $this->db->query($sql);

        //--resturn requested row----------
        return ($result);
    }

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
    public function getRow ($sql='') {
        
        //--initialize database if necessary----------
        if (!$this->db) {
            $this->createConnection();
        }

        //--prepare PDO----------

        //--execute query----------
        $row = $this->db->get_row($sql);

        //--resturn requested row----------
        return ($row);
    }

}
