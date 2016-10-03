<?php
/**************************
* private/models/pre.md.interface.php
*--------------------------
* define Markdown interface handling for "pre" preprocessor
*--------------------------
* 1.0.0 (cheth) 2016-Sep-17 initial implementation
**************************/

interface Markdown_Interface {

    /**********************************************
    * formatText()
    *------------------
    * Purpose: fancify text
    * Actual implemetation may observe traditional Markdown, or anything else.
    *------------------
    * param: <string> marked-up text
    *------------------
    * returns: <string> fancified text
    ***********************************************/
    public function formatText($string);
    
}
