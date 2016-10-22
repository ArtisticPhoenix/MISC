<?php
/*
(c) 2017 Hugh Durham III
 
For license information please view the LICENSE file included with this source code. 
Do not run this file directly instead run exampleBgProcess.php

a minimul example of a background process ( non-blocking )

this file will leave a small text file at the very end of its execution, this is to prove it runs as a background job

A background job ( non-blocking ) is a script that runs in a seperate execution thread then the script that executed it.
url: http://localhost/MISC/examples/bgProcess.php

*/

//this file will be created when the script finishes 
$testFile = __DIR__.'/testFile.txt';

if(@unlink( $testFile )){
	//remove the file if it exists.
	echo "Deleted $testFile\n";
}

sleep( 60 ); //seep for 1 minut so we can see what is going on.

file_put_contents($testFile, 'Success! Input variables: '.implode(', ', $argv));


