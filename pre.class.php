<?php
/**************************
* private/models/pre.class.php
*--------------------------
* HTML(+) preprocessor
* -------------------------
* 1.1.17 (cheth) 2016-Jun-20 convert to class
* 1.1.18 (cheth) 2016-Sep-10 publish as public project on GitHub
**************************/

class Preprocess_Helper
{

    /***************
    * private vars *
    ***************/
    private $version = "1.1.18";  // prelib version displayed on verbose runs
    private $db; // strategy implementation of Database_Interface
    private $md; // strategy implementation of Markdown_Interface
    private $defines; // array of #define(s)
    private $extension = '.html'; // default; leading "dot" required
    private $include_path; // used by optional configuration file
    private $line_count; // line number of primary file (unaffected by #include)
    
    /***************
    * public vars *
    ***************/
    public $error_found = FALSE;
    public $error_text = '';

    /******************************************
    * __construct method
    ******************************************/
    function __construct () {
        date_default_timezone_set('America/Los_Angeles');
    
        //--find myself-----
        $this->include_path = dirname(__FILE__) . DIRECTORY_SEPARATOR; // trailing slash
    }

    /**********************************************
    * function: getFileContents()
    *------------------
    * Purpose: get file
    *------------------
    * params: <string> filename
    *------------------
    * returns: <string> file contents or Boolean FALSE on failure
    ***********************************************/
    private function getFileContents($filename) {

        //--validity checking--------
        if (!$this->verifyFilename($filename)) {
            return (FALSE);
        };

        //--get file--------
        $file = file_get_contents($filename);

        if (!$file) {
            $this->error_found = TRUE;
            $this->error_text = $filename . ' error\n';
            return (FALSE);
        };

        return ($file);
    }    

    /**********************************************
    * function: getRowAsDefines()
    *------------------
    * Purpose: retrieve row and add to defines array
    *------------------
    * params: none
    *------------------
    * returns: <Boolean> TRUE if successful, FALSE if no more rows
    ***********************************************/
    private function getRowAsDefines() {

        //--verify database implementation has been defined-----
        if (!$this->verifyDbImplemented()){
            return FALSE;
        }

        //--retrieve row-----
        $row = $this->db->getRow();
        if (!$row) { // usually means there are no more rows (could also be error)
            return (FALSE);
        }

        foreach($row as $key => $val) {
            $this->defines[$key] = $val;
        }
        return (TRUE);
    }    

