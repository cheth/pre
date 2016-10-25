<?php
/****************************************
* filename: pre.prettifier.php
* purpose:  Class for applying prettifications to text
****************************************
* 1.0.0 (cheth) 2016-May-10 initial implementation
* 1.1.0 (cheth) 2016-Sep-12 migrate to pre
****************************************/

class Prettification_Helper implements Markdown_Interface {
    /**
    * Private constants
    */

    /**
    * Private vars
    */
    private $verbs;
    private $pre_verb = '```';
    private $div_verb = '~~~';
    private $is_pre = FALSE;
    private $is_div = FALSE;
    private $is_ul = FALSE;
    private $previous_line = '';
    private $previous_character = '';
    private $current_character = '';

    private $verb_characters = array(
        "\n"  // two newlines start paragraph; many verbs must begin line  
       ,"'"   // apostrophe converted to HTML entity
       ,'"'   // double quotes are matched into left and right HTML entities
       ,'-'   // double hypens become em dash
       ,'*'   // asterisk in column 1 begins <h*> tag; elsewhere causes italics/bolding
       ,'~'   // tilde in column 1 begins/ends <pre> tag; elsewhere causes <code>
       ,'['   // start alt text portion of link/image
       ,']'   // end alt text portion of link/image
       ,'('   // start url portion of link/image
       ,')'   // end url portion of link/image
       ,'!'   // exclamation point in column 1 identifies image
    );

    private $formatted_text; // output html
    /**
    * Public vars
    */
    public $error_found;
    public $error_text;

    /**
    * __construct method
    */
    public function __construct () { // surprisingly this must be public
        $this->verbs[] = (object) array('verb' => "\n");
        $this->verbs[] = (object) array('verb' => "'", 'immediate' => TRUE, 'to' => '&apos;');
        $this->verbs[] = (object) array('verb' => "'", 'pair' => TRUE);
        $this->verbs[] = (object) array('verb' => "-", 'double' => TRUE, 'to' => '&mdash;');
        $this->verbs[] = (object) array('verb' => "*", 'column1' => TRUE);
        $this->verbs[] = (object) array('verb' => "*", 'column1' => FALSE, 'pair' => TRUE, 'tag' => 'i');
        $this->verbs[] = (object) array('verb' => "_", 'column1' => FALSE, 'pair' => TRUE, 'tag' => 'strong');
        $this->verbs[] = (object) array('verb' => "~", 'column1' => TRUE, 'triple' => TRUE, 'pair' => TRUE, 'tag' => 'pre');
        $this->verbs[] = (object) array('verb' => "![", 'column1' => TRUE);
        $this->verbs[] = (object) array('verb' => "[", 'column1' => TRUE, 'helper' => 'helper_href');
    }

