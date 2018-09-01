<?php
/*
 *
 * (c) 2016 ArtisticPhoenix
 *
 * For license information please view the LICENSE file included with this source code.
 *
 * This script can take a large MySQL dump file and extract a single table from it
 * It can be used either from the CLI (command line), or from HTTP:
 * obviously it will run better from the CL
 *
 * Command line Arguments
 * php "{path}/extract_table/index.php" "input" "table" "extract"
 * extract can be a pipe seperated list "structure|data|index" 
 *
 * Http Arguments (or you can use the simple web from for submission)
 * http://localhost/extract_table?filename=test.sql&table=sometable&extract[]=structure&extract[]=index
 *
 * In both cases (cli or http) you can also set these constants to manually override the dynamic inputs
 * OVERRIDE_FILENAME, OVERRIDE_TABLE, OVERRIDE_EXTRACT 
 * like the CLI extract can be pipe seperated or an array in PHP7+ 
 * @todo: add support for multiple tables
*/
error_reporting(-1);
ini_set('display_errors', 1);

//setup constants
define('EXTRACT_STRUCTURE', 'structure');
define('EXTRACT_DATA', 'data');
define('EXTRACT_INDEX', 'index');

define('MODE_INIT', 'mode_init');
define('MODE_SKIP', 'mode_skip');
define('MODE_ACTIVE', 'mode_active');

define('IS_CLI', php_sapi_name() == "cli");

define('DEBUG', true);

//Comment to allow input arguments
/*define('OVERRIDE_FILENAME', '');
define('OVERRIDE_TABLE', '');
define('OVERRIDE_EXTRACT', '');
/**/

function debug($var = null, $offset=0){
	if(!DEBUG) return;
	
	$stacktrace = debug_backtrace(false);
	$trace = $stacktrace[$offset];
	extract($trace);
	$head = '<span style="color: #CCC;">%s</span>';
	$body = '<pre>%s</pre>';
	$headMessage = "Output from FILE[ $file ] LINE[ $line ]";

	$bodyMessage = var_export($var, true);

	if(!IS_CLI){
		$head = sprintf($head, $headMessage);
		$body = sprintf($body, $bodyMessage);
	}else{
		$head = $headMessage."\n";
		$body = $bodyMessage."\n\n";
	}
	echo $head;
	echo $body;
}

$s_line = str_pad('', 100, '-');
$e_line = str_pad('', 100, '=');

debug('Is CLI:'.(IS_CLI ? 'true' : 'false'));

//set defaults
$showHtml = true;	

if (IS_CLI) {
	//Command line mode
	//php "{path}/extract_table/index.php"
	//php "{path}/extract_table/index.php" "filename" "table" "extract"
	///php "{path}/extract_table/index.php"
	
	$showHtml = false; //never show HTML form
	
	array_shift($argv); //argv[0] is the name of this file
	
	//allow manual override of args
	$filename = defined('OVERRIDE_FILENAME') ? OVERRIDE_FILENAME : array_shift($argv);
	$table = defined('OVERRIDE_TABLE') ? OVERRIDE_TABLE : array_shift($argv);
	$extract = defined('OVERRIDE_EXTRACT') ? OVERRIDE_EXTRACT : array_shift($argv);
	
}else{
	//HTTP mode
	//http://localhost/extract_table
	//http://localhost/extract_table?filename=test.sql&table=sometable&extract[]=structure&extract[]=index&extract[]=data
	
	$filename = defined('OVERRIDE_FILENAME') ? OVERRIDE_FILENAME : !empty($_GET['filename']) ? $_GET['filename'] : false;
	$table = defined('OVERRIDE_TABLE') ? OVERRIDE_TABLE : !empty($_GET['table']) ? $_GET['table'] : false;
	$extract = defined('OVERRIDE_EXTRACT') ? OVERRIDE_EXTRACT : !empty($_GET['extract']) ? $_GET['extract'] : false;
	
	//if these are true then we proccess it!
	if($filename && $table && $extract){
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Turn off PHP output compression
		ini_set('zlib.output_compression', false);
		
		//Flush (send) the output buffer and turn off output buffering
		while (@ob_end_flush());
		
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
		$showHtml = false;
	}
}


debug("Arg:filename {$filename}");
debug("Arg:table {$table}");
debug("Arg:extract ".(is_array($extract) ? impode("|", $extract) : $extract));