    /**********************************************
    * preprocess()
    *------------------
    * Purpose: oversee list of files to be preprocessed
    *------------------
    * params: $preArgs <array> filenames, with optional wildcards, to process
    * params: $preVerbose <Boolean> [OPTIONAL] TRUE to output additional information
    *------------------
    * returns: <integer> count of number of files processed
    *     -or- <HTML> "virtual" runs return preprocessed HTML
    ***********************************************/
    public function preprocess($preArgs, $preVerbose=FALSE) {

        $preVirtual = FALSE;
 
        //--command line calls automatically filled with pre.php-------
        if (substr($preArgs[0],-7) === "pre.php") { // ignore directory part of filename
            array_shift($preArgs);
        };

        //--command line calls can ask for "quiet"-------
        if ($preArgs && $preArgs[0] === "quiet") {
            $preVerbose = FALSE;
            array_shift($preArgs);
        };

        //--"virtual" calls do not write to disk------------
        if ($preArgs && $preArgs[0] == "virtual") {  ## flag to return HTML only.
            array_shift($preArgs);
            $preVirtual = TRUE;
            while (count($preArgs) > 1) {
                array_splice($preArgs,1,2);
            };
        };

        //--command line self-announcement-----------
        if ($preVerbose) {
            echo "pre.php - HTML preprocessor v$this->version\n";
            echo "Copyright (C) 1997-" . date("Y") . " Cheth Rowe Consulting. All rights reserved.\n";
        };

        if (count($preArgs) < 1) {
            echo "syntax: pre [quiet] <filename(s)>\n";
        };

        //--init vars-----------
        $preCount = "0";
        $preProcessed = array();

        $preStartDir = getcwd();
        $preStartDir = preg_replace("/.*:/", "", $preStartDir);  ## remove drive reference

        $preOutput_html = ""; // will contain preprocessing result
        $preAllFiles = array(); // filenames after wildcard expansion

        $preWilddir = '';
        $preWildcard = '';

        //--process command line file list--------------
        foreach ($preArgs as $preFilename)
        {
            $info = pathinfo($preFilename);
            $preWilddir = $info['dirname'];
            $preWildcard = $info['filename'];
            if (isset($info['extension']) && $info['extension']) { // might have forgot extension?
                $preWildcard = $info['filename'] . '.' . $info['extension'];
            };    

            $getcwd = getcwd();

            $preCurDir = getcwd();
            $preCurDir = preg_replace("/.*:/", "", $preCurDir);  ## remove drive reference
            if ($preStartDir <> $preCurDir) {
                if (file_exists($preStartDir)) { // should probably generate error
                    chdir($preStartDir);
                } else {
                    $this->error_found = TRUE;
                    $this->error_text = "can't open directory: $preStartDir\n";    
                }    
            };

            if ($preWilddir == "") { ## default is current dir
                $preWilddir = ".";     ## dot is current directory
            } else {              ## request to change directory
                if (file_exists($preWilddir)) { // should probably generate error
                    chdir("$preWilddir");
                } else {
                    $this->error_found = TRUE;
                    $this->error_text = "can't open directory: $preWilddir\n";    
                }    
            };

            $preCurDir = getcwd();

            if (!$preDirHandle = opendir (".")) {
                echo "\nE001 Can't open current directory\n";
            };
            $preAllFiles = array();
            while (false !== ($preFile = readdir($preDirHandle))) {
          	   array_push ($preAllFiles,$preFile);
      	    };

            closedir ($preDirHandle);


            //--command line error reporting----------------- 
            if ($this->error_found && !$preVirtual) {
                echo("ERROR: " . $this->error_text . "\n");
            }

            //--process each filename (after any wildcard expansion)----------------- 
            if (!$this->error_text) foreach ($preAllFiles as $preInputFilename){
                if (!fnmatch ($preWildcard, $preInputFilename) ) {
                    continue;
                }
                $this->defines = array();
                $preOutput_filename = preg_replace("/\.pre/", $this->extension, $preInputFilename);
                if ($preVerbose) {
                    echo "\nOutput file name=$preOutput_filename\n";
                };

                //--preprocess file--------------- 
                $preOutput_html = ''; // start fresh for each new file
                $preOutput_html = $this->process_file($preInputFilename,$preOutput_html,1);
                if (!$preVirtual) {
                    $this->preWriteHTML($preOutput_filename, $preOutput_html);
                };
                $preCount ++;
                $preProcessed[] = $preWilddir ."/". $preOutput_filename;
            };
        };

        //--closing process----------------
        if ($preVerbose) {
            $prettyText = '';
            $prettyVerb = 'processed';
            if ($this->error_found){
                $prettyVerb = 'attempted';
            }
            if ($preCount == 0) {
                $prettyText = 'Warning: no files';
            } elseif ($preCount == 1) {
                $prettyText = 'one file';
            } else {
                $prettyText = $preCount . ' files';
            }      
            echo("$prettyText $prettyVerb.\n");
        };

        if ($preVirtual) {
            return ($preOutput_html);
        } else {
            return ($preProcessed);  ## list of files preprocessed
        };

    }

