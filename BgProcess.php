<?php
/**
 * 
 * 
 * Create a background shell process free of the shell, non-blocking background process
 * 
 */
class BgProcess{
	
	/**
	 * 
	 * @var string
	 */
	protected $_comand;
	
	/**
	 * 
	 * @var boolean
	 */
	protected $_osWin;
	
	/**
	 * 
	 * @param string $arg0, $arg1 ...
	 * $arg0 is location of php file to run
	 * $arg1 ..is additional params to send to script
	 */
	public function __construct($arg0){
		
		if(stripos(php_uname('s'), 'win') > -1){
			$this->_osWin = true;
		}else{
			$this->_osWin = false;
		}
		
		$args = func_get_args();
		if(empty($args)){
			throw new Exception(__CLASS__.' arguments required' );
		}
		
		$file = str_replace('\\', '/', array_shift($args));
		$script = escapeshellarg($file).' '.escapeshellarg(implode('/', $args));
		if(false !== ($phpPath = $this->_getPHPExecutableFromPath())){

			if($this->_osWin){	
				$WshShell = new \COM('WScript.Shell');
				$cmd = 'cmd /C '.$phpPath.' '.$script;
				$oExec = $WshShell->Run($cmd, 0, false);
			}else{
				$cmd = $phpPath.' -f '.$script.' > /dev/null &';
				exec($cmd, $oExec);
			}
			
			$this->_comand = $cmd;

		}else{
			throw new Exception( 'Could not find php executable' );
		}
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getCommand(){
		return $this->_comand;
	}
	
	/**
	 * 
	 * @return string|boolean
	 */
	protected function _getPHPExecutableFromPath() {
		$paths = explode(PATH_SEPARATOR, getenv('PATH'));
		if($this->_osWin){
			foreach ($paths as $path) {
				if (strstr($path, 'php')){
					$php_executable =  $path . DIRECTORY_SEPARATOR . 'php.exe';
					if(file_exists($php_executable) && is_file($php_executable)){
						return $php_executable;
					}
				}
			}
		}else{
			foreach ($paths as $path) {
				$php_executable = $path . DIRECTORY_SEPARATOR . "php";
				if (file_exists($php_executable) && is_file($php_executable)) {
					return $php_executable;
				}
			}
			
		}
		return false;
	}
	

}