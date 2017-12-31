<?php

/**
 * A simple 1 level router
 * 
 * URL schema is http://example.com/{controller}/{method}/{args ... }
 * 
 * @author ArtisticPhoenix
 * @package SimpleRouter
 */
class SimpleRouter{
    
    /**
     * should be the same as rewrite base in .htaccess
     * @var string
     */
    const REWRITE_BASE = '/MISC/Router/';
    
    /**
     * path to controller files
     * 
     * @var string
     */
    const CONTOLLER_PATH = __DIR__.'/Controllers/';
    
    /**
     * simple router
     * 
     * schema 
     */
    public static function route(){
        //normalize
        $uri = preg_replace('~^'.self::REWRITE_BASE.'~i', '',$_SERVER['REQUEST_URI']);
        $uri = preg_replace('~^index\.php~i', '',$uri);      
        $uri = trim($uri,'/');
        
        //empty url, like www.example.com
        if(empty($uri)) $uri = 'home/index';
        
        //empty method like www.example.com/home
        if(!substr_count($uri, '/')) $uri .= '/index';
        
        $arrPath = explode('/', $uri);
        
        $contollerName = array_shift($arrPath);
        $methodName = array_shift($arrPath);;
        $contollerFile = self::CONTOLLER_PATH.$contollerName.'.php';
        
        if(!file_exists($contollerFile)){
            //send to error page
            self::error404($uri);
            return;
        }
        
        require_once $contollerFile;
        
        if(!class_exists($contollerName)){
            self::error404($uri);
            return;
        }
        
        $Controller = new $contollerName();
        
        if(!method_exists($Controller, $methodName)){
            self::error404($uri);
            return;
        }
        
        if(!count($arrPath)){
            call_user_func([$Controller, $methodName]);
        }else{
            call_user_func_array([$Controller, $methodName], $arrPath);
        } 
    }
 
    /**
     * call error 404
     * 
     * @param string $uri
     */
    protected static function error404($uri){
        require_once self::CONTOLLER_PATH.'home.php';     
        $Controller = new home();
        $Controller->error404($uri);
    }
}