    /**********************************************
    * function: preWriteHTML()
    *------------------
    * Purpose: write output file to disk
    *------------------
    * params: $xxOutput_filename <string> output filename
    * params: $preOutput_html <string> preprocessed lines to be written
    *------------------
    * returns: <Boolean> TRUE if successful
    ***********************************************/
    private function preWriteHTML($preOutput_filename, $preOutput_html) {

        //--validity checking--------
        if ($preOutput_html == "") {
            $this->error_found = TRUE;
            $this->error_text .= "Output html is empty.\n";
            return (FALSE);
        };

        //--open/create file for output-----------
        if (!$preHandle = fopen($preOutput_filename, 'wb')) {
            $this->error_found = TRUE;
            $this->error_text .= "Cannot open output file ($preOutput_filename)\n";
            return (FALSE);
        }

        //--write file-----------
        if (!fwrite($preHandle, $preOutput_html, strlen($preOutput_html))) {
            $strlen = strlen($preOutput_html);
            $this->error_found = TRUE;
            $this->error_text .= "Cannot write to file ($preOutput_filename) with handle $preHandle... attempting $strlen bytes\n";
            return (FALSE);
        }

        //--close file-----------
        if (!fclose($preHandle)) {
            $this->error_found = TRUE;
            $this->error_text .= "Cannot close output file ($preOutput_filename)\n";
            return (FALSE);
        }

        //--all done-----------
        return (TRUE);
    }

    /**********************************************
    * function: process_file()
    *------------------
    * Purpose: expand source file into ready-to-use thml (or other format)
    *------------------
    * params: $xxPreFilename <string> source filename
    * params: $preOutput_html <string>
    * params: $newFile <Boolean> [OPTIONAL] default TRUE (function can be called recursively)
    *------------------
    * returns: <integer> count of number of files processed
    *     -or- <HTML> "virtual" runs return preprocessed HTML
    ***********************************************/
    function process_file($xxPreFilename, &$preOutput_html, $newFile=TRUE)
    {
        if ($newFile) { // not #include
            $this->line_count = 0;
        };

        $prelibBypass = "";

        if (!is_array($xxPreFilename)) {
            $xxPreFilename = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $xxPreFilename);
            if (!file_exists($xxPreFilename)) {
                $this->error_found = TRUE;
                $this->error_text .= "Requested input file, \"$xxPreFilename,\" at line $this->line_count, does not exist.\n";
                return(FALSE);
            };
        };

        ##must be error free before proceeding
        ##if ($this->error_found) {
        ##    return(FALSE);
        ##};

        if (!is_array($xxPreFilename)) {
            $preHandle = fopen ($xxPreFilename, "r");
        };

        $preLoopSuck = false;
        $preLoopBlow = false;
        $preLoopIndex = 0;
        $preLoopSize = 0;
        $preMultilineArg = "";
        $preMultilineAns = "";
        $preMultilineVerb = '';

