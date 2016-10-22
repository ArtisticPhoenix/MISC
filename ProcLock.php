<?php
/*
 (c) 2017 Hugh Durham III
 
 For license information please view the LICENSE file included with this source code. 

 Proccess Locker
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
 Only one Lock per Process!
 -note- when running in a browser typically all tabs will have the same PID
 so the locking will not be able to tell if it's the same process, to get 
 around this run in CLI, or use 2 diffrent browsers, so the PID numbers are diffrent.
 
 This class is static for the simple fact that locking is done per-proces, so there is no need 
 to ever have duplate ProcLocks within the same process
 ---------------------------------------------------------------
 */
final class ProcLock{
	
	/**
	 * exception code numbers
	 * @var int
	 */
	const DIRECTORY_NOT_FOUND 	= 2000;	
	const LOCK_FIRST			= 2001;
	const FAILED_TO_UNLOCK		= 2002;
	const FAILED_TO_LOCK		= 2003;
	const ALREADY_LOCKED		= 2004;
	const UNKNOWN_PID			= 2005;
	const PROC_UNKNOWN_PID		= 2006;
	
	
	/**
	 * process _key
	 * @var string
	 */
	protected static $_lockFile; 
	
	/**
	 *
	 * @var int
	 */
	protected static $_pid;   
	
	/**
	 * No construction allowed
	 */
	private function __construct(){}
	
	/**
	 * No clones allowed
	 */
	private function __clone(){}
	
	/**
	 * globaly sets the lock file
	 * @param string $lockFile
	 */
	public static function setLockFile( $lockFile ){
		$dir = dirname( $lockFile );
		if( !is_dir( dirname( $lockFile ))){ 
			throw new Exception("Directory {$dir} not found", self::DIRECTORY_NOT_FOUND);  //pid directroy invalid
		}
		
		self::$_lockFile = $lockFile;
	}
	
	/**
     * return global lockfile
	 */
	public static function getLockFile() {
		return ( self::$_lockFile ) ? self::$_lockFile : false;
	}
	
	/**
	 * safe check for local or global lock file
	 */
	protected static function _chk_lock_file( $lockFile = null ){
		if( !$lockFile && !self::$_lockFile ){
			throw new Exception("Lock first", self::LOCK_FIRST); //
		}elseif( $lockFile ){
			return $lockFile;
		}else{
			return self::$_lockFile;
		}
	}
	
	/**
	 * 
	 * @param string $lockFile
	 */
	public static function unlock( $lockFile = null ){
		if( !self::$_pid ){
			//no pid stored - not locked for this process
			return;
		}
		
		$lockFile = self::_chk_lock_file($lockFile);
		if(!file_exists($lockFile) || unlink($lockFile)){
			return true;
		}else{
			throw new Exception("Failed to unlock {$lockFile}", self::FAILED_TO_UNLOCK ); //no lock file exists to unlock or no permissions to delete file
		}
	}
	
	/**
	 *
	 * @param string $lockFile
	 */
	public static function lock( $lockFile = null ){	
		$lockFile = self::_chk_lock_file($lockFile);
		if( self::canLock( $lockFile )){
			self::$_pid = getmypid();
			if(!file_put_contents($lockFile, self::$_pid ) ){
				throw new Exception("Failed to lock {$lockFile}", self::FAILED_TO_LOCK ); //no permission to create pid file
			}
		}else{
			throw new Exception('Process is already running[ '.$lockFile.' ]', self::ALREADY_LOCKED );//there is a process running with this pid 
		}
	}

	/**
	 *
	 * @param string $lockFile
	 */
	public static function getPidFromLockFile( $lockFile = null ){
		$lockFile = self::_chk_lock_file($lockFile);
		
		if(!file_exists($lockFile) || !is_file($lockFile)){
			return false;
		}
	
		$pid = file_get_contents($lockFile);
	
		return intval(trim($pid));
	}
	
	/**
	 * 
	 * @return number
	 */
	public static function getMyPid(){
		return ( self::$_pid ) ? self::$_pid : false;
	}
	
	/**
	 * 
	 * @param string $lockFile
	 * @param string $myPid
	 * @throws Exception
	 */
	public static function validatePid($lockFile = null, $myPid = false ){
		$lockFile = self::_chk_lock_file($lockFile);
		if( !self::$_pid && !$myPid ){
			throw new Exception('no pid supplied', self::UNKNOWN_PID ); //no stored or injected pid number
		}elseif( !$myPid ){
			$myPid = self::$_pid;
		}

		return ( $myPid == self::getPidFromLockFile( $lockFile ));	
	}

	/**
	 * update the mtime of lock file
	 * @param string $lockFile
	 */
	public static function canLock( $lockFile = null){
		if( self::$_pid ){
			throw new Exception("Process was already locked", self::ALREADY_LOCKED ); //process was already locked - call this only before locking
		}
		
		$lockFile = self::_chk_lock_file($lockFile);
		
		$pid = self::getPidFromLockFile( $lockFile );
		
		if( !$pid ){
			//if there is a not a pid then there is no lock file and it's ok to lock it
			return true;
		}
		
		//validate the pid in the existing file
		$valid = self::_validateProcess($pid);  
		
		if( !$valid ){
			//if it's not valid - delete the lock file
			if(unlink($lockFile)){
				return true;
			}else{
				throw new Exception("Failed to unlock {$lockFile}", self::FAILED_TO_UNLOCK ); //no lock file exists to unlock or no permissions to delete file
			}	
		}
		
		//if there was a valid process running return false, we cannot lock it.
		//update the lock files mTime - this is usefull for a heartbeat, a periodic keepalive script.
		touch($lockFile);
		return false;	
	}
	
	/**
	 *
	 * @param string $lockFile
	 */
	public static function getLastAccess( $lockFile = null ){
		$lockFile = self::_chk_lock_file($lockFile);
		clearstatcache( $lockFile );
		if( file_exists( $lockFile )){
			return filemtime( $lockFile );
		}
		return false;
	}
	
	/**
	 *
	 * @param int $pid
	 */
	protected static function _validateProcess( $pid ){
		$task = false;
		$pid = intval($pid);
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
<<<<<<< HEAD
				4298 ?        S      0:00 /usr/bin/php /home/webrecom/dev/public_html/clients/index.php
				'
				*/
=======
			4298 ?        S      0:00 /usr/bin/php /home/dev/public_html/clients/index.php
			'
			*/
>>>>>>> refs/remotes/origin/master
		}
			
		//print_rr( $task );
		if($task){
			return ( preg_match('/php|httpd/', $task) ) ? true : false;
		}
	
		throw new Exception("pid detection failed {$pid}", self::PROC_UNKNOWN_PID);  //failed to parse the pid look up results 
		//this has been tested on CentOs 5,6,7 and windows 7 and 10
	}
	
	/**
	 * destroy a lock ( safe unlock )
	 */
	public static function destroy($lockFile = null){
		try{
			$lockFile = self::_chk_lock_file($lockFile);
			self::unlock( $lockFile );
		}catch( Exception $e ){
			//ignore errors here - this called from distruction so we dont care if it fails or succeeds
			//generally a new process will be able to tell if the pid is still in use so
			//this is just a cleanup process
		}
	}
}

/*
 * register our shutdown handler - if the script dies unlock the lock
 * this is superior to __destruct(), because the shutdown handler runs even in situation where PHP exhausts all memory
 */
<<<<<<< HEAD
register_shutdown_function(array('\\'.ProcLock::class, "destroy"));
=======
register_shutdown_function(array('\\Lib\\Queue\\ProcLock',"destroy"));
>>>>>>> refs/remotes/origin/master
