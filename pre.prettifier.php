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

    /**
    * Public vars
    */
    public $error_found;
    public $error_text;

    /**
    * __construct method
    */
    public function __construct () { // surprisingly this must be public
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
        if (!is_string($text)) {
            $this->error_found = TRUE;
            $this->error_text = 'requires string';
            return (FALSE);
        };

        $formatted_text = '';
        $is_sub_heading = FALSE;
        $is_pre_heading = FALSE;
        $is_raw_html = FALSE;
        $is_pre = FALSE;
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
        $line_trained_text .= "\n";

        for ($i=0; $i<strlen($line_trained_text); $i++) {
            $previous_character = $current_character;
            $current_character = substr($line_trained_text, $i, 1);
            //if ($i===0 && $current_character <> '#') {
            //    $formatted_text .= '<p>';
            //    $is_paragraph = TRUE;
            //};
            
            //--end paragraph----------
            if ($current_character === "\n" && $previous_character === "\n" && $is_paragraph) {
                $formatted_text .= "</p>\n";
                $is_paragraph = FALSE;
                continue;
            }

            //--end <h1> to <h6>----------
            if ($current_character === "\n" && $is_header) {
                $formatted_text .= "</h$h_counter>\n";
                $h_counter = 0;
                $is_header = FALSE;
                continue;
            }

            //--begin <h1> to <h6>----------
            if ($current_character === "#" && ($previous_character === "\n" || $i===0)) {
                $h_counter = 1;
                continue;
            }

            //--count <h2> to <h6>----------
            if ($current_character === "#" && $previous_character === "#" && $h_counter && !$is_header) {
                $h_counter++;
                if ($h_counter <= 6) {
                    continue;
                } 
                // if more than 6 then abandon <h*> attempt
                $formatted_text .= '#######'; // 7
                $h_counter = 0;
            }

            //--start <h*> tag----------
            if ($current_character !== "#" && $previous_character === "#" && $h_counter && !$is_header) {
                $formatted_text .= "<h$h_counter>";
                $is_header = TRUE;
            }

            //--end triple backtick pre----------------
            if ($current_character === "\n" && $previous_character === "`" && $is_pre) {
                $is_pre = FALSE;
                $formatted_text .= "</pre>\n";
                continue;
            };     

            if ($current_character === "`" && $is_pre) {
                continue; // expecting three backticks; but any number will end pre section
            };     

            //--begin triple backtick pre----------------
            if ($current_character === "`" && ($previous_character === "\n" || $i===0) && !$is_pre) {
                $backtick_counter = 1;
                continue;
            }

            if ($current_character === "`" && $previous_character === "`" && $backtick_counter && !$is_pre) {
                $backtick_counter++;
                if ($backtick_counter <= 3) {
                    continue;
                } 
                // if more than 3 then abandon <pre> attempt
                $formatted_text .= '````'; // 4
                $backtick_counter = 0;
            }

            //--start backtick <pre> tag----------
            if ($current_character !== "`" && $previous_character === "`" && $backtick_counter===3 && !$is_pre) {
                $formatted_text .= "<pre>\n";
                $is_pre = TRUE;
                $backtick_counter = 0;
                if ($current_character === "\n") {
                    continue;
                }
            }

            //--begin pre----------------
            if ($current_character === "`" && $previous_character === "`" && !$is_pre) {
                $is_pre = TRUE;
                $formatted_text .= "<pre>\n";
                continue;
            };     

            if ($current_character === "\n" && $previous_character === '~' && !$is_code) {
                continue;
            };     

            //--end pre----------------
            if ($current_character === '~' && $previous_character === '~' && $is_pre) {
                $is_pre = FALSE;
                $formatted_text .= "</pre>\n";
                continue;
            };     

            if ($current_character === "\n" && $previous_character === '~' && !$is_pre) {
                continue;
            };     

            //--begin code----------------
            if ($current_character !== '~' && $is_pre && !$is_code) {
                $is_code = TRUE;
                $formatted_text .= "<code>";
            };     

            //--end code----------------
            if ($current_character === "\n" && $previous_character === "\n" && $is_code) {
                $is_code = FALSE;
                $formatted_text .= "&nbsp;</code>\n";
                continue;
            };

            if ($current_character === "\n" && $is_code) {
                $is_code = FALSE;
                $formatted_text .= "</code>\n";
                continue;
            };

            //--begin raw html----------------
            if ($current_character === '%' && $previous_character === '%' && !$is_raw_html) {
                $is_raw_html = TRUE;
                continue;
            };     

            //--end raw html----------------
            if ($current_character === '%' && $previous_character === '%' && $is_raw_html) {
                continue;
            };     

            if ($current_character === "\n" && $previous_character === '%' && $is_raw_html) {
                $is_raw_html = FALSE;
                continue;
            };     

            //--begin pre heading----------------
            if ($current_character === '{' && $previous_character === '{' && !$is_pre_heading) {
                $is_pre_heading = TRUE;
                $formatted_text .= '<div class="pre_heading">';
                continue;
            };     

            //--end pre heading----------------
            if ($current_character === '}' && $previous_character === '}' && $is_pre_heading) {
                $is_pre_heading = FALSE;
                $formatted_text .= "</div>\n";
                continue;
            };     

            if ($current_character === "\n" && $previous_character === '}' && !$is_pre_heading) {
                continue;
            };     

            //--begin sub-heading----------------
            if ($current_character === '^' && $previous_character === '^' && !$is_sub_heading) {
                $is_sub_heading = TRUE;
                $formatted_text .= '<div class="sub_heading">';
                continue;
            };     

            //--end sub-heading----------------
            if ($current_character === '^' && $previous_character === '^' && $is_sub_heading) {
                $is_sub_heading = FALSE;
                $formatted_text .= "</div>\n";
                continue;
            };     

            if ($current_character === "\n" && $previous_character === '^' && !$is_sub_heading) {
                continue;
            };     

            //--begin pull quote----------------
            if ($current_character === '|' && $previous_character === '|' && !$is_pull_quote) {
                $is_pull_quote = TRUE;
                $formatted_text .= '<div class="pull_quote">';
                continue;
            };     

            //--end pull quote----------------
            if ($current_character === '|' && $previous_character === '|' && $is_pull_quote) {
                $is_pull_quote = FALSE;
                $formatted_text .= "</div>\n";
                continue;
            };     

            if ($current_character === "\n" && $previous_character === '|' && !$is_pull_quote) {
                continue;
            };     

            //--begin code fragment----------------
            if ($current_character === '$' && $previous_character === '$' && !$is_code_fragment) {
                $is_code_fragment = TRUE;
                $formatted_text .= '<span class="code_fragment">';
                continue;
            };     

            //--end code fragment----------------
            if ($current_character === '$' && $previous_character === '$' && $is_code_fragment) {
                $formatted_text .= "</span>\n";
                $is_code_fragment = FALSE;
                $current_character = ''; // hack to prevent restart of code_fragment
                continue;
            };     

            //--power character preservation----------
            if ($current_character !== '`' && $previous_character === '`' && !$is_pre) {
                $formatted_text .= '`';
            };
                
            if ($current_character !== '~' && $previous_character === '~' && !$is_pre) {
                $formatted_text .= '~';
            };
                
            if ($current_character !== '{' && $previous_character === '{' && !$is_pre_heading) {
                $formatted_text .= '{';
            };

            if ($current_character !== '}' && $previous_character === '}' && !$is_pre_heading) {
                $formatted_text .= '}';
            };

            if ($current_character !== '^' && $previous_character === '^' && !$is_sub_heading) {
                $formatted_text .= '^';
            };
                
            if ($current_character !== '|' && $previous_character === '|' && !$is_pull_quote) {
                $formatted_text .= '|';
            };
                
            if ($current_character !== '%' && $previous_character === '%' && !$is_raw_html) {
                $formatted_text .= '%';
            };
                
            if ($current_character !== '$' && $previous_character === '$' && !$is_code_fragment) {
                $formatted_text .= '$';
            };

            //--delay processing of power characters----------
            if ($current_character === '`') {
                continue;
            };

            if ($current_character === '~') {
                continue;
            };

            if ($current_character === '{') {
                continue;
            };

            if ($current_character === '}') {
                continue;
            };

            if ($current_character === "^") {
                continue;
            };

            if ($current_character === "|") {
                continue;
            };

            if ($current_character === "%") {
                continue;
            };

            if ($current_character === "$") {
                continue;
            };

            //--single newlines disappear; but leave at least a space----------
            if ($current_character === "\n") {
                if (strpos("\n ",substr($formatted_text,-1)) === FALSE) {
                    $formatted_text .= ' ';
                }    
                continue;
            };

            //--start paragraph----------
            if (!$is_pre && !$is_paragraph && !$is_pre_heading && !$is_sub_heading && !$is_pull_quote && !$h_counter) {
                $is_paragraph = TRUE;
                $formatted_text .= "<p>";
            };

            //--handle quotations--------
            if ($is_paragraph && !$is_raw_html && $current_character === '"') {
                if ($is_quoting) {
                    //$formatted_text .= '&rdquo;'; // close quote
                    $formatted_text .= '</q>'; // close quote
                    $is_quoting = FALSE;
                } else {
                    //$formatted_text .= '&ldquo;'; // open quote
                    $formatted_text .= '<q>'; // open quote
                    $is_quoting = TRUE;
                };
                continue;
            };

            if ($is_paragraph && $current_character === "'") {
                    $formatted_text .= '&apos;'; // single apostrophe
                    continue;
            };    

            //--handle em dash--------
            if ($is_paragraph && $current_character === '-' && $previous_character !== '-') {
                continue;
            };

            if ($is_paragraph && $current_character === "-" && $previous_character === "-") {
                $formatted_text .= '&mdash;'; // emit em dash (and done with current item)
                $was_em_dash = TRUE; 
                continue;
            };    

            if ($is_paragraph && $current_character !== "-" && $previous_character === "-" && !$was_em_dash) {
                $formatted_text .= '-'; // emit hyphen (and whatever is actually in queue)
            };    

            if ($is_paragraph && $current_character !== "-" && $previous_character === "-" && $was_em_dash) {
                $was_em_dash = FALSE;
            };    

            //--accept character----------
            $formatted_text .= $current_character;
        };

        $formatted_text = trim($formatted_text);
        return ($formatted_text);
    }

}

/*--eof */