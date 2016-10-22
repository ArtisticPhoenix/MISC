<?php
/*
 (c) 2017 Hugh Durham III

 For license information please view the LICENSE file included with this source code.
 
 this code will set implicit flushing on, instead of waiting for the script to end 
 output will be streamed instead, this is not needed for locking but makes it easier to see 
 whats going on by not waiting for the output.
 */
//------------- START::STREAM OUTPUT ------------
if( !headers_sent() ){
	//set text content type on CLI
	header('Content-Type: text/plain; charset=utf-8');
}
//STREAM OUTPUT FOR CLI
// Turn off output buffering
ini_set('output_buffering', 'off');
// Turn off PHP output compression
ini_set('zlib.output_compression', false);
// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);
// Clear, and turn off output buffering
while (ob_get_level()) {
	// Get the curent level
	$level = ob_get_level();
	// End the buffering
	ob_end_clean();
	// If the current level has not changed, abort
	if (ob_get_level() == $level) break;
}
// Disable apache output buffering/compression
if (function_exists('apache_setenv')) {
	apache_setenv('no-gzip', '1');
	apache_setenv('dont-vary', '1');
}
//------------- END::STREAM OUTPUT ---------------------

$inculded = true;