        while (1==1) {
            if (is_array($xxPreFilename)) {
                if (count($xxPreFilename) == 0) {
                    break;
                }
            } else { // not array
                if (feof ($preHandle)) {
                    //echo("eof:preLoopSuck=$preLoopSuck preLoopBlow=$preLoopBlow...\n");
                    break;
                }
            };
            if ($preLoopBlow && $preLoopIndex == 0) {
                $result =  $this->getRowAsDefines(); // load (next) row
                if (!$result){
                    $preLoopIndex = 0;
                    $preLoopSuck = false;
                    $preLoopBlow = false;
                    continue;
                }
            };

            if ($preLoopBlow && $preLoopIndex >= $preLoopSize) {
                $preLoopIndex = 0;
                continue;
            };

            if ($preLoopBlow) {
                $line = $preLoop[$preLoopIndex];
                $preLoopIndex++;
            }
            elseif (is_array($xxPreFilename))
            {
                $line = array_shift($xxPreFilename);
            }
            else
            {
                $line = fgets($preHandle, 4096);
                if ($newFile) { // not #include
                    $this->line_count++;
                }    
            };

            /***************
            * ## (comment) *
            ***************/
            //--double pound sign (##) for source comments--------------
            if (substr($line,0,2) == "##" && !$preMultilineVerb) {  ## COMMENT
                continue;
            };

            /***************
            * detect verbs *
            ***************/
            //--sanitize/standardize possible preprocessor commands---------- 
            $pattern = '|^(#.*?)\s.*$|';
            $count = preg_match($pattern,$line,$matches);
            $prefix = '';
            if ($count) { // if line begins with a recognized verb
                $prefix=$matches[1];
            }

            /*************
            * #endselect *
            *************/
            //--#endselect stops database loop---------- 
            if ($prefix == "#endselect"){  ## ENDSELECT
                $preLoopSuck = false;
                $preLoopBlow = true;
                $preLoopSize = $preLoopIndex;
                continue;
            };

            //--database loop increment---------- 
            if ($preLoopSuck) {
                $preLoop[$preLoopIndex] = $line;
                $preLoopIndex++;
                continue;
            }

            /*********
            * #endif *
            *********/ 
            if ($prefix == "#endif") {  ## ENDIF
                $prelibBypass = 0;
                continue;
            };

            if ($prefix == "#else" && $prelibBypass == 0) {  ## ELSE
                $prelibBypass = 1;
                continue;
            };

            if ($prefix == "#else" && $prelibBypass == 1) {  ## ELSE
                $prelibBypass = 0;
                continue;
            }

            if ($prelibBypass) {
                continue;
            }

            /*****************
            * expand defines *
            *****************/
            //--replace all #defined elements within source line----------------
            if ($prefix <> "#define" && $prefix <> "#undef" && isset($this->defines) > 0) {
                foreach ($this->defines as $arg => $ans) {
                    $line = preg_replace("/$arg/",$ans,$line);
                };
            };

            /**********
            * #select *
            **********/
            if ($prefix == "#select"){  ## SELECT

                //--verify database implementation has been defined-----
                if (!$this->verifyDbImplemented()){
                    continue;
                }

                //--parse query-----
                $argument = trim(substr($line, 7));
                if ($argument === '<<') {    ## multi-line sql coming
                     $preMultilineVerb = 'select';
                     $preMultilineArg = '';
                     $preMultilineAns = 'SELECT ';
                     continue;
                };
                $query = substr($line,1,strlen($line)-1); // everything except the initial pound sign
                $query = trim($query);

                $this->selectMultipleRow($query);
                $preLoopSuck = true;
                continue;
            };

            /**************
            * #select-one *
            **************/
            if ($prefix == "#select-one"){  ## SELECT ONE ROW ONLY

                //--verify database implementation has been defined-----
                if (!$this->verifyDbImplemented()){
                    continue;
                }

                //--parse query-----
                $argument = trim(substr($line, 11));
                if ($argument === '<<') {    ## multi-line sql coming
                     $preMultilineVerb = 'select-one';
                     $preMultilineArg = '';
                     $preMultilineAns = 'SELECT ';
                     continue;
                };
                $query = substr($line,1,strlen($line)-1); // everything except the initial pound sign
                $query = str_replace('select-one', 'select', $query);
                $query = trim($query);

                //$result = $this->db->query($query); // execute PDO query
                //if (!$result) {
                //    $this->error_found = TRUE;
                //    $this->error_text = "SQL error\n";
                //    echo("sql error encountered\n");
                //}
                $this->selectSingleRow($query); // load first row
                continue;
            };

            /************
            * #markdown *
            ************/
            if ($prefix == "#markdown"){  ## MARKDOWN
                $html = $this->markdownText(substr($line,9));
                $preOutput_html .= $html . "\n";
                continue;
            };

            /***********
            * #include *
            ***********/
            if ($prefix == "#include"){  ## INCLUDE
                $exploded = explode(" ", trim($line));
                if (count($exploded) == 2) {
                    $op = $exploded[0];
                    $ans= $exploded[1];
                    $ans = trim($ans);
                    $ans = preg_replace("/[<>]/","",$ans);
                    $this->process_file($ans,$preOutput_html,0); // recursive call
                };
                if (count($exploded) == 4) { #include <filename> AS <key>
                    $op = $exploded[0];
                    $ans= $exploded[1];
                    $as = $exploded[2];
                    $key= $exploded[3];
                    if (strtolower($as) <> 'as' || !$key) {
                        continue;
                    }
                    $value = $this->getFileContents($ans);
                    if ($value) {
                        $this->defines[$key] = $value; // store file contents in "defines" array
                    }
                };
                continue;
            };

            /**********
            * #define *
            **********/
            if ($prefix == "#define" && isset($this->defines) > 0) {  ## DEFINE
                $exploded = explode(" ", $line, 3);
                if (count($exploded) > 1) {
                    $op = $exploded[0];
                    $origarg = $exploded[1];
                    $replaceable = "";
                    if (count($exploded) == 2) {
                        $this->defines["$origarg"] = "$replaceable";
                    };
                    if (count($exploded) > 2) {
                        $replaceable = trim($exploded[2]);
                        if ($replaceable == '<<') {    ## multi-line define
                             $preMultilineVerb = "define";
                             $preMultilineAns = "";
                             $preMultilineArg = $origarg;
                             continue;
                        };
                        foreach ($this->defines as $arg => $ans) {
                            $replaceable = preg_replace("/$arg/","$ans",$replaceable);
                            $replaceable = trim($replaceable); 
                        };
                        $this->defines["$origarg"] = "$replaceable";
                    };
                };
                $count = count($exploded);
                continue;
            };

            /**********
            * #define *
            **********/
            if ($prefix == "#define" && isset($this->defines) == 0){  ## DEFINE (first time)
                echo("defines first time\n");
                $exploded = explode(" ", $line, 3);
                $ans = "";
                if (count($exploded) > 1) {
                    $op = $exploded[0];
                    $arg= $exploded[1];
                    $arg = trim($arg);
                    if (count($exploded) == 3) {
                        $ans = $exploded[2];
                    };
                    $ans = trim($ans);
                    $this->defines["$arg"] = "$ans";
                };
                continue;
            };

            /**********
            * #enddef *
            **********/
            if ($prefix == "#enddef") { ## end of multi-line define
                $this->defines["$preMultilineArg"] = "$preMultilineAns";
                $preMultilineVerb = "";
                $preMultilineAns = "";
                $preMultilineArg = "";
                continue;
            };

            /*********************************
            * >> (end multi-line statements) *
            *********************************/
            if (trim($line) == '>>' && $preMultilineVerb) { ## end of multi-line command
                $preMultilineAns = trim($preMultilineAns);
                if ($preMultilineVerb === 'define') {
                    $this->defines["$preMultilineArg"] = $preMultilineAns;
                } elseif ($preMultilineVerb === 'select') {
                    $this->selectMultipleRow($preMultilineAns);
                    $preLoopSuck = true;
                } elseif ($preMultilineVerb === 'select-one') {
                    $this->selectSingleRow($preMultilineAns);
                }    
                $preMultilineVerb = '';
                $preMultilineAns = '';
                $preMultilineArg = '';
                continue;
            };

            /*******************
            * multi-line verbs *
            *******************/
            if ($preMultilineVerb) {  ## add to multi-line define
                $preMultilineAns .= $line;
                continue;
            };

            /*************
            * #coolclass *
            *************/
            if ($prefix == "#coolclass") {  ## COOLCLASS
                $exploded = explode(" ", $line, 3);
                if (count($exploded) === 3) {
                    $class_filename = $exploded[1];
                    $class_classname = $exploded[2];
                };
                if (count($exploded) !== 3) {
                    $this->error_found = TRUE;
                    $this->error_text = '#coolclass requires two arguments\n';
                }
                if(!$this->error_found) {
                    if (!$file_exists($class_filename)) {
                        $this->error_found = TRUE;
                        $this->error_text = '#coolclass not found\n';
                    } else {
                        require_once ($class_filename);
                        $this->cool = new $class_classname;
                    }
                }
               
                continue;
            };

            /*************
            * #extension *
            *************/
            if ($prefix == "#extension") {  ## EXTENSION
                $exploded = explode(" ", $line, 2);
                if (count($exploded) === 2) {
                    $arg = $exploded[1];
                    $arg = trim($arg);
                };
                if (substr($arg,0,1) !== '.'){
                    $this->error_found = TRUE;
                    $this->error_text = '#extension must begin with "dot"\n';
                }    
                if (count($exploded) !== 2) {
                    $this->error_found = TRUE;
                    $this->error_text = '#extension requires one argument\n';
                }
                if(!$this->error_found && $arg) {
                    $this_extension = $arg;
                }
               
                continue;
            };

            /*************
            * #mysqlopen *
            *************/
            if ($prefix == "#mysqlopen") {  ## MYSQLOPEN

                //--verify database implementation has been defined-----
                if (!$this->verifyDbImplemented()){
                    continue;
                }

                //--parse database connection credentials-----
                $pattern = '|(.*?)=(.*?)\s|';
                $count = preg_match_all($pattern, substr($line,11), $matches);
                $credentials = array();
                if ($count) for ($i=0;$i<$count;$i++){
                    $key = $matches[1][$i];
                    $val = $matches[2][$i];
                    $credentials[$key] = $val;
                }

                //--create database connection------------
                $result = $this->db->createConnection($credentials);
                if (!$result) { // semi-soft error condition; does not cause immediate death
                    $this->error_found = TRUE;
                    $this->error_text .= "Could not connect to database.\n";
                };    
               
                continue;
            };

            /******
            * #if *
            ******/
            if ($prefix == "#if") {  ## IF
                $exploded = explode(" ", $line, 2);
                $evalstring = $exploded[1];
                $evalstring = preg_replace("/\"/","'",$evalstring);
                $evalstring = preg_replace("/eq/","==",$evalstring);
                $evalstring = preg_replace("/ne/","<>",$evalstring);
                if ($evalstring=="") {$evalstring=0;};
                $evalstring = "return($evalstring);";
                if (eval("$evalstring")) {$prelibBypass = 0;} else {$prelibBypass = 1;};
                continue;
            };

            /*********
            * #undef *
            *********/
            if ($prefix == "#undef") {  ## UNDEF
                $exploded = explode(" ", $line, 2);
                if (count($exploded) == 2) {
                    $arg = $exploded[1];
                    $arg = trim($arg);
                    unset($this->defines["$arg"]);
                };
                continue;
            };

            /********
            * #eval *
            ********/
            if ($prefix == "#eval") {  ## EVAL (dangerous inline evaluation)
                $exploded = explode(" ", $line, 3);
                $evalkey = $exploded[1];
                $evalstring = $exploded[2];
                $evalresult = eval("\$evaluated=$evalstring;; return(\$evaluated);");
                $this->defines[$evalkey] = "$evalresult";
                continue;
            };


            /*****************
            * ordinary lines *
            *****************/
            $preOutput_html .= $line;

        }

