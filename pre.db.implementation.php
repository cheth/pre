<?php
/**************************
* private/models/pre.db.implementation.php
*--------------------------
* database implementation using MySQL_PDO_Helper class
*--------------------------
* 1.0.0 (cheth) 2016-Aug-31 convert to class
* 1.0.1 (cheth) 2016-Sep-17 minor documentation changes
**************************/

class Database_Helper implements Database_Interface {

    /**
    * Private vars
    */
    private $db;        // link to PDO class

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

        //--db charset optional-----------
        if (!isset($KIC['database']['charset'])) {
            $KIC['database']['charset'] = 'utf8';
        }

        //--initialize database access-----------
        $this->db = new MySQL_PDO_Helper;

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
