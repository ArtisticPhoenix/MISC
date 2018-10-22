<?php

/*
 (c) 2018 Hugh Durham III {ArtisticPhoenix}
 
 For license information please view the LICENSE file included with this source code. (GNU/GPL3)
*/

class Converter{
    
    const VAR_EXPORT = 'var_export';
    const VAR_DUMP = 'var_dump';
    const PRINT_R = 'print_r';
    
    protected function tokenize($subject, $tokens){
        $types = array_keys($tokens);
        $patterns = [];
        $tokenStream = [];
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
                $tokenStream[] = $tok;
            }
        }
        return $tokenStream;
    }

    
    public function detectOutputType($string){       
        if(preg_match('/^(bool|int|float|string|array|object|resource)\s*\([^)]\)\s*{/', $string)) return self::VAR_DUMP;
        if(preg_match('/^(Array|\w+\s*Object)\s*\(/', $string)) return self::PRINT_R;
        return self::VAR_EXPORT;
    }
    
    
    public function convertFromVarDump($string){
        $tokens = [
            'T_EOF'             => '\Z',
            'T_ARRAY'		    => 'array\(\d+\)\s*\{',
            'T_STRING'			=> 'string\(\d+\)\s*".*?(?<!\\\\)"',
            'T_BOOL'            => 'bool\((?:false|true)\)',
            'T_INT'             => 'int\(\d+\)',
            'T_FLOAT'           => 'float\(\d+\.\d+\)',
            'T_OBJECT'          => 'object\([\w\\\]+\)#\d+\s*\(\d+\)\s*{',
            'T_RESOURCE'        => 'resource\(\d+\)',
            'T_ARROW'			=> '=>',
            'T_NEST_END'		=> '}',
            'T_KEY'				=> '\[[^\]]+\]',
            'T_WHITESPACE'		=> '\s+',
            'T_UNKNOWN'         => '.+?'
        ];
        
        $tokenStream = $this->tokenize($string, $tokens);
        //print_r($tokenStream);
        return $this->parseVarDumpTokens($tokenStream);
    }
    
    function parseVarDumpTokens( array &$tokenStream, $nesting = 0, $line=0 ){
        $result = [''];
        $n = "\n";
        $t = "    ";
        $eidx = 0;
        while($current = current($tokenStream)){
            //echo $result."\n";
            $content = $current['content'];
            $type = $current['type'];
            //var_export($current); echo "\n\n";
            switch($type){
                case 'T_WHITESPACE':
                    //skip free whitespace
                    next($tokenStream);
                break;
                case 'T_EOF': return trim($result[0]);   
                case 'T_ARROW':
                    next($tokenStream);
                    $result[$eidx] .= " => ";
                break;
                case 'T_NEST_END':
                    next($tokenStream);
                    $eidx = 0;
                    return str_repeat($t, $nesting).implode(','.$n.str_repeat($t, $nesting), $result);
                break;
                case 'T_KEY':
                    next($tokenStream);
                    $result[$eidx] = trim($content,'[]');
                break;
                case 'T_ARRAY':
                    next($tokenStream);
                    $result[$eidx] .=  $n.str_repeat($t, $nesting).'array ('.$n;
                    ++$nesting;
                    $result[$eidx] .= $this->parseVarDumpTokens($tokenStream, $nesting).$n;
                    --$nesting;
                    $result[$eidx] .=  str_repeat($t, $nesting).')';
                break;
                case 'T_OBJECT':
                    next($tokenStream);
                     preg_match('/object\((\w+)\)/i', $content, $match);
                     $result[$eidx] .= $match[1].'::__set_state(array('.$n;
                     ++$nesting;
                     $result[$eidx] .= $this->parseVarDumpTokens($tokenStream, $nesting).$n;
                     --$nesting;
                     $result[$eidx] .=  str_repeat($t, $nesting).'))';
                break;
                case 'T_STRING':
                case 'T_BOOL':
                case 'T_INT':
                case 'T_FLOAT':
                case 'T_RESOURCE':
                    next($tokenStream);
                    switch($type){
                        case 'T_BOOL':
                            preg_match('/bool\((false|true)\)/i', $content, $match);
                            $value = $match[1];
                        break;
                        case 'T_STRING':
                            preg_match('/string\(\d+\)\s*"(.*?(?<!\\\\))"/i', $content, $match);
                            $value = var_export((string)$match[1],true); //let var_export escape the string
                        break;
                        case 'T_INT':
                            preg_match('/int\((\d+)\)/i', $content, $match);
                            $value = (int)$match[1];
                        break;
                        case 'T_FLOAT':
                            preg_match('/float\((\d+\.\d+)\)/i', $content, $match);
                            $value = (float)$match[1];
                        break;
                        case 'T_RESOURCE':
                            $value = 'NULL';
                        break;
                    }
                    $result[$eidx] .= $value;
                    ++$eidx;
                break;
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
    
    public function convertFromPrintR($string){
        $tokens = [
            'T_EOF'             => '\Z',
            'T_ARRAY'		    => 'Array\s*\(',
            'T_KEY'             => '\[[^\]]+\]\s*=>\s*',
            'T_OBJECT'          => '[\w\\\]+\s+Object\s*\(',
            'T_RESOURCE'        => 'Resource id #\d+',
            'T_NUMBER'          => '\d+(?:\.\d+)?',
            'T_STRING'          => '\w+',    
            'T_OPEN_PAREN'      => '\(',
            'T_CLOSE_PAREN'     => '\)',
            'T_WHITESPACE'		=> '\s+',
            'T_UNKNOWN'         => '.+?'
        ];
        
        $tokenStream = $this->tokenize($string, $tokens);
        //print_r($tokenStream);
        return $this->parsePrintRTokens($tokenStream);
    }
    
    
    function parsePrintRTokens(array &$tokenStream,$nesting = 0,$num_paren=0){
        $result = [''];
        $n = "\n";
        $t = "    ";
        $eidx = 0;
        $num_paren = 0;
        $buffer = '';
        while($current = current($tokenStream)){         
            $content = $current['content'];
            $type = $current['type'];
            switch($type){
                case 'T_WHITESPACE':
                    //skip free whitespace
                    next($tokenStream);
                break;
                case 'T_EOF':                   
                    return trim($result[0]);
                break;
                case 'T_ARRAY':
                    next($tokenStream);
                    $result[$eidx] .=  $n.str_repeat($t, $nesting).'array ('.$n;
                    ++$num_paren;
                    ++$nesting;
                    $result[$eidx] .= $this->parsePrintRTokens($tokenStream, $nesting, 1).$n;
                    --$nesting;
                    $result[$eidx] .=  str_repeat($t, $nesting).')';
                break;
                case 'T_OBJECT':
                    next($tokenStream);
                    preg_match('/([\w\\\]+)\s+Object/', $content, $match);
                    $result[$eidx] .= $match[1].'::__set_state(array('.$n;
                    ++$num_paren;
                    ++$nesting;
                    $result[$eidx] .= $this->parsePrintRTokens($tokenStream, $nesting, 1).$n;
                    --$nesting;
                    $result[$eidx] .=  str_repeat($t, $nesting).'))';
                 break;
                case 'T_STRING':
                    next($tokenStream);
                    $buffer .=  $content;
                break;
                case 'T_OPEN_PAREN':
                    next($tokenStream);
                    $buffer .=  '(';
                    ++$num_paren;
                break;
                case 'T_CLOSE_PAREN':                  
                    next($tokenStream);
                    --$num_paren;
                    if($num_paren < 1){
                        $result[$eidx] .= $this->multiCast($buffer);
                        return str_repeat($t, $nesting).implode(','.$n.str_repeat($t, $nesting), $result);
                    }else{
                        $buffer .=  ')';
                    }
                break;
                case 'T_RESOURCE':
                    next($tokenStream);
                    $buffer .=  'NULL';
                break;
                case 'T_KEY':
                    next($tokenStream);
                    if(preg_match('/^(\[(?:[^\]\[]+|(?1))*+\])/', trim($content), $match)){
                        if(strlen($buffer)){
                             $result[$eidx] .= $this->multiCast($buffer);
                             $buffer = '';
                             ++$eidx;
                        }
                        
                        $result[$eidx] = $this->multiCast(trim($match[1],'][')).' => ';
                    }else{
                        $buffer .= $content;
                    }
               break;
               case 'T_STRING':
               case 'T_NUMBER':
               case 'T_UNKNOWN':
                   next($tokenStream);
                   $buffer .= $content;
               break;
               default:
                    print_r($current);
                    trigger_error("Unknown token[".key($tokenStream)."] $type value $content", E_USER_ERROR);
            }
        }
        if( !$current ) return;
        var_export($current);
        trigger_error("Unclosed item $mode for $type value $content", E_USER_ERROR);
    }
    
    protected function multiCast($var){
        switch (strtolower(trim($var))){
            case '' : $value = null; break;
            case 'null': 
            case 'true':  
            case 'false': 
                $value = $var;
            break;
            default:
                if(is_numeric($var)){
                    $value = $var;
                }else{
                    $value = var_export(preg_replace('/^"(.+)"$/','\1',$var),true);
                }
        }
        return $value;
    }
    
}