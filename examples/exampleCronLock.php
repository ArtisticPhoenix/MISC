<?php
//url: http://localhost/MISC/examples/exampleCronLock.php
//cli> php -f "{your path}\MISC\examples\exampleCronLock.php"
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
    process ends when max lifetime is reached
	
Conclusion - this simulates forcing a process to run as a single thread, this is usefull if you have a 
			long running cron job, but don't want to run a second copy becuase it could overlap ( cause a race condition )
			with the first copy.
			
			An added benifit is if the script dies for some reason, the next time cron runs it can lock the process and so 
			it starts a new instance of the script.

*/
header('Content-Type:text/plain'); //plain text
require __DIR__.'/streamoutput.php';
if( !isset( $inculded ) ){
	//make sure streaming is on
	exit();
}

require __DIR__.'/../ProcLock.php';

$lockfile = __DIR__.'/cronlock.lock';

echo "lock file: $lockfile\n";

$expire = 60 * 5;  //5 minutes  ( this simulate some amount of working takeing 5 minutes )
set_time_limit(($expire+60)); //set max running time loger then script execution

$chunk = 5; //interval chunk ( this simulates serverl small jobs taking 5 seconds each )

$maxLifetime =  60 * 4; //max lifetime ( the script will die after this time )
$starttime = time();   //current time.

ProcLock::setLockFile($lockfile); //just sets the lock file so we don't have to keep putting it in.
ProcLock::lock();

echo "Starting Process.\n";
flush();

while( $expire > 0 ){
	echo "$expire/s remaining \n";
	sleep( $chunk );
	$expire -= $chunk;
	
	if( !ProcLock::getLastAccess() ){
		//if the lockfile is deleted then last access is false.
		echo "Lock file removed.\n";
		exit(0);
	}
	
	if( $starttime + $maxLifetime < time()){
		//if the max lifetime is ran out exit.
		echo "Max Lifetime exceeded.\n";
		exit(0);
	}
}

