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
    
        $column = '';
        $params = [];
        $sql = '';
    
        while($current = current($lexer_stream)){
            $content = $current['content'];
            $type = $current['type'];
            switch($type){
                case 'T_WHITESPACE':
                case 'T_COMPARISON':
                case 'T_PAREN_OPEN':
                case 'T_PAREN_CLOSE':
                case 'T_COMMA':
                case 'T_SYMBOL':
                    $sql .= $content;
                    next($lexer_stream);
                break;
                case 'T_COLUMN':
                    $column = $content;
                    $sql .= $content;
                    next($lexer_stream);
                break;
                case 'T_OPPERATOR':
                case 'T_NULL':
                    $column = '';
                    $sql .= $content;
                    next($lexer_stream);
                break;
                case 'T_ENCAP_STRING': 
                case 'T_NUMBER':
                    if(empty($column)){
                        throw new Exception('Parse error, value without a column name', 2001);
                    }
                    
                    $value = trim($content,"'");
                       
                    $palceholder = createPlaceholder($column, $value, $params);
                    
                    $params[$palceholder] = $value;
                    $sql .= $palceholder;
                    next($lexer_stream);
                break;
                case 'T_IN':
                    $sql .= $content;
                    parseIN($column, $lexer_stream, $sql, $params);
                break;
                case 'T_EOF': return ['params' => $params, 'sql' => $sql];
                
                case 'T_UNKNOWN':
                case '':
                default:
                    $content = htmlentities($content);
                    print_r($current);
                    throw new Exception("Unknown token $type value $content", 2000);
            }
        }
    }
    
    function createPlaceholder($column, $value, $params){
        $placeholder = ":{$column}";
        
        $i = 1;
        while(isset($params[$placeholder])){
            
            if($params[$placeholder] == $value){
                break;
            }
            
            $placeholder = ":{$column}_{$i}";
            ++$i;
        }
        
        return $placeholder;
    }
    
    function parseIN($column, &$lexer_stream, &$sql, &$params){
        next($lexer_stream);
        
        while($current = current($lexer_stream)){
            $content = $current['content'];
            $type = $current['type'];
            switch($type){
                case 'T_WHITESPACE':
                case 'T_COMMA':
                    $sql .= $content;
                    next($lexer_stream);
                break; 
                case 'T_ENCAP_STRING':
                case 'T_NUMBER':
                    if(empty($column)){
                        throw new Exception('Parse error, value without a column name', 2001);
                    }
                    
                    $value = trim($content,"'");
                    
                    $palceholder = createPlaceholder($column, $value, $params);
                    
                    $params[$palceholder] = $value;
                    $sql .= $palceholder;
                    next($lexer_stream);
                break;    
                case 'T_PAREN_CLOSE':
                    $sql .= $content;
                    next($lexer_stream);
                    return;
                break;          
                case 'T_EOL':
                    throw new Exception("Unclosed call to IN()", 2003);
    
                case 'T_UNKNOWN':
                default:
                    $content = htmlentities($content);
                    print_r($current);
                    throw new Exception("Unknown token $type value $content", 2000);
            }
        }
        throw new Exception("Unclosed call to IN()", 2003);
    }
    
    /**
     * token should be "name" => "regx"
     * 
     * Order is important
     * 
     * @var array $tokens
     */
    $tokens = [
        'T_WHITESPACE'      => '[\r\n\s\t]+',
        'T_ENCAP_STRING'    => '\'.*?(?<!\\\\)\'',
        'T_NUMBER'          => '\-?[0-9]+(?:\.[0-9]+)?',
        'T_BANNED'          => 'SELECT|INSERT|UPDATE|DROP|DELETE|ALTER|SHOW',
        'T_COMPARISON'      => '=|\<|\>|\>=|\<=|\<\>|!=|LIKE',
        'T_OPPERATOR'       => 'AND|OR',
        'T_NULL'            => 'IS NULL|IS NOT NULL',
        'T_IN'              => 'IN\s?\(',
        'T_COLUMN'          => '[a-z_]+',
        'T_COMMA'           => ',',
        'T_PAREN_OPEN'      => '\(',
        'T_PAREN_CLOSE'      => '\)',
        'T_SYMBOL'          => '[`]',
        'T_EOF'             => '\Z',
        'T_UNKNOWN'         => '.+?'
    ];
    
    $tests = [
        "title = 'home' and description = 'this is just an example'",
        "title = 'home' OR title = 'user'",
        "id = 1 or title = 'home'",
        "title IN('home','user', 'foo', 1, 3)",
        "title IS NOT NULL",
    ];
    
    /* the loop here is for testing only, obviously call it one time */
    foreach ($tests as $test){   
        print_r(parse($test,$tokens));
        echo "\n".str_pad(" $test ", 100, "=", STR_PAD_BOTH)."\n";  
    }


echo "Complete";
