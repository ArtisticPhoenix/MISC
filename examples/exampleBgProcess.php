<?php
//url: http://localhost/MISC/examples/exampleBgProcess.php
//cli> php -f "{your path}\MISC\examples\exampleBgProcess.php"
/*
 * 
(c) 2017 Hugh Durham III

For license information please view the LICENSE file included with this source code.

Tests
	1. Run this file, wait ( 60 seconds ) check for backgroud process to make file.
	
Conclusions
	When this file is ran it will spawn a background process.  That process will run for 1 minute and then output a file.
	This is non-blocking, so in that one minute time, this script will continue on ( no blocked ) outputing the expired time

*/

header('Content-Type:text/plain'); //plain text
require __DIR__.'/streamoutput.php';
if( !isset( $inculded ) ){
	//make sure streaming is on
	exit();
}
require __DIR__.'/../BgProcess.php';

$testFile = __DIR__.'/testFile.txt';
if(@unlink( $testFile )){
	//remove the file if it exists.
	echo "Deleted $testFile\n";
}

$bgProcess = __DIR__.'/bgProcess.php'; //php script to run as the background process

$BG = new BgProcess($bgProcess, "one", "two", "buckle", "my", "shoe");

$expire = 90;  //one and a half minutes, bgProcess.php will write the file after 1 minute
set_time_limit(($expire+60));
$chunk = 5; //ten second chunk

while( $expire > 0 ){
	echo $expire."\n";
	
	sleep($chunk);
	$expire -= $chunk;
	
	if( $expire == 30 )
		echo "Check for file at ".__DIR__."/testFile.php"; //bgProcess.php runs for one minute ( 90-60=30 )

}