<?php
/**
 *
 * (c) 2016 ArtisticPhoenix
 *
 * For license information please view the LICENSE file included with this source code.
 *
 * PSR4 compatable Autoloader
 * 
 * @author ArtisticPhoenix
 * @see http://www.php-fig.org/psr/psr-4/
 * 
 * @example
 * $Autoloader = Autoloader::getInstance();
 * //looks in includes for folder named /includes/Lib/Auth/User/
 * $Autoloader->regesterPath('Lib\\Auth\\User', __DIR__.'/includes/');
 *
 */
final class Autoloader
{
    /**
     *
     * @var int
     */
    const DEFAULT_PRIORITY = 10;
    
    /**
     * namespace / class path storage
     * @var array
     */
    private $paths = array();
    
    /**
     * cashe the loaded files
     * @var array
     */
    private $files = array();
    
    /**
     * namespace / class path storage
     * @var array
     */
    private $debugMode = false;
    
    /**
     *
     * @var Self
     */
    private static $instance;
    
    
    /**
     * No public construction allowed - Singleton
     */
    private function __construct($throw, $prepend)
    {
        spl_autoload_register(array( $this,'splAutoload'), $throw, $prepend);
    }
    
    /**
     * No cloning of allowed
     */
    private function __clone()
    {
    }
    
    /**
     *
     * Get an instance of the Autoloader Singleton
     * @param boolean $throw
     * @param boolean $prepend
     * @return self
     */
    public static function getInstance($throw = false, $prepend = false)
    {
        if (!self::$instance) {
            self::$instance = new self($throw, $prepend);
        }
        return self::$instance;
    }
    
    /**
     * set debug output
     * @param boolean $debug
     * @return self
     */
    public function setDebug($debug = false)
    {
        $this->debugMode = $debug;
        return $this;
    }
    
    /**
     * Autoload
     * @param string $class
     */
    public function splAutoload($class)
    {
        $this->debugMode('_START_');
        $this->debugMode(__METHOD__.' '.$class);

        //keep the orignal class name
        $_class = str_replace('\\', '/', $class);
        $namespace = '';
        if (false !== ($pos = strrpos($_class, '/'))) {
            $namespace = substr($_class, 0, ($pos));
            $_class = substr($_class, ($pos + 1));
        }

        //replace _ in class name only
        if (false !== ($pos = strrpos($_class, '/'))) {
            if (strlen($namespace)) {
                $namespace .= '/'.substr($_class, 0, ($pos));
            } else {
                $namespace = substr($_class, 0, ($pos));
            }
            $_class = substr($_class, ($pos + 1));
        }
        
        $this->debugMode("Checking class: $_class");
        $this->debugMode("Checking namespace: $namespace");

        do {
            if (isset($this->paths[ $namespace ])) {
                foreach ($this->paths[ $namespace ] as $registered) {
                    $filepath = $registered['path'] . $_class . '.php';
                    
                    $this->debugMode("checking pathname:{$filepath}");

                    if (file_exists($filepath)) {
                        $this->debugMode("Found: $filepath");
                        $this->debugMode('_END_');
                        require_once $filepath;
                        $this->files[$class] = $filepath;
                    }
                }
            }
            
            if (strlen($namespace) == 0) {
                //if the namespace is empty and we couldn't find the class we are done.
                break;
            }
            
            if (false !== ($pos = strrpos($namespace, '/'))) {
                $_class = substr($namespace, ($pos + 1)) . '/' . $_class;
                $namespace = substr($namespace, 0, ($pos));
            } else {
                $_class = (strlen($namespace) ? $namespace : '') . '/' . $_class;
                $namespace = '';
            }
        } while (true);

        $this->debugMode('_END_');
    }
    
    /**
     * get the paths regestered for a namespace, leave null go get all paths
     * @param string $namespace
     * @return array or false on falure
     */
    public function getRegisteredPaths($namespace = null)
    {
        if (is_null($namespace)) {
            return $this->paths;
        } else {
            return (isset($this->paths[$namespace])) ? array($namespace => $this->paths[$namespace])  : false;
        }
    }
    
    /**
     *
     * @param string $namespace
     * @param string $path
     * @param int $priority
     * @return self
     */
    public function regesterPath($namespace, $path, $priority = self::DEFAULT_PRIORITY)
    {
        $namespace = str_replace('\\', '/', $namespace); //convert to directory seperator
        $path = ($this->normalizePath($path));
                
        $this->paths[$namespace][sha1($path)] = array(
            'path'        => $path,
            'priority'    => $priority
        );

        $this->sortByPriority($namespace);
        return $this;
    }
    
    /**
     * un-regester a path
     * @param string $namespace
     * @param string $path
     */
    public function unloadPath($namespace, $path = null)
    {
        if ($path) {
            $path = $this->normalizePath($path);
            unset($this->paths[$namespace][sha1($path)]);
        } else {
            unset($this->paths[$namespace]);
        }
    }
    
    /**
     * check if a namespace is regestered
     * @param string $namespace
     * @param string $path
     * @return bool
     */
    public function isRegistered($namespace, $path = null)
    {
        if ($path) {
            $path = $this->normalizePath($path);
            return isset($this->paths[$namespace][sha1($path)]) ? true : false;
        } else {
            return isset($this->paths[$namespace]) ? true : false;
        }
    }
    
    /**
     * get the file pathname of a loaded class
     * @param string $class
     * @return mixed
     */
    public function getLoadedFile($class = null)
    {
        if (!$class) {
            return $this->files;
        }
        
        if (isset($this->files[$class])) {
            return $this->files[$class];
        }
    }

    /**
     * output debug message
     * @param string $message
     */
    protected function debugMode($message)
    {
        if (!$this->debugMode) {
            return;
        }
        
        switch ($message) {
            case '_START_':
                echo str_pad("= ".__METHOD__." =", 90, "=", STR_PAD_BOTH) . PHP_EOL;
            break;
            case '_END_':
                echo str_pad("", 90, "=", STR_PAD_BOTH) . PHP_EOL . PHP_EOL;
            break;
            default:
                echo $message . PHP_EOL;
        }
    }
    
    /**
     * sort namespaces by priority
     * @param string $namespace
     */
    protected function sortByPriority($namespace)
    {
        uasort($this->paths[$namespace], function ($a, $b) {
            return ($a['priority'] > $b['priority']) ? true : false;
        });
    }
    
    /**
     * convert a path to unix seperators and make sure it has a trailing slash
     * @param string $path
     * @return string
     */
    protected function normalizePath($path)
    {
        if (false !== strpos($path, '\\')) {
            $path = str_replace("\\", "/", $path);
        }
        
        return rtrim($path, '/') . '/';
    }
}
