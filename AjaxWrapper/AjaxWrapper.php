<?php

class AjaxWrapper{
    
    /**
     * Development mode
     *
     * This is the least secure mode, but the one that
     * diplays the most information.
     *
     * @var string
     */
    const ENV_DEVELOPMENT = 'development';
    
    /**
     *
     * @var string
     */
    const ENV_PRODUCTION = 'production';
    
    /**
     * 
     * @var string
     */
    protected static $environment;
    
    /**
     * 
     * @param string $env
     */
    public static function setEnviroment($env){
        if(!defined(__CLASS__.'::ENV_'.strtoupper($env))){
            throw new Exception('Unknown enviroment please use one of the '.__CLASS__.'::ENV_* constants instead.');
        }
        $this->environment = $env;
    }
    
    public static function respond($callback, $options=0, $depth=32){
        $result = ['userdata' => [
              'debug' => false,
              'error' => false
        ]];

        ob_start();

         try{

             if(!is_callable($callback)){
                //I have better exception in mine, this is just more portable
                throw new Exception('Callback is not callable');
             }

             $callback($result);
         }catch(\Exception $e){
              //example 'Exception[code:401]'
             $result['userdata']['error'] = get_class($e).'[code:'.$e->getCode().']';
            if(static::$environment == ENV_DEVELOPMENT){
            //prevents leaking data in production
                $result['userdata']['error'] .= ' '.$e->getMessage();
                $result['userdata']['error'] .= PHP_EOL.$e->getTraceAsString();
            }
         }

         $debug = '';
         for($i=0; $i < ob_get_level(); $i++){
             //clear any nested output buffers
             $debug .= ob_get_clean();
         }
         if($this->environment == self::ENV_DEVELOPMENT){
             //prevents leaking data in production
              $result['userdata']['debug'] = $debug;
         }
         header('Content-Type: application/json');
         echo self::jsonEncode($result, $options, $depth);
   }

   public static function jsonEncode($result, $options=0, $depth=32){
       $json = json_encode($result, $options, $depth);
       if(JSON_ERROR_NONE !== json_last_error()){
           //debug is not passed in this case, because you cannot be sure that, that was not what caused the error.  Such as non-valid UTF-8 in the debug string, depth limit, etc...
           $json = json_encode(['userdata' => [
              'debug' => false,
              'error' => json_last_error_msg()
           ]],$options);
       }
       return $json;
   }

}