    /**********************************************
    * formatText()
    *------------------
    * Purpose: fancify text
    *------------------
    * Notes: this is a parser, not a regex approach.
    *------------------
    * Rules:
    * {paragraph mode} := default state; ends on blank line; restarts unless {special characters}
    * {special characters} := [~~|{{|}}^^]
    * \n := begin/end paragraph
    * ^^ := begin/end sub-heading
    * %% := begin/end raw html
    * $$ := begin/end in-line code fragment (typically keyword)
    * {{ := if not within paragraph: begin pre-heading; following text shown in pre-heading bar
    * }} := if not within paragraph: end pre-heading;
    * ~~\n := begin/end {pre mode}
    * || := begin/end pull quote
    * {pre mode} := echo line shown within {code}
    * -- := within paragraph: em dash
    *-----------------  
    * params: <string> text to be prettified
    *------------------
    * returns: formatted text or FALSE on failure
    ***********************************************/
    public function formatText ($text) {
        //echo("text=$text\n");
        if (!is_string($text)) {
            $this->error_found = TRUE;
            $this->error_text = 'requires string';
            return (FALSE);
        };

        $formatted_text = '';
        $is_sub_heading = FALSE;
        $is_pre_heading = FALSE;
        $is_raw_html = FALSE;
        $is_code = FALSE;
        $is_code_fragment = FALSE;
        $is_paragraph = FALSE;
        $is_pull_quote = FALSE;
        $is_quoting = FALSE;
        $is_header = FALSE; // within text of header
        $h_counter = 0; // h1 to h6
        $backtick_counter = 0; // ``` starts code block; ``` ends code block
        $was_em_dash = FALSE; // was not is, because just sent
        $current_character = '';
        $previous_character = '';

        $text = trim($text);
        $line_trained_text = str_replace("\r\n", "\n", $text);
        $line_trained_text .= "\n\n"; // eof marker

        for ($i=0; $i<strlen($line_trained_text); $i++) {
            $this->previous_character = $this->current_character;
            $this->previous_line .= $this->current_character;
            $this->current_character = substr($line_trained_text, $i, 1);

            //--main loop---------
            if ($this->current_character === "\n") {
                if ($this->is_pre) {
                    $this->addToOutput($this->previous_line);
                    $this->previous_line = '';
                    continue;
                }
                if ($this->is_pre && substr($this->previous_line,0,3) === $this->pre_verb) {
                    $this->closePre();
                    continue;
                }
                if (substr($this->previous_line,0,3) === $this->pre_verb) {
                    $this->openPre();
                    continue;
                }
                if ($this->is_div && substr($this->previous_line,0,3) === $this->div_verb) {
                    $this->closeDiv();
                    $this->previous_line = '';
                    $this->is_div = FALSE;
                    continue;
                }
                if (substr($this->previous_line,0,3) === $this->div_verb) {
                    $this->openDiv();
                    $this->previous_line = '';
                    $this->is_div = TRUE;
                    continue;
                }
                //if ($this->isSingleVerb($previous_line)) { // not multi-line paired verb
                //    $remainder = $this->processVerb();
                //    $this->addToOutput($remainder);
                //    continue;
                //}
                if (substr($this->previous_line,0,1) === '#') {
                    $this->addToOutput($this->previous_line);
                    $this->previous_line = '';
                    $this->current_character = ''; // headings only take one line
                    continue;
                };
                //echo("previous_character=$this->previous_character\n");
                if ($this->previous_character === "\n") {
                    $this->addToOutput($this->previous_line);
                    $this->previous_line = '';
                    continue;
                };
            }
            continue;

        };

        $this->formatted_text = trim($this->formatted_text);
        return ($this->formatted_text);
    }

    /**********************************************
    * helperHref()
    *------------------
    * Purpose: determine if character is a potential verb
    *------------------
    * params: <character> current character
    * params: <character> previous character
    * params: <string> current "line" (could be paragraph)
    *------------------
    * returns: <Boolean> TRUE if potential verb
    ***********************************************/
    private function helperHref ($current_character, $previous_character, $line) {
        $pattern = '/^\[.*?\]\(.*?\)$/'; // full pattern
    }

