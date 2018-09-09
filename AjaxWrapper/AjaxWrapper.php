<?php
/**
 *
 * (c) 2016 ArtisticPhoenix
 *
 * For license information please view the LICENSE file included with this source code.
 *
 * Ajax Wrapper
 * 
 * @author ArtisticPhoenix
 * 
 * 
 * @example
 * 
 * <b>Javascript</b>
 * $.post(url, {}, function(data){
 * 
 *          if(data.error){
 *              alert(data.error);
 *              return;
 *          }else if(data.debug){          
 *              alert(data.debug);
 *          }
 *          
 * 
 * });
 * 
 *
 * <b>PHP</p>
 * //put into devlopment mode (so it will include debug data)
 * AjaxWrapper::setEnviroment(AjaxWrapper::ENV_DEVELOPMENT);
 * 
 * //wrap code in the Wrapper (wrap on wrap of it's the wrapper)
 * AjaxWrapper::respond(function(&$response){
 *     echo "hello World"
 *     Your code goes here
 *     $response['success'] = true;
 * });
 *
 */
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
        static::$environment = $env;
    }
    
    /**
     * 
     * @param closure $callback - a callback with your code in it
     * @param number $options - json_encode arg 2
     * @param number $depth - json_encode arg 3
     * @throws Exception
     * 
     * @example
     * 
     * 
     */
    public static function respond(Closure $callback, $options=0, $depth=32){
        $response = ['userdata' => [
              'debug' => false,
              'error' => false
        ]];

        ob_start();

         try{

             if(!is_callable($callback)){
                //I have better exception in mine, this is just more portable
                throw new Exception('Callback is not callable');
             }

             $callback($response);
         }catch(\Exception $e){
              //example 'Exception[code:401]'
             $response['error'] = get_class($e).'[code:'.$e->getCode().']';
            if(static::$environment == ENV_DEVELOPMENT){
            //prevents leaking data in production
                $response['error'] .= ' '.$e->getMessage();
                $response['error'] .= PHP_EOL.$e->getTraceAsString();
            }
         }

         $debug = '';
         for($i=0; $i < ob_get_level(); $i++){
             //clear any nested output buffers
             $debug .= ob_get_clean();
         }
         if(static::environment == static::ENV_DEVELOPMENT){
             //prevents leaking data in production
              $response['debug'] = $debug;
         }
         header('Content-Type: application/json');
         echo static::jsonEncode($response, $options, $depth);
   }

   /**
    * common Json wrapper to catch json encode errors
    * 
    * @param array $response
    * @param number $options
    * @param number $depth
    * @return string
    */
   public static function jsonEncode(array $response, $options=0, $depth=32){
       $json = json_encode($response, $options, $depth);
       if(JSON_ERROR_NONE !== json_last_error()){
           //debug is not passed in this case, because you cannot be sure that, that was not what caused the error.
           //Such as non-valid UTF-8 in the debug string, depth limit, etc...
           $json = json_encode(['userdata' => [
              'debug' => false,
              'error' => json_last_error_msg()
           ]],$options);
       }
       return $json;
   }

}