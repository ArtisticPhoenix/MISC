<?php

//For debugging
error_reporting(-1);
ini_set('display_errors', 1);
echo "<pre>";

function parse($subject, $tokens)
{
    $types = array_keys($tokens);
    $patterns = [];
    $lexer_stream = [];
    $result = false;
    foreach ($tokens as $k=>$v){
        $patterns[] = "(?P<$k>$v)";
    }
    $pattern = "/".implode('|', $patterns)."/i";
    if (preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE)) {
        //print_r($matches);
        foreach ($matches[0] as $key => $value) {
            $match = [];
            foreach ($types as $type) {
                $match = $matches[$type][$key];
                if (is_array($match) && $match[1] != -1) {
                    break;
                }
            }
            $tok  = [
                'content' => $match[0],
                'type' => $type,
                'offset' => $match[1]
            ];
            $lexer_stream[] = $tok;
        }
        $result = parseTokens( $lexer_stream );
    }
    return $result;
}
function parseTokens( array &$lexer_stream ){
    $result = [];

    while($current = current($lexer_stream)){
        $content = $current['content'];
        $type = $current['type'];
        switch($type){
            default:
                print_r($current);
                trigger_error("Unknown token $type value $content", E_USER_ERROR);
        }
    }
    if( !$current ) return;
    print_r($current);
    trigger_error("Unclosed item $mode for $type value $content", E_USER_ERROR);
}

function parse_array(array &$lexer_stream){
    $value = array();
    $v='';
    while($current = current($lexer_stream)){
        $content = $current['content'];
        $type = $current['type'];
        switch($type){
            case 'T_EOF':
                
            return;
            case 'T_UNKNOWN':
            default:
                print_r($current);
                trigger_error("Unknown token $type value $content", E_USER_ERROR);
        }
    }
}

/**
 * token should be "name" => "regx"
 * 
 * Order is important
 * 
 * @var array $tokens
 */
$tokens = [

    'T_EOF'             => '\Z',
    'T_UNKNOWN'         => '.+?'
];

