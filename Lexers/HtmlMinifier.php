<?php

//For debugging
error_reporting(-1);
ini_set('display_errors', 1);


class Minifier{
    
    /**
     * not within an HTML tag
     * @var string
     */
    const MODE_CLOSED = 'closed';
    
    /**
     * withing an html tag
     * @var string
     */
    const MODE_OPEN = 'open';
    
    /**
     * ignore whitespace tell the next close tag
     * @var string
     */
    const MODE_IGNORE = 'ignore';
    
    /**
     * HTML tags to ingnore whitespace on
     * @var array
     */
    protected $ignoreTags = [
        'script',
        'style'
    ];
    
    /**
     * Regex tokens
     * @var array
     */
    protected $tokens =  [
        'T_EOF'             => '\Z',                            //matches end of string
        'T_COMMENT'         => '<(?=!--).+(?<=--)>',            //matches only <!-- comment -->
        'T_OPEN_TAG'        => '<(?!\/)[^>]+(?<!\/)>',          //matches only <tag ... >
        'T_CLOSE_TAG'       => '<(?=\/)[^>]+(?<!\/)>',          //matches only </tag ..>
        'T_INLINE_TAG'      => '<(?!\/)[^>]+(?<=\/)>',          //matches only <tag ... />
        'T_ENCAPSED_STRING' => '(?P<Q>\'|").*?(?<!\\\\)\k<Q>',   //matches  "foo\"bar" or 'foo\'bar'
        'T_STRING'          => '[-\w]+',                        //matches -0-9a-z
        'T_WHITESPACE'      => '\s+',                           //matches \s\t\r\n
        'T_UNKNOWN'         => '.+?'                            //matches everything else 
    ];
    
    /**
     * 
     * @param mixed $ignoreTags
     */
    public function __construct($ignoreTags = ''){
        $this->setTag($ignoreTags);
    }
    
    /**
     * 
     * @param mixed $ignoreTags
     * @return bool
     */
    public function issetTag($ignoreTags)
    {
        return in_array($ignoreTags,$this->ignoreTags);
    }
    
    /**
     * Set one or more tags
     * 
     * @param mixed $ignoreTags 
     */
    public function setTag($ignoreTags)
    {
        if(empty($ignoreTags)) return;
        if(!is_array($ignoreTags)) $ignoreTags = [$ignoreTags];     
        $this->ignoreTags = array_unique(array_merge($this->ignoreTags, $ignoreTags));   
    }
    
    /**
     * Set one or more tags
     *
     * @param mixed $ignoreTags
     */
    public function unsetTag($ignoreTags)
    {
        $index = array_search($ignoreTags, $this->ignoreTags);
        if(false !== $index) unset($this->ignoreTags[$index]);
    }
    /**
     * 
     * @param string $html
     * @return string
     */
    public function minify($html)
    {
        $token_stream = $this->lexTokens($html);
        return $this->parseTokens($token_stream);
    }
       
