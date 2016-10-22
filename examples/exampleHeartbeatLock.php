<?php
//url: http://localhost/MISC/examples/exampleHeartbeatLock.php
//cli> php -f "{your path}\MISC\examples\exampleHeartbeatLock.php"
/*
(c) 2017 Hugh Durham III
 
For license information please view the LICENSE file included with this source code. 

A minimul example of locking a cron process, where cron is a Blocking process ( ie. this script is the cron job )
.
DO NOT USE THE SAME BROWSER for example 2 FireFox windows, as they will both have the same PID ( on windows at least )
This prevents the lockfile from realizing it shouldnt run, because new process ( 2nd window ) has the same PID as 
Old process ( 1st window ) - what happens is 2nd process is blocked ( paused ) untill first process is finished.

Tests:
1. Open one copy of this file in a browser or CLI window and try to open a second copy in anther browser or CLI
	An exception is thrown in the second copy.
	
2. Open one copy of this file in a browser or CLI
	Find the lock file on the HD, and delete it
	Process throws exception and dies.
	
3. Open one copy of this file in a browser or CLI - wait
    process ends when accesstime + heartbeat  < time()  ( 1 minute or apx at output of 840/s 900-60)
    
4. Open one copy of this file in a browser or CLI - wait, before the heartbeat expires try to open a second copy in anther browser or CLI
    the 2nd copy will die as in the first example, but note the process will run past the time in the third example
	
Conclusion - this simulates forcing a process to run as a single thread, this is usefull if you have a 
			long running cron job, but don't want to run a second copy becuase it could overlap ( cause a race condition )
			with the first copy.
			
			This differs from the exampleCronLock.php file in that this file has a specified heartbeat time, this simulates
			having a process that waits for work, with a longer end time  (15 minutes) - this could be for moving a file
			submitted by FTP, where the script will run and cycle with nothing to do, then pickup a file to run.
			- Using that example (FTP upload)
			* this is superior to a short interval cron polling script - because depending if the input file is not locked,
			you could access it from a second copy of the script ran by cron ( race condition ).
			Using ProcLock the second cron job dies if the first is running
			* this is superior to a long interval cron polling script - because if you want to end the process it's very 
			difficult if runs for several hours.  Using ProcLock and a heartbeat you just stop the cron job, which stops updating
			the lock file's last modified time ( updated by ProcLock::lock via ProcLock::canLock() ), then with checking
			the last access time ( ProcLock::getLastAccess ) we can kill it if its longer then the heartbeat interval
			lock file, this causes the heartbeat to expire and the script dies.
			* this is superior to a long interval cron polling script - because if the script dies, it may have to wait
			some time for cron to run and restart it. 
			
			The only catch is that the cron job run with an interval less then the heartbeat, the reason for this should be obvious.
*/
header('Content-Type:text/plain'); //plain text

require __DIR__.'/streamoutput.php';
if( !isset( $inculded ) ){
	//make sure streaming is on
	exit();
}

require __DIR__.'/../ProcLock.php';

$lockfile = __DIR__.'/heartbeatlock.lock';

echo "lock file: $lockfile\n";

$heartbeatTimeout = 60 * 2;
$expire = 60 * 15;  //15 minutes  ( this simulate some amount of working takeing 5 minutes )
set_time_limit(($expire+60)); //set max running time loger then script execution

$chunk = 5; //interval chunk ( this simulates serverl small jobs taking 5 seconds each )

$maxLifetime =  60 * 15; //max lifetime ( the script will die after this time )
$starttime = time();   //current time.

//max heartbeat time - die after this interval diffrence between mtime of lock and time
$heartbeatTimeout = 60;

ProcLock::setLockFile($lockfile); //just sets the lock file so we don't have to keep putting it in.
ProcLock::lock();

echo "Starting Process.\n";
flush();

while( $expire > 0 ){
	echo "$expire/s remaining \n";
	sleep( $chunk );
	$expire -= $chunk;

	if( false === ( $lastAcess = ProcLock::getLastAccess())){
		//if the lockfile is deleted then last access is false.
		echo "Lock file removed.\n";
		exit(0);
	}
	

	if( $lastAcess + $heartbeatTimeout < time()){
		echo "Heartbeat timeout.\n";
		exit(0);
	}
	
	if( $starttime + $maxLifetime < time()){
		//if the max lifetime is ran out exit.
		echo "Max Lifetime exceeded.\n";
		exit(0);
	}
}