    /**********************************************
    * addToOutput()
    *------------------
    * Purpose: push formatted text to output
    *------------------
    * params: <string> text to add to output (can arrive as multiple lines)
    *------------------
    * returns: <Boolean> TRUE if successful
    ***********************************************/
    private function addToOutput ($line) {

        //--sanitization-------
        $line = trim($line);
        
        //--error checking-------- 
        if ($this->is_pre) {
            $this->formatted_text .= "<code>$line</code>\n";
            return TRUE;
        }

        //--end li items (+ in column 1)---------
        if ($this->is_ul && substr($line,0,1) !== '+') {
            $this->formatted_text .= "</ul>\n";
            $this->is_ul = FALSE;
        }    

        //--expand li items (+ in column 1)
        if (substr($line,0,1) === '+') {
            if ($this->is_ul === FALSE) {
                $this->formatted_text .= "<ul>\n";
                $this->is_ul = TRUE;
            };
            $line = trim(substr($line,1)); // bypass initial +
            $line = str_replace("\n+","</li>\n<li>",$line); // multiple lines fed here at once
            $this->formatted_text .= "<li>$line</li>\n";
            return TRUE; 
        }

        //--headings-------------
        $pattern = '|^(#+) (.*)|';
        preg_match($pattern, $line, $matches);

        if (isset($matches[1])) {
            $hn = strlen($matches[1]);
            $this->formatted_text .= '<h' . $hn . '>' . $matches[2] . '</h' . $hn . ">\n";
            return TRUE;
        }
        
        //--paragraph class name(s)--------
        $pattern = '|^(\{\s*?\.\w+?\s*?\})|'; // { .classname }
        preg_match($pattern, $line, $matches);
        $line = preg_replace($pattern, '', $line);
        $line = trim($line);
        $class = '';
        if (isset($matches[1])) {
            $class = $matches[1];
            $pattern = '|[{.}]|';
            $class = preg_replace($pattern, '', $class);
            $class = trim($class);
        }

        //--fancy apostrophe--------
        $pattern = '|(?<!\\\\)\'|';
        $line = preg_replace($pattern,"&apos;",$line);

        //--fancy quotes--------
        $pattern = '|(?<!\\\\)"(.*?)(?<!\\\\)"|';
        $line = preg_replace($pattern,"&ldquo;$1&rdquo;",$line);

        //--emdash--------
        $pattern = '|(?<!\\\\)(--)|';
        $line = preg_replace($pattern,"&mdash;",$line);

        //--remove backslashes--------
        $line = str_replace('\\','',$line);

        //--expand links and images (must follow fancy quotemarks)-------------
        $line = $this->expandLinksAndImages($line);

        //--is there output?---------
        if (!$line) {
            return FALSE; // but not really an error
        }

        //--send output---------
        $this->formatted_text .= "<p class=\"$class\">$line</p>\n";
        return TRUE;
    }


    /**********************************************
    * expandLinksAndImages()
    *------------------
    * Purpose: expand links and images 
    *------------------
    * params: $line
    *------------------
    * returns: <string> line, with expanded links and images
    ***********************************************/
    private function expandLinksAndImages ($line) {

        //--recognize href or image--------
        $pattern = '|^(!?)\[(\s*?.+?\s*?)\]\s*?\((\s*?.+?\s*?)\)\s*?(\{.*\})?|m'; // [ link name ] ( link_url )

        $line = preg_replace_callback($pattern, 
            function ($matches){
                $is_image = FALSE;
                $class = '';
                $caption = '';
                $title = '';
                $href = '';
                $image_link = '';
                $id = '';
                $pattern = '|\s*\{(.*?)\}|';
                if ($matches[1] === '!') {
                    $is_image = TRUE;
                }
                $title = $matches[2];
                $href = $matches[3];

                //--process any brace-delimited options-------------
                if (isset($matches[4])) { // brace delimited text
                    preg_match_all($pattern,$matches[4],$options);
                    foreach($options[1] as $option) { // brace delimited text
                        $option = trim($option);
                        $pattern = '|^(\w*)([.#:=])(.*)|'; // [verb] <separator> <value> 
                        preg_match($pattern,$option,$spec);
                        $verb = trim(strtolower($spec[1]));
                        if ($spec[2] === '.') {
                            $verb = 'class';
                        }
                        if ($spec[2] === '#') {
                            $verb = 'id';
                        }
                        $value = trim($spec[3]);
                        if ($verb === 'caption') {
                            $caption = $value;
                        }
                        if ($verb === 'href') {
                            $href = $value;
                        }
                        if ($verb === 'class') {
                            $value = str_replace('.', '', $value);
                            $class = $value;
                        }
                        if ($verb === 'id') {
                            $value = str_replace('#', '', $value);
                            $id = $value;
                        }
                    }
                }
                
                //--class and id clauses---------
                $class_and_id = '';
                if ($class) {
                    $class_and_id = 'class="' . $class . '" ';
                }
                if ($id) {
                    $class_and_id = 'id="' . $id . '" ';
                }

                //--construct html for image or link
                $html = '';
                if ($is_image) {
                    $html .= "<figure $class_and_id>\n";
                    $html .= "    <img src=\"$href\" alt=\"$title\" />\n";
                    if (!$caption) {
                        $caption = $title;
                    }
                    if ($caption) {
                        $html .= "    <figcaption>$caption</figcaption>\n";
                    }
                    $html .= "</figure>\n";
                } else {
                    $html .= "<a href=\"$href\" $class_and_id>$title</a>\n"; 
                }
                return ($html);
            },
            $line
        );

    return ($line);
    }