    /**
     * 
     * @param string $html
     * @return boolean
     */
    public function lexTokens($html)
    {
        $types = array_keys($this->tokens);
        $patterns = [];
        $token_stream = [];
        $result = false;
        foreach ($this->tokens as $k=>$v){
            $patterns[] = "(?P<$k>$v)";
        }
        $pattern = "/".implode('|', $patterns)."/is";
        if (preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
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
                $token_stream[] = $tok;
            }
        }
        return $token_stream;
    }
    
    /**
     * 
     * @param array $token_stream - pass by refrence
     * @throws Exception
     * @return string
     */
    protected function parseTokens( array &$token_stream )
    {  
        $mode = 'closed';
        
        $string = '';
        $result = '';
    
        while($current = current($token_stream)){  
            $content = $current['content'];
            $type = $current['type'];
            
            next($token_stream);
            switch($type){  
                case 'T_COMMENT':
                    //remove comments
                break;
                case 'T_OPEN_TAG':
                    if(strlen($string)){
                        //add trimmed string to result, reset string.
                        if($mode == 'ignore'){
                            $result .= $string;
                        }else{
                            $result .= trim($string);
                        }
                        $string = '';
                    }
                    //clean
                    $content = $this->cleanTag($content);
                    
                    if($this->isIgnoredTag($content)){
                        //indicate ignore whitespace
                        $mode = 'ignore';
                    }else{
                        //indicate a tag is open
                        $mode = 'open';
                    }
                    $result .= $content;
                break;
                case 'T_INLINE_TAG':
                case 'T_CLOSE_TAG':  
                    if(strlen($string)){
                        //add trimmed string to result, reset string.
                        if($mode == 'ignore'){
                            $result .= $string;
                        }else{
                            $result .= trim($string);
                        }
                        $string = '';
                    }
                    //clean
                    $content = $this->cleanTag($content);
                    //add content to result
                    $result .= $content;
                    //indicate a tag is closed
                    $mode = 'closed';               
                break;  
                case 'T_ENCAPSED_STRING':
                case 'T_STRING':
                case 'T_UNKNOWN':
                    switch ($mode){
                        case 'ignore':
                        case 'open':
                        case 'closed':
                            //add content to string (not result)
                            $string .= $content;
                        break;
                        default:
                            print_r($result);
                            throw new Exception("Unknown Mode:$mode for $type value $content", 1002);
                    }   
                break;           
                case 'T_WHITESPACE':
                    switch ($mode){
                        case 'closed':
                            //remove whitespace between tags.
                        break;
                        case 'open':
                            //add only on space ot string no matter how many we find
                            $string .= ' ';
                        break;
                        case 'ignore':
                            $string .= $content;
                        break;
                        default:
                            print_r($result);
                            throw new Exception("Unknown Mode:$mode for $type value $content", 1002);
                    }   
                break;
                case 'T_EOF': return $result;
                default:
                    print_r($current);
                    print_r($result);
                    throw new Exception("Unknown token $type value $content", 1001);
            }
        }
    }
    
    /**
     * 
     * @param string  $tag
     * @return string
     */
    protected function cleanTag($tag)
    {
        return preg_replace([
            '/\s{2,}/',            
            '/^<\s+/',
            '/^<\/\s+/',
            '/\s+>$/',
            '/\s\/>$/'
         ],[
            ' ',
            '<',
            '</',
            '>',
            '/>',
         ], $tag);
    }
    
    /**
     * 
     * should be cleand with cleanTag first.
     * 
     * @param string $htmlTag
     * @param array $ignoreTags
     * @throws Exception
     * @return boolean
     */
    protected function isIgnoredTag($htmlTag)
    {
        if(!preg_match('/<\/?([a-z]+)\b/i', $htmlTag, $tagName))
            throw new Exception("Cound not parse HTML tag name $htmlTag", 1000);
       return in_array($tagName[1],$this->ignoreTags);
    }
}


$html = <<<HTML
<style type="text/css" >
.body, div
{
    background-color: #CCC;
}

#someid
{
   color: #fff;
}
</style>
<p>
This is
            a
stupid p tag
            that has
    all     kinds   of  extra   space   in  it.
</p>
<   span  id="foo"  >Insert title<  /    span    ><!-- extra space in this tag, comments are removed -->
<
br
><!-- new line tag -->
<br  /  ><!-- spaced inline tag -->
<form class="some class other another"> 
    <div class="title-box">
        <div class="title">Questions</div>
    </div>

    <div class="content">
        <div>
            <span>Insert title</span>
            <div>
                <input name="question" placeholder="Insert some text here" type="text" />
            </div>
        </div>
        <div class="margin-t-10">
            <label>Insert BIO</label>
            <div>
                <textarea name="bio" class="textarea-content">This is first line text
This is second line text

more lines...</textarea>
            </div>
        </div>
        <div class="description">
            <label>Insert description here</label>
            <div>
                <textarea data-something name="description" class="textarea-content other class">

Line one
line two
    have some tabulation here to keep...

another line...</textarea>
            </div>
        </div>
    </div>
</form>
<script type="text/javascript">
(function($){
    $(document).ready(function(){
        var div = "<div>foobar</div>";
        var span = '<span>span</span>';
        $('textarea[name="bio"]').focuus();
        $(form).on('submit', function(e){
            e.preventDefault();
            return false;
        }
    });
})(jQuery);
</script>
HTML;

header('Content-type: text/plain');
echo (new Minifier('textarea'))->minify($html);

