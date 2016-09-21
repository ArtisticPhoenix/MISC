<?php
namespace Lib\Queue;

use Lib\FileSystem\FileSystem;
use Lib\FileSystem\FileSystemConfig;
/*
 CLASS Lock
 Description - Process locker
 ==================================================================
 This is a pseudo implementation of mutex since php does not have
 any thread synchronization objects
 This class uses files to provide locking functionality.
 Lock will be released in following cases
 1 - user calls unlock
 2 - when this lock object gets deleted
 3 - when request or script ends
 4 - when pid of lock does not match self::$_pid
 ==================================================================
 */
class ProcLock{
	/**
	 * process _key
	 * @var string
	 */
	protected static $_key; 
	
	/**
	 *
	 * @var int
	 */
	protected static $_pid;  
	
	
	/**
	 *
	 * @var QueueConfig
	 */
	protected static $_queueConfig;  
	
	/**
	 *
	 * @var Logger
	 */
	protected static $_loggerKey;  
	
	/**
	 * 
	 * @param string $key
	 * @param int $pid
	 */
	public function __construct(){	
		self::$_loggerKey = basename(__CLASS__);
		self::$_queueConfig = QueueConfig::getInstance();
	}
	
	/**
	 * remove lock files whos processes have died,
	 * this is not essential as we check this when getting a new lock - but it looks better to clean them up.
	 */
	public function cleanDeadLocks(){
		$path = self::$_queueConfig->get('path', 'locks');
		$contents = array_diff(scandir( $path ), array('.','..'));
		
		foreach ( $contents as $filename ){
			$key = str_replace('.lock', '', $filename);
			$pid = $this->getPidFromLockFile( $key );
			if( !$this->_validatePid($pid) ){
				unlink( $path . $filename );
			}
		}
	}
	
	
	/**
	 * 
	 * @param string $key
	 * @return string
	 */
	public function getLockFilename($key){
		return self::$_queueConfig->get('path', 'locks').preg_replace('/^(.+?)(\.lock)?$/', '\1.lock', $key);
	}
	
	/**
	 * 
	 */
	public static function destroy(){
		$Lock = new self;
		$Lock->unlock();
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function hasLock(){
		return ( self::$_key && self::$_pid ) ? true : false;
	}

	/**
	 * 
	 * @return boolean
	 */
	public function lock($key, $pid){
		if($this->canLock($key)){
			$filename = $this->getLockFilename($key);
			if(false !== ($handle = fopen( $filename, 'w' ))){
				fwrite( $handle, $pid);
				fclose( $handle );
				self::$_key = $key;
				self::$_pid = $pid;
				return true;
			}else{
				throw new QueueException('[ '.$filename.' ]', QueueException::ER_LOCK);
			}
		}
		return false;
	}

	/**
	 * 
	 * @throws QueueException
	 * @return boolean
	 */
	public function unlock(){
		print_rr(__METHOD__);
		if( $this->hasLock() === true ){
			$filename = $this->getLockFilename( self::$_key );
			if(!file_exists($filename) || unlink($filename)){
				self::$_key = null;
				self::$_pid = null;
				return true;
			}else{
				throw new QueueException('[ '.$filename.' ]', QueueException::ER_UNLOCK);
			}
		}
		return false;
	}
	
	/**
	 * 
	 * @return NULL|number
	 */
	public function getPidFromLockFile($key = null){
		if( !$key ){
			$key = self::$_key;
		}
		$lockFile = $this->getLockFilename($key);
		if(!file_exists($lockFile) || !is_file($lockFile)){
			return false;
		}

		$pid = file_get_contents($lockFile);
		return intval(trim($pid));
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function lastAccess(){
		if(!$this->hasLock()){
			//should never happen unless improperly called
			throw new QueueException('before calling '.__METHOD__.'()', QueueException::ER_LOCK_FIRST);
		}
	
		$lockFile = $this->getLockFilename(self::$_key);
	
		if(!file_exists($lockFile) || !is_file($lockFile)){
			//no lock file
			throw new QueueException('[ '.$lockFile.' ]', QueueException::ER_FILE_NOT_EXISTS);
		}

		
		$processPid = $this->getPidFromLockFile(self::$_key);
		
		if(self::$_pid != $processPid){
			throw new QueueException('[ '.self::$_pid.' != '.$processPid.' ]', QueueException::ER_LOCK_PID_MISSMATCH);
			//process id missmatch
		}
		
		clearstatcache($lockFile);
		return @filemtime($lockFile);
	}
	
	
	protected function _validatePid( $pid ){
		$task = false;
		$pid = intval($pid);
		print_rr(self::$_pid . ' = ' . $pid );
		if(stripos(php_uname('s'), 'win') > -1){
			$task = shell_exec("tasklist /fi \"PID eq {$pid}\"");
			/*
			 'INFO: No tasks are running which match the specified criteria.
			'
			*/
			/*
			 '
			Image Name                     PID Session Name        Session#    Mem Usage
			========================= ======== ================ =========== ============
			php.exe                    5064 Console                    1     64,516 K
			'
			*/
		}else{
			$cmd = "ps ".intval($pid);
			$task = shell_exec($cmd);
			/*
			 '  PID TTY      STAT   TIME COMMAND
			'
			*/
			/*
			 '  PID TTY      STAT   TIME COMMAND
			4298 ?        S      0:00 /usr/bin/php /home/webrecom/dev/public_html/clients/index.php
			'
			*/
		}
		//print_rr( $task );
		//print_rr( $task );
		if($task){
			return ( preg_match('/php|httpd/', $task) ) ? true : false;
		}
		
			
		throw new QueueException('task pid detection failed [ '.$pid.' ]', QueueException::ER_INVALID_DATA_TYPE);
		
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function canLock($key){
		$lockFile = $this->getLockFilename($key);

		if(file_exists($lockFile) && is_file($lockFile)){
			touch($lockFile); //update mTime and aTime
		}

		if(false === ($lPid = $this->getPidFromLockFile($key))){
			//no pid from previous process
			return true;
		}
		
		return !$this->_validatePid( $lPid );
	}
}

/*
 * register our shutdown handler - if the script dies unlock the lock
 */
register_shutdown_function(array('\\Lib\\Queue\\ProcLock',"destroy"));