        //--close file--------
        if (!is_array($xxPreFilename)) {
            fclose ($preHandle);
        };

        //--must be error free before proceeding--------
        if ($this->error_found && $newFile) { // only report errors once
            echo $this->error_text;
            return (FALSE);
        };

        //--strip any leading whitespace--------
        $preOutput_html = ltrim($preOutput_html);

        //--return preprocessed file--------
        return ($preOutput_html);

    }

    /**********************************************
    * function: requireIfExists()
    *------------------
    * Purpose: load PHP source file if it exists
    *------------------
    * params: <string> filename
    *------------------
    * returns: <Boolean> TRUE if successful
    ***********************************************/
    public function requireIfExists($filename) {
        
        //--validity check---------------
        if (!$filename || !is_string($filename)) {
            $this->error_found = TRUE;
            $this->error_text = "filename expected\n";
            return FALSE; // error condition
        }

        //--does file exist?------------------
        if (!file_exists($filename)) {
            return FALSE; // neither error nor warning; likely just means a sparse environment
        }

        //--load file------------------
        require_once ($filename);
        return (TRUE);
    }    

    /**********************************************
    * function: selectMultipleRow()
    *------------------
    * Purpose: execute query that might produce multiple rows
    *------------------
    * params: sql
    *------------------
    * returns: <Boolean> TRUE if successful
    ***********************************************/
    private function selectMultipleRow($sql) {
        $query = str_replace('select-one', 'select', $sql);
        $query = str_replace('<<', '', $query); // multi-line sql statements
        $query = trim($query);

        $result = $this->db->query($query); // execute PDO query
        if ($this->db->error_found) {
            $this->error_found = TRUE;
            $this->error_text = "SQL error at line $this->line_count. " . $this->db->error_text . "\n";
            return (FALSE);
        }
        return (TRUE);
    }    

    /**********************************************
    * function: selectSingleRow()
    *------------------
    * Purpose: retrieve single row
    *------------------
    * params: sql
    *------------------
    * returns: <Boolean> TRUE if successful, FALSE if no more rows
    ***********************************************/
    private function selectSingleRow($sql) {
        $query = str_replace('select-one', 'select', $sql);
        $query = str_replace('<<', '', $query); // multi-line sql statements
        $query = trim($query);

        $result = $this->db->query($query); // execute PDO query
        if ($this->db->error_found) {
            $this->error_found = TRUE;
            $this->error_text = "SQL error at line $this->line_count. " . $this->db->error_text . "\n";
            return (FALSE);
        }
        $this->getRowAsDefines(); // load first row into defines array
        return (TRUE);
    }    

    /******************************************
    * setDatabaseHelper()
    *----------------------
    * Use this function to enable database access.
    *----------------------
    * params: <Database_Interface> class name
    *----------------------
    * returns: null
    ******************************************/
    public function setDatabaseHelper (Database_Interface $class_name ) {
        $this->db = $class_name;
    }

    /******************************************
    * setMarkdownHelper()
    *----------------------
    * Use this function to enable markdown access.
    *----------------------
    * params: <Markdown_Interface> class name
    *----------------------
    * returns: null
    ******************************************/
    public function setMarkdownHelper (Markdown_Interface $class_name ) {
        $this->md = $class_name;
    }

    /**********************************************
    * function: verifyDbImplemented()
    *------------------
    * Purpose: verify that database implementation has been defined
    *------------------
    * params: none
    *------------------
    * returns: <Boolean> TRUE if database connection established
    ***********************************************/
    private function verifyDbImplemented() {
        if (!$this->db) {
            $this->error_found = TRUE;
            $this->error_text = "Database option not enabled in pre.config.php\n";
            return FALSE;
        }
        return TRUE;
    }    

    /**********************************************
    * function: markdownText()
    *------------------
    * Purpose: expand markdown text to html
    *------------------
    * params: <string> text
    *------------------
    * returns: <string> html or FALSE on error
    ***********************************************/
    private function markdownText($text) {
        if (!$this->md) {
            $this->error_found = TRUE;
            $this->error_text = "Markdown option not enabled in pre.config.php\n";
            return FALSE;
        }
        $html = $this->md->formatText($text); 
        return $html;
    }    

    /**********************************************
    * function: verifyFilename()
    *------------------
    * Purpose: verify file exists
    *------------------
    * params: <string> filename
    *------------------
    * returns: <Boolena> TRUE if file exists
    ***********************************************/
    private function verifyFilename($filename) {

        //--validity checking--------
        if (!$filename) {
            $this->error_found = TRUE;
            $this->error_text = "filename missing\n";
            return (FALSE);
        };

        //--file must exist--------
        if (!file_exists($filename)) {
            $this->error_found = TRUE;
            $this->error_text = $filename . ' not found\n';
            return (FALSE);
        };

        return (TRUE);
    }    

}
