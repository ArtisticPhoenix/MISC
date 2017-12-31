<?php

/**
 * 
 * The default controller
 * 
 * @author ArtisticPhoenix
 * @package SimpleRouter
 */
class home{
    
    public function index($arg=false){
        echo "<h3>".__METHOD__."</h3>";
        echo "<pre>";
        print_r(func_get_args());
    }
  
    public function otherpage($arg){
        echo "<h3>".__METHOD__."</h3>";
        echo "<pre>";
        print_r(func_get_args());
    }
    
    public function error404($uri){
        header('HTTP/1.0 404 Not Found');
        echo "<h3>Error 404 page {$uri} not found</h3>";
    }
    
}