if($showHtml): ?>
<!DOCTYPE html>
<html>
	<head>
		<title>Extract table from SQL Dump</title>
		
		<style type="text/css" >
			input[type="text"],
			input[type="submit"]
			{
				padding:5px 10px;
				font-size:22px;
			}
			
			label{
				font-size:22px;
			}
			
			input[type="checkbox"]{
				transform: scale(1.5);
				margin-right: 10px;
			}
			
			.row{
				margin: 15px auto;
			} 
		
		</style>
	</head>
	<body>	
		<div style="width:500px;margin:50px auto;" >
			<form method="get" style="background:#CCC;padding:15px;border:1px solid #000;">
				<h2 style="margin: 5px 0;font-size:36px;" >Please enter a table to extract.</h2>
				<div class="row" >
					<input type="text" name="filename" value="" placeholder="Input File Name" style="width:340px;" />
				</div>
				<div class="row" >
					<input type="text" name="table" value="" placeholder="Table Name" style="width:340px;" />
					<input type="submit" name="submit" value="Submit" />
				</div>
				<div class="row" >
					<label>Structure Only:<input type="checkbox" name="extract[]" value="<?php echo EXTRACT_STRUCTURE; ?>" checked="checked" /></label>
					<label>Data Only:<input type="checkbox" name="extract[]" value="<?php echo EXTRACT_DATA; ?>" checked="checked" /></label>
					<label>Indexes Only:<input type="checkbox" name="extract[]" value="<?php echo EXTRACT_INDEX; ?>" checked="checked" /></label>
				</div>
			</form>
		</div>
	</body>	
</html>
<?php 
	exit();
endif;
//PROCESS
set_time_limit(0);

if (!IS_CLI) echo "<pre>";


if(empty($filename)) die('ERROR: Arg filename cannot be empty!');

$filename = str_replace("\\", "/", $filename);

if(!file_exists($filename)) die('ERROR: File not found['.$filename.']!');

if(empty($table)) die('ERROR: Arg table cannot be empty!');
if(empty($extract)) die('ERROR: Arg extract cannot be empty!');

if(!is_array($extract)) $extract = array_filter(array_map('trim',explode('|', $extract)));

//stages must be in this order (as they show in the sql file)
$stages = [
	EXTRACT_STRUCTURE 		=> [],
	EXTRACT_DATA			=> [],
	EXTRACT_INDEX			=> []
];

//Setup our staging
foreach($extract as $ex){
	$stage = [];
	
	switch($ex){
		case EXTRACT_STRUCTURE:
			$stage['search'] = '-- Table structure for table `'.$table.'`';
		break;
		case EXTRACT_DATA:
			$stage['search'] = '-- Dumping data for table `'.$table.'`';
		break;
		case EXTRACT_INDEX:
			$stage['search'] = '-- Indexes for table `'.$table.'`';
		break;
		default:
			die('ERROR: Invalid extract value['.$extract.']!');
	}

	$stage['mode'] = MODE_INIT;
	$stage['name'] = $ex;
	$stages[$ex] = $stage;
}

//remove any empty stages
$stages = array_filter($stages);

//Open input file for reading
$f = fopen($filename, 'r');
$offsets = [];

if(!$f)die('ERROR: Could not open input file['.$filename.'] for reading!');

$output_file = str_replace("\\", "/", __DIR__.'/output-'.trim(strrchr($filename,'/'), '/'));

debug("output_file: {$output_file}");

//Open output file for writing
$o = fopen($output_file, 'w');
if(!$o)die('ERROR: Could not open input file['.$filename.'] for reading!');

//set the first active stage
$active_stage = array_shift($stages);

while($active_stage && false !== ($buffer = fgets($f))){
	//debug($buffer);
//	debug($active_stage);
	if($active_stage['mode'] == MODE_INIT){
		//search
		if(0 === ($pos = strpos($buffer, $active_stage['search']))){
			debug("{$s_line}\nSTART[{$active_stage['name']}]:{$active_stage['search']}\n{$e_line}");
			debug("{$buffer}\n{$s_line}");
			//if the line starts with $active_stage['search'], write it and switch modes
			$active_stage['mode'] = MODE_SKIP;
			fwrite($o, "--\n");
			fwrite($o, $buffer);
			fwrite($o, fgets($f, 4)); //get the last -- in the comment block, so it doesn't trigger below
		}
	}else{
		//write
		if(0 === ($pos = strpos($buffer, '--'))){
			if($active_stage['mode'] == MODE_SKIP) continue;
			
			debug("{$s_line}\nEND[{$active_stage['name']}]:{$active_stage['search']}\n{$e_line}");
			debug("{$buffer}\n{$s_line}");
			//if a line starts with -- its the start of new comment, so we can close this stage
			$active_stage = array_shift($stages);
		}else{
			$active_stage['mode'] = MODE_ACTIVE;
			//write lines while in this mode
			fwrite($o, $buffer);
		}
	}	
}
echo "Complete!";
