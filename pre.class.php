<?php
/**************************
* private/models/pre.class.php
*--------------------------
* (HTML) preprocessing
* -------------------------
* Syntax for preprocess candidate file
* ## <comment>
*
* #define <label> <value>
* #define <label> <<
* #enddef
*
* #eval
*
* #if <if clause>
* #else
* #endif
*
* #include <filename>
*
* #mysqlopen <credentials>
* #select <sql>
* #select-one <sql>
* #endselect
*
*--------------------------
* 1.1.17 (cheth) 2016-Jun-20 convert to class
* 1.1.18 (cheth) 2016-Sep-10 publish as public project on GitHub
**************************/

class Preprocess_Helper
{

    /***************
    * private vars *
    ***************/
    private $version = "1.1.18";  // prelib version displayed on verbose runs
    private $db; // database connection
    private $defines; // array of #define(s)
    private $extension = '.html'; // default; leading "dot" required
    private $cool; // prettification (coolness) connection
    
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
    }

    /******************************************
    * database_connnect
    ******************************************/
    private function database_connect () {
        global $db;
        if ($db) {
            $this->db = $db;
        }
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
        //$predefined = "";

        //--command line calls automatically filled with pre.php-------
        if (substr($preArgs[0],-7) === "pre.php") { // ignore directory part of filename
            array_shift($preArgs);
            //if (!isset($preVerbose)) {$preVerbose = "on";};
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
                #echo "preArgs[1]=$preArgs[1]...<br>\n";
                #echo "preArgs[2]=$preArgs[2]...<br>\n";
                //$predefined["$preArgs[1]"] = "$preArgs[2]";
                array_splice($preArgs,1,2);
            };
        };

        //--command line self-announcement-----------
        if ($preVerbose) {
            echo "pre.php - HTML preprocessor v$this->version\n";
            echo "Copyright (C) 1997-" . date("Y") . " Cheth Rowe Consulting. All rights reserved.\n";
            #echo "preprocessing $preArgs[0]\n";
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

        //--process command line file list--------------
        foreach ($preArgs as $preFilename)
        {
            $info = pathinfo($preFilename);
            $preWilddir = $info['dirname'];
            $preWildcard = $info['filename'] . '.' . $info['extension'];

            $getcwd = getcwd();
            //echo "\n-->preFilename=$preFilename... preWilddir=$preWilddir... preWildcard=$preWildcard... getcwd=$getcwd...\n";

            $preCurDir = getcwd();
            $preCurDir = preg_replace("/.*:/", "", $preCurDir);  ## remove drive reference
            if ($preStartDir <> $preCurDir) {
                //echo "before preCurDir=$preCurDir... preStartDir=$preStartDir...<br>\n";
                if (file_exists($preStartDir)) { // should probably generate error
                    chdir($preStartDir);
                } else {
                    $this->error_found = TRUE;
                    $this->error_text = "can't open directory: $preStartDir";    
                }    
                //$preCurDir = getcwd();
                #echo "middle  preCurDir=$preCurDir... preStartDir=$preStartDir...<br>\n";
            };

            if ($preWilddir == "") { ## default is current dir
                $preWilddir = ".";     ## dot is current directory
            } else {              ## request to change directory
                //echo "before chdir, preCurDir=$preCurDir... preWilddir=$preWilddir... preFilename=$preFilename...<br>\n";
                if (file_exists($preWilddir)) { // should probably generate error
                    chdir("$preWilddir");
                } else {
                    $this->error_found = TRUE;
                    $this->error_text = "can't open directory: $preWilddir";    
                }    
            };

            $preCurDir = getcwd();
            //echo "after  preCurDir=$preCurDir... preStartDir=$preStartDir...<br>\n";

            if (!$preDirHandle = opendir (".")) {
                echo "\nE001 Can't open current directory";
            };
            $preAllFiles = array();
            while (false !== ($preFile = readdir($preDirHandle))) {
               #echo "\nPushing preFile=$preFile...";
          	   array_push ($preAllFiles,$preFile);
      	    };

            #$preAllFiles = readdir ($preDirHandle);
            #&dieNow ("wilddir=$wilddir... wildcard=$wildcard... allfiles=@allfiles...");
            closedir ($preDirHandle);

            #echo "\npreAllFiles=$preAllFiles... element0=$preAllFiles[0]...element1=$preAllFiles[1]...";

            //--command line error reporting----------------- 
            if ($this->error_found && !$preVirtual) {
                echo("ERROR: " . $this->error_text . "\n");
            }

            //--process each filename (after any wildcard expansion)----------------- 
            if (!$this->error_text) foreach ($preAllFiles as $preInputFilename){
                //echo "<br>\npreWildcard=$preWildcard... preInputFilename=$preInputFilename...<br>\n";
                //if (!preg_match ("/^$preWildcard/i", "$preInputFilename") ) {continue;};
                if (!fnmatch ($preWildcard, $preInputFilename) ) {
                    continue;
                }
                #if (isset($this->defines)) {
                    #unset($GLOBALS['defines']);
                #};
                #unset ($this->defines);
                #global $this->defines;
                $this->defines = array();
                #array_splice($this->defines,0);
                #$myvirtual="";
                #echo "\npreInputFilename=$preInputFilename...";
                $preOutput_filename = preg_replace("/\.pre/", $this->extension, $preInputFilename);
                if ($preVerbose) {
                    echo "\nOutput file name=$preOutput_filename<br>\n";
                    #print_r ($this->defines);
                    #echo "<br><br>\n";
                };
                //if ($preVirtual) {
                    //if($predefined) foreach($predefined as $key => $value) {
                    //    $this->defines["$key"] = "$value";
                    //};
                //};

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
            echo "\n";
        };

        if ($preVirtual) {
            return ($preOutput_html);
        } else {
            return ($preProcessed);  ## list of files preprocessed
        };

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
        global $preLineCnt;
        if ($newFile) {
            $preLineCnt = 0;
        };

        $prelibBypass = "";

        if (!is_array($xxPreFilename)) {
            //$xxPreFilename = stripslashes($xxPreFilename);
            $xxPreFilename = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $xxPreFilename);
            if (!file_exists($xxPreFilename)) {
                //$preCurDir = getcwd();
                //echo "file not found: preCurDir=$preCurDir...xxPreFilename=$xxPreFilename...<br>\n";
                #print_r ($this->defines);
                $this->error_found = TRUE;
                $this->error_text .= "Requested input file, \"$xxPreFilename,\" at line $preLineCnt, does not exist.\n";
                return(FALSE);
            };
        };

        ##must be error free before proceeding
        if ($this->error_found) {
            echo $this->error_text;
            return(FALSE);
        };

        if (!is_array($xxPreFilename)) {
            //if(!file_exists($xxPreFilename))
            //{
            //    die("Fatal Error P410: Preprocessor input file '$xxPreFilename' does not exist.");
            //};
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
                //echo "in preLoopBlow at $preLoopIndex... line=$line...<br>";
                $preLoopIndex++;
            }
            elseif (is_array($xxPreFilename))
            {
                $line = array_shift($xxPreFilename);
            }
            else
            {
                $line = fgets($preHandle, 4096);
                $preLineCnt++;
            };

            /***************
            * ## (comment) *
            ***************/
            //--double pound sign (##) for source comments--------------
            if (substr($line,0,2) == "##") {  ## COMMENT
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
                //echo("encountered endselect\n");
                continue;
            };

            //--database loop increment---------- 
            if ($preLoopSuck) {
                //echo "in preLoopSuck at $preLoopIndex... line=$line...<br>";
                $preLoop[$preLoopIndex] = $line;
                $preLoopIndex++;
                continue;
            }

            /*********
            * #endif *
            *********/ 
            if ($prefix == "#endif") {  ## ENDIF
                $prelibBypass = 0;
                #echo "#endif encountered suck=$preLoopSuck... blow=$preLoopBlow... index=$preLoopIndex... line=$line...<br>\n";
                continue;
            };

            if ($prefix == "#else" && $prelibBypass == 0) {  ## ELSE
                #echo "#else encountered suck=$preLoopSuck... blow=$preLoopBlow... index=$preLoopIndex... bypass=$prelibBypass...line=$line...<br>\n";
                $prelibBypass = 1;
                continue;
            };

            if ($prefix == "#else" && $prelibBypass == 1) {  ## ELSE
                #echo "#else encountered suck=$preLoopSuck... blow=$preLoopBlow... index=$preLoopIndex... bypass=$prelibBypass...line=$line...<br>\n";
                $prelibBypass = 0;
                continue;
            }

            if ($prelibBypass) {
                continue;
            }

            /**********
            * #define *
            **********/
            //--replace all #defined elements within source line----------------
            if ($prefix <> "#define" && $prefix <> "#undef" && isset($this->defines) > 0) {
                foreach ($this->defines as $arg => $ans) {
                    $line = preg_replace("/$arg/",$ans,$line);
                    //echo "line=$line... arg=$arg... ans=$ans...<br>\n";
                };
            };

            /**********
            * #select *
            **********/
            if ($prefix == "#select"){  ## SELECT
                $argument = trim(substr($line, 7));
                if ($argument === '>>') {    ## multi-line sql coming
                     $preMultilineVerb = 'select';
                     $preMultilineArg = '';
                     $preMultilineAns = 'SELECT ';
                     //echo("initiated multiline select\n");
                     continue;
                };
                $query = substr($line,1,strlen($line)-1); // everything except the initial pound sign
                $query = trim($query);

                $result = $this->db->query($query); // execute PDO query
                //echo "result=$result...\n";
                #$this->getRowAsDefines(); // load first row
                $preLoopSuck = true;
                continue;
            };

            /**************
            * #select-one *
            **************/
            if ($prefix == "#select-one"){  ## SELECT ONE ROW ONLY
                $argument = trim(substr($line, 11));
                if ($argument === '>>') {    ## multi-line sql coming
                     $preMultilineVerb = 'select-one';
                     $preMultilineArg = '';
                     $preMultilineAns = 'SELECT ';
                     //echo("initiated multiline select-one\n");
                     continue;
                };
                $query = substr($line,1,strlen($line)-1); // everything except the initial pound sign
                $query = str_replace('select-one', 'select', $query);
                $query = trim($query);

                $result = $this->db->query($query); // execute PDO query
                $this->getRowAsDefines(); // load first row
                //echo "processed one-row-only...\n";
                #$this->getRowAsDefines(); // load first row
                continue;
            };

            /***********
            * #include *
            ***********/
            if ($prefix == "#include"){  ## INCLUDE
                $exploded = explode(" ", trim($line));
                //echo("inclusion underway\n");
                //echo("explosion count=" . count($exploded) . "\n");
                //print_r($exploded);
                if (count($exploded) == 2) {
                    $op = $exploded[0];
                    $ans= $exploded[1];
                    $ans = trim($ans);
                    $ans = preg_replace("/[<>]/","",$ans);
                    //echo("include=$ans\n");
                    $this->process_file($ans,$preOutput_html,0); // recursive call
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
                        //echo "count=2:preLineCnt=$preLineCnt... exploded=$exploded... op=$op... origarg=$origarg... replaceable=$replaceable...<br>\n";
                    };
                    if (count($exploded) > 2) {
                        $replaceable = trim($exploded[2]);
                        if ($replaceable == '>>') {    ## multi-line define
                             $preMultilineVerb = "define";
                             $preMultilineAns = "";
                             $preMultilineArg = $origarg;
                             #echo "#defines start: preMultilineArg=$preMultilineArg...\n";
                             continue;
                        };
                        foreach ($this->defines as $arg => $ans) {
                            $replaceable = preg_replace("/$arg/","$ans",$replaceable);
                            $replaceable = trim($replaceable); 
                            #$line = "$op$origarg$replaceable"; 
                            //echo "count>2:preLineCnt=$preLineCnt... line=$line... op=$op...  origarg=$origarg... replaceable=$replaceable...<br>\n";
                        };
                        $this->defines["$origarg"] = "$replaceable";
                    };
                };
                $count = count($exploded);
                //echo "ct=$count...  preLineCnt=$preLineCnt... exploded=$exploded... op=$op...  origarg=$origarg... replaceable=$replaceable...<br>\n";
                continue;
            };

            /**********
            * #define *
            **********/
            if ($prefix == "#define" && isset($this->defines) == 0){  ## DEFINE (first time)
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
                    //echo "preLineCnt=$preLineCnt... exploded=$exploded... op=$op... arg=$arg... ans=$ans...<br>\n";
                };
                continue;
            };

            /**********
            * #enddef *
            **********/
            if ($prefix == "#enddef") { ## end of multi-line define
                #echo "multi-line: preMultilineArg=$preMultilineArg... preMultilineAns=$preMultilineAns...";
                $this->defines["$preMultilineArg"] = "$preMultilineAns";
                $preMultilineVerb = "";
                $preMultilineAns = "";
                $preMultilineArg = "";
                continue;
            };

            /*****
            * << *
            *****/
            if (trim($line) == '<<' && $preMultilineVerb) { ## end of multi-line command
                if ($preMultilineVerb === 'define') {
                    $this->defines["$preMultilineArg"] = "$preMultilineAns";
                } elseif ($preMultilineVerb === 'select') {
                    $result = $this->db->query($preMultilineAns); // execute PDO query
                    $preLoopSuck = true;
                } elseif ($preMultilineVerb === 'select-one') {
                    $this->selectSingleRow($preMultilineAns);
                }    
                $preMultilineVerb = '';
                $preMultilineAns = '';
                $preMultilineArg = '';
                //echo("processed double <<\n");
                continue;
            };

            /*******************
            * multi-line verbs *
            *******************/
            if ($preMultilineVerb) {  ## add to multi-line define
                $preMultilineAns .= $line;
                //echo("added during $preMultilineVerb\n");
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
                $pattern = '|(.*?)=(.*?)\s|';
                $count = preg_match_all($pattern, substr($line,11), $matches);
                //print_r($matches);
                $credentials = array();
                if ($count) for ($i=0;$i<$count;$i++){
                    $key = $matches[1][$i];
                    $val = $matches[2][$i];
                    $credentials[$key] = $val;
                }
                $this->database_connect();
                $result = $this->db->createConnection($credentials);
                if (!$result) { // semi-soft error condition; does not cause immediate death
                    $this->error_found = TRUE;
                    $this->error_text .= "Could not connect to database.";
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
                #$evalstring="return(3==4);";
                if ($evalstring=="") {$evalstring=0;};
                $evalstring = "return($evalstring);";
                if (eval("$evalstring")) {$prelibBypass = 0;} else {$prelibBypass = 1;};
                //echo "line=$line... prelibBypass=$prelibBypass... evalstring=$evalstring...<br>\n";
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
                $exploded = explode(" ", $line, 2);
                $evalstring = $exploded[1];
                $evalresult = eval("\$evaluated=$evalstring;; return(\$evaluated);");
                $this->defines["EVALRESULT"] = "$evalresult";
                continue;
            };


            /*****************
            * ordinary lines *
            *****************/
            #if (!is_array($xxPreFilename)) {
                #echo "line=$line<br>\n";
                $preOutput_html .= $line;
            #};

        }

        //--close file--------
        if (!is_array($xxPreFilename)) {
            fclose ($preHandle);
        };

        //--must be error free before proceeding--------
        if ($this->error_found) {
            echo $this->error_text;
            exit;
        };

        //--strip any leading whitespace--------
        $preOutput_html = ltrim($preOutput_html);

        //--return preprocessed file--------
        return ($preOutput_html);

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
        //echo("selecting single row\n");
        $query = str_replace('select-one', 'select', $sql);
        $query = str_replace('>>', '', $query); // multi-line sql statements
        $query = trim($query);

        $result = $this->db->query($query); // execute PDO query
        $this->getRowAsDefines(); // load first row into defines array
        return (TRUE);
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
        $row = $this->db->getRow();
        if (!$row) { // usually means there are no more rows (could also be error)
            return (FALSE);
        }

        foreach($row as $key => $val) {
            $this->defines[$key] = $val;
        }
        //echo("inside getRowAsDefines()\n");
        return (TRUE);
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
            $this->error_text .= "Output html is empty.";
            return (FALSE);
        };

        //--open/create file for output-----------
        if (!$preHandle = fopen($preOutput_filename, 'wb')) {
            $this->error_found = TRUE;
            $this->error_text .= "Cannot open output file ($preOutput_filename)";
            return (FALSE);
        }

        //--write file-----------
        if (!fwrite($preHandle, $preOutput_html, strlen($preOutput_html))) {
            $strlen = strlen($preOutput_html);
            $this->error_found = TRUE;
            $this->error_text .= "Cannot write to file ($preOutput_filename) with handle $preHandle... attempting $strlen bytes";
            return (FALSE);
        }

        //--close file-----------
        if (!fclose($preHandle)) {
            $this->error_found = TRUE;
            $this->error_text .= "Cannot close output file ($preOutput_filename)";
            return (FALSE);
        }

        //--all done-----------
        return (TRUE);
    }

}


