<?php
/****************************************
* filename: MySQL_PDO_model.php
* purpose:  Class for PDO/MySQL access
****************************************
* 1.0.1 (cheth) 2016-Apr-28 adapt from MySQL_Model
* 1.0.2 (cheth) 2017-Mar-13 correct minor bugs
****************************************/

class MySQL_PDO_Helper {
    /**
    * Private constants
    */

    /**
    * Private vars
    */
    private $db;		// PHP-assigned link for MySQL PDO access
    private $stmt;      // pointer to current query

    /**
    * Public vars
    */
    public $error_found;
    public $error_text;
    public $last_insert_id;
    public $rows_affected; // set by queries as well as updates, etc.
    public $last_result; // array of row objects built by query()
    public $current_row; // pointer into results array

    /**
    * __construct method for MySQL access
    */
    public function __construct ($credentials) { // surprisingly this must be public
        $myHostname = '';
        $myDatabase = '';
        $myUsername = '';
        $myPassword = '';
        $myCharset = 'utf8'; // default
        $myPort = '3306'; // default
        
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

        //--whitetlist credentials-----------
        if($credentials) foreach($credentials as $key => $val) {
            $key = str_replace('DB_','',$key);
            $key = strtolower($key);

            if ($key === 'hostname') {
    			$myHostname = $val;
    		}

            if ($key === 'database') {
    			$myDatabase = $val;
    		}

            if ($key === 'username') {
    			$myUsername = $val;
    		}

            if ($key === 'password') {
    			$myPassword = $val;
    		}

            if ($key === 'charset') {
                $myCharset = $val;
            }

            if ($key === 'port') {
                $myPort = $val;
            }
        }    

        $dsn = "mysql:dbname=$myDatabase;" .
               "host=$myHostname;" .
               "port=$myPort;" .
               "charset=$myCharset"
             ;
        
        //echo("dsn=$dsn myUsername=$myUsername myPassword=$myPassword\n");

        try {
            $this->db = new PDO($dsn, $myUsername, $myPassword);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) { 
            $this->error_found = TRUE;
            $this->error_text = ('database connection refused: ' . $e);
            return FALSE;
        }

        return TRUE;

    }

    /**********************************************
    * query()
    *------------------
    * Purpose: perform any kind of MySQL query
    *------------------
    * params: $sql -> sql statement
    * params: $params -> "prepared statement" parameters passed as array of arrays
    *------------------
    * example:
    * $sql = "SELECT * FROM dictionary WHERE
    *         SUBSTR(dic_word,1,1) = :first_letter    
    *         LIMIT :limit"; 
    * unset ($params); // in case there was a prior query, with leftover params
    * $params[] = array(':first_letter','C',PDO::PARAM_STR,'value'); // passed by value
    * $params[] = array(':limit',4,PDO::PARAM_INT,'param'); // runtime-evaluated var (param)
    * $db->query($sql, $params);
    *------------------
    * returns: count of rows affected (or false on failure)
    ***********************************************/
    public function query ($sql, $params=[]) {
        $sql = trim($sql); // for convenience of both debugging and determination of query type

        $this->stmt = $this->db->prepare($sql);

        //--accept parameterized variables into query--------
        if(isset($params) && $params) foreach($params as $param) {
            $bind_filter = PDO::PARAM_STR;
            if(isset($param[2])) {
                $bind_filter = $param[2];
            }
            if(isset($param[3]) && strtolower($param[3]) == 'value') {
                $this->stmt->bindValue($param[0],$param[1],$bind_filter);
            } else {
                $this->stmt->bindParam($param[0],$param[1],$bind_filter); // default
            };
        };

        try {
            $this->stmt->execute();
        } catch(PDOException $e) { 
            $this->error_found = TRUE;
            $this->error_text = ('SQL query error: ' . $e);
            return FALSE;
        }

        $this->rows_affected = $this->stmt->rowCount(); // works for queries too (in MySQL PDO)

        //--was query insert, delete, update, or replace?-----
        if ( preg_match("/^(insert|delete|update|replace)\s+/i",$sql) )
        {
            // Take note of the insert_id
            if ( preg_match("/^(insert|replace)\s+/i",$sql) )
            {
                $this->last_insert_id = $this->db->lastInsertId();
            }

        }
        // Query was a select
        else
        {

            $num_rows=0;
            while ( $row = $this->stmt->fetch(PDO::FETCH_OBJ) )
            {
                // Store results as objects within array
                $this->last_result[$num_rows] = $row;
                $num_rows++;
            }
            $this->current_row = 0; // used and incremented by this->get_row()
        };

        return $this->rows_affected;
    }

    /**********************************************
    * get_row()
    *------------------
    * Purpose: get single row (first row) from query
    *------------------
    * params: $sql [OPTIONAL] sql statement
    * params: $params [OPTIONAL] "prepared statement" parameters
    *------------------
    * returns: single row as object
    ***********************************************/
    public function get_row ($sql='', $params=[]) {
        if ($sql) {
            $this->query($sql,$params);
        };

        if ($this->current_row >= $this->rows_affected) {
            return (false);
        };

        $row = $this->last_result[$this->current_row];
        $this->current_row++;
        return $row;
    }

    /**********************************************
    * get_var()
    *------------------
    * Purpose: get single var (first var) from query
    *------------------
    * params: $sql [OPTIONAL] sql statement
    * params: $params [OPTIONAL] "prepared statement" parameters
    *------------------
    * returns: single var
    ***********************************************/
    function get_var ($sql, $params=[]) {
        if ($sql) {
            $this->query($sql,$params);
        };

        $value = '';

        if ($this->last_result[0]) foreach ($this->last_result[0] as $var => $value) {
            break;
        }
        
        return $value;
    }


    /**********************************************
    * get_results()
    *------------------
    * Purpose: get all results from query
    *------------------
    * params: $sql [OPTIONAL] sql statement
    * params: $params [OPTIONAL] "prepared statement" parameters
    *------------------
    * returns: single var
    ***********************************************/
    function get_results ($sql, $params=[]) {
        if ($sql) {
            $this->query($sql,$params);
        };

        return $this->last_result;
    }


    /**
    * __destruct method for MySQL access
    */
    public function __destruct () {
		return (true);
    }

}
