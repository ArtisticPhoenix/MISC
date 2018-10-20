<?php
function parse($subject)
{
    $tokens = [
        'T_EOF'             => '\Z',
        'T_ARRAY_START'		=> 'array\(\d+\)\s\{',
        'T_ARRAY_END'		=> '}',
        'T_VALUE'			=> '".*?(?<!\\\\)"',
        'T_KEY'				=> '\[[^\]]+\]',
        'T_ARROW'			=> '=>',
        'T_TYPE'			=> 'string\(\d+\)',
        'T_WHITESPACE'		=> '\s+',
        'T_UNKNOWN'         => '.+?'
    ];
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
    $key = false;
    while($current = current($lexer_stream)){
        $content = $current['content'];
        $type = $current['type'];
        //var_export($result);
        switch($type){
            case 'T_EOF': return;
            case 'T_WHITESPACE' :
            case 'T_ARROW' :
                next($lexer_stream);
                break;
                
            case 'T_TYPE' :
                next($lexer_stream);
                break;
                
            case 'T_ARRAY_START' :
                next($lexer_stream);
                if(false == $key) return $result[] = parseTokens($lexer_stream);
                else $result[$key] = parseTokens($lexer_stream);
                break;
            case 'T_KEY' :
                next($lexer_stream);
                $key = trim($content,'[]');
                break;
            case 'T_VALUE' :
                next($lexer_stream);
                $result[$key] = trim($content,'"');
                break;
            case 'T_ARRAY_END' :
                next($lexer_stream);
                return $result;
                break;
            case 'T_COMMA' :
            case 'T_VALUE' :
                
            case 'T_UNKNOWN':
            default:
                print_r($current);
                trigger_error("Unknown token $type value $content", E_USER_ERROR);
        }
    }
    if( !$current ) return;
    var_export($current);
    trigger_error("Unclosed item $mode for $type value $content", E_USER_ERROR);
}

/*


$subject = <<<CODE
array(2) {
  [0]=>
  array(2) {
    ["id"]=>
    string(11) "43000173601"
    ["data"]=>
    array(2) {
      [0]=>
      array(2) {
        ["id"]=>
        string(5) "52874"
        ["name"]=>
        string(3) "x70"
      }
      [1]=>
      array(2) {
        ["id"]=>
        string(5) "52874"
        ["name"]=>
        string(3) "x70"
      }
    }
  }
  [1]=>
  array(2) {
    ["id"]=>
    string(11) "43000173602"
    ["data"]=>
    array(1) {
      [0]=>
      array(2) {
        ["id"]=>
        string(5) "52874"
        ["name"]=>
        string(3) "x70"
      }
    }
  }
}
CODE;

var_export( parse($subject, $tokens) );

 */