    /**********************************************
    * isPowerCharacter()
    *------------------
    * Purpose: determine if character is a potential verb
    *------------------
    * params: <character> current character
    * params: <character> previous character
    * params: <string> current "line" (could be paragraph)
    *------------------
    * returns: <Boolean> TRUE if potential verb
    ***********************************************/
    private function isPowerCharacter ($current_character, $previous_character, $line) {

        //--error checking------------
        if (!is_string($current_character)) {
            $error_found = TRUE;
            $error_text = 'current_character not a string';
            return FALSE;
        }

        if (strlen($current_character) !== 1) {
            $error_found = TRUE;
            $error_text = 'current_character must be single character';
            return FALSE;
        }

        if (!is_string($previous_character)) {
            $error_found = TRUE;
            $error_text = 'previous_character not a string';
            return FALSE;
        }

        if (strlen($previous_character) > 1) {
            $error_found = TRUE;
            $error_text = 'prevous_character must be single character';
            return FALSE;
        }

        if (!is_string($line)) {
            $error_found = TRUE;
            $error_text = 'line not a string';
            return FALSE;
        }

        //--backslash disengages verb--------------
        if ($previous_character === '\\') { // single backslash
            return FALSE;
        }

        //--is it a verb?--------------
        foreach($this->verbs as $verb) {
            if ($current_character === $verb->verb) {
                return TRUE;
            }
        }

        //--it was not a verb--------------
        return FALSE;
    }    

    /**********************************************
    * closePre()
    *------------------
    * Purpose: end pre tag
    *------------------
    * params: none
    *------------------
    * returns: NULL
    ***********************************************/
    private function closePre () {
        $this->previous_line = '';
        $this->is_pre = FALSE;
        $this->addToOutput("</pre>\n");
    }    

    /**********************************************
    * openPre()
    *------------------
    * Purpose: open pre tag
    *------------------
    * params: none
    *------------------
    * returns: NULL
    ***********************************************/
    private function openPre () {
        $this->previous_line = '';
        $this->addToOutput("<pre>\n");
        $this->is_pre = TRUE;
    }    

    /**********************************************
    * collectString()
    *------------------
    * Purpose: collect string, one character at a time
    *------------------
    * params: <character> verb character
    *------------------
    * returns: formatted text or FALSE on failure
    ***********************************************/
    private function collectString ($character) {
    }    

    /**********************************************
    * collectVerb()
    *------------------
    * Purpose: collect verb, one character at a time
    *------------------
    * params: <character> verb character
    *------------------
    * returns: formatted text or FALSE on failure
    ***********************************************/
    private function collectVerb ($character) {
    }    

    /**********************************************
    * isCompleteVerb()
    *------------------
    * Purpose: determine if current character completes a verb
    *------------------
    * params: <character> current character
    * params: <character> previous character
    * params: <string> current "line" (could be paragraph)
    *------------------
    * returns: <Boolean> TRUE if potential verb
    ***********************************************/
    private function isCompleteVerb ($current_character, $previous_character, $line) {
        
    }    

}

/*--eof */