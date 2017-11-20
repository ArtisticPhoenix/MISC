<?php

//For debugging
error_reporting(-1);
ini_set('display_errors', 1);
echo "<pre>";

function parseJson($subject, $tokens)
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
        $result = parseJsonTokens( $lexer_stream );
    }
    return $result;
}
function parseJsonTokens( array &$lexer_stream ){
    $result = [];
    next($lexer_stream); //advnace one
    $mode = 'key'; //items start in key mode  ( key => value )
    $key = '';
    $value = '';
    while($current = current($lexer_stream)){
        $content = $current['content'];
        $type = $current['type'];
        switch($type){
            case 'T_WHITESPACE'://ignore whitespace
                next($lexer_stream);
                break;
            case 'T_STRING':
                //keys are always strings, but strings are not always keys
                if( $mode == 'key')
                    $key .= $content;
                    else
                        $value .= $content;
                        next($lexer_stream); //consume a token
                        break;
            case 'T_COLON':
                $mode = 'value'; //change mode key :
                next($lexer_stream);//consume a token
                break;
            case 'T_ENCAP_STRING':
                if( $mode == 'key'){
                    $key .= trim($content,'"');
                }else{
                    $value .= unicode_decode($content); //encapsulated strings are always content
                }
                next($lexer_stream);//consume a token
                break;
            case 'T_COMMA':  //comma ends an item
                //store
                $result[$key] = $value;
                //reset
                $mode = 'key'; //items start in key mode  ( key => value )
                $key = '';
                $value = '';
                next($lexer_stream);//consume a token
                break;
            case 'T_OPEN_BRACE': //start of a sub-block
                $value = parseJsonTokens($lexer_stream); //recursive
                break;
            case 'T_CLOSE_BRACE': //start of a sub-block
                //store
                $result[$key] = $value;
                next($lexer_stream);//consume a token
                return $result;
                break;
            case 'T_OPEN_BRACKET': //start of a sub-block  
                next($lexer_stream);//consume a token
                $value = parse_array($lexer_stream);
            break;
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
            case 'T_WHITESPACE'://ignore whitespace
                next($lexer_stream);
            break;
            case 'T_STRING':
            case 'T_ENCAP_STRING':
                $v .= $content;
                 next($lexer_stream);
            break;
            case 'T_COMMA':
                $value[] = $v;
                $v ='';
                 next($lexer_stream);
            break;   
            case 'T_OPEN_BRACKET':
                next($lexer_stream);
                $value[] = parse_array($lexer_stream); //recursive
            break;
            case 'T_CLOSE_BRACKET':
                if(!empty($v)) $value[] = $v;
                next($lexer_stream);
                return $value;
            default:
                print_r($current);
                trigger_error("Unknown token $type value $content", E_USER_ERROR);
        }
    }
}
//https://stackoverflow.com/questions/2934563/how-to-decode-unicode-escape-sequences-like-u00ed-to-proper-utf-8-encoded-cha
function replace_unicode_escape_sequence($match) {
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}
function unicode_decode($str) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
}
$str = '{
    "party":"bases",
    number:"1",
    id:"xx_3039366",
    url:"systen01-ny.com",
    target:"_self",
    address:"Ch\u00e3o as Alminhas-Medas,Uteiros de Gatos e Fontes Longaq<br/>64320-761 ANHADOS LdA",
    coordinate:{
        x:90.995262145996094,
        y:-1.3394836426
    },
    contactDetails:{
        id:"366",
        phone:"xxxxxx",
        mobile:"",
        fax:"xxxx 777 235",
        c2c:!0
    },
    parameters:"Flex Am\u00e1vel Silva,hal,,EN_30336,S,786657,1,0,",
    text:"Vila Nova de Loz C\u00f4a,os melhores vinhos, v\u00e1rias. Produtor/exportador/com\u00e9rcio",
    website:null,
    mail:"",
    listing:"paid",
    pCode:"64",
    name:"xpto Am\u00e1vel Costa",
    logo:{src:"http://ny.test.gif",
    altname:"xpto Am\u00e1vel Costa"},
    bookingUrl:"",
    ipUrl:"",
    ipLabel:"",
    customerId:"7657",
    addressId:"98760",
    combined:null,
    showReviews:!0,
    array: [
     1,
     2,
     3,
     [1,2,3]
    ]
}';
$tokens = [
    'T_OPEN_BRACE'      => '\{',
    'T_CLOSE_BRACE'     => '\}',
    'T_OPEN_BRACKET'      => '\[',
    'T_CLOSE_BRACKET'     => '\]',
    'T_ENCAP_STRING'    => '\".*?(?<!\\\\)\"',
    'T_COLON'           => ':',
    'T_COMMA'           => ',',
    'T_STRING'          => '[-a-z0-9_.!]+',
    'T_WHITESPACE'      => '[\r\n\s\t]+',
    'T_UNKNOWN'         => '.+?'
];
print_r( parseJson($str, $tokens) );

//OUPUTS
/*
Array
(
    [party] => "bases"
    [number] => "1"
    [id] => "xx_3039366"
    [url] => "systen01-ny.com"
    [target] => "_self"
    [address] => "Chão as Alminhas-Medas,Uteiros de Gatos e Fontes Longaq<br/>64320-761 ANHADOS LdA"
    [coordinate] => Array
        (
            [x] => 90.995262145996094
            [y] => -1.3394836426
        )

    [contactDetails] => Array
        (
            [id] => "366"
            [phone] => "xxxxxx"
            [mobile] => ""
            [fax] => "xxxx 777 235"
            [c2c] => !0
        )

    [parameters] => "Flex Amável Silva,hal,,EN_30336,S,786657,1,0,"
    [text] => "Vila Nova de Loz Côa,os melhores vinhos, várias. Produtor/exportador/comércio"
    [website] => null
    [mail] => ""
    [listing] => "paid"
    [pCode] => "64"
    [name] => "xpto Amável Costa"
    [logo] => Array
        (
            [src] => "http://ny.test.gif"
            [altname] => "xpto Amável Costa"
        )

    [bookingUrl] => ""
    [ipUrl] => ""
    [ipLabel] => ""
    [customerId] => "7657"
    [addressId] => "98760"
    [combined] => null
    [showReviews] => !0
    [array] => Array
        (
            [0] => 1
            [1] => 2
            [2] => 3
            [3] => Array
                (
                    [0] => 1
                    [1] => 2
                    [2] => 3
                )

        )

)

http://sandbox.onlinephpfunctions.com/code/b2917e4bb8ef847df97edbf0bb8f415a10d13c9f
 */
