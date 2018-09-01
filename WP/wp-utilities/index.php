<?php
/*
 * Disclamer - 
 * Use at your own risk.
 * 
 * It is not recomended to leave this file on the server as it can expose sensative information such as Database credentials.
 * This file requiers that exce() is avalible to the PHP user.
 * 
 * 
 * 
 */

ini_set('display_errors', 1);
error_reporting(-1);

if(!function_exists('exec')) throw new Exception('Function exec() does not exist');

include_once realpath(__DIR__.DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR.'wp-config.php';


$conf = [
    'Prefix'    => $table_prefix,
    'Host'      => DB_HOST,
    'Database'  => DB_NAME,
    'User'      => DB_USER,
    'Password'  => DB_PASSWORD
];

$message = "";

if(isset($_POST['export'])){
    $filename = date('Y-m-d').'_wp_dump.sql';
    $pathname =  __DIR__.'/'.$filename;
    $command = "mysqldump --host={$conf['Host']} --user={$conf['User']} --password={$conf['Password']} {$conf['Database']} > {$pathname}";
 
    exec($command, $output, $worked);

    switch($worked){
        case 0:
            if(!isset( $_GET['import'] ) && is_file($filename)){
                echo  'There was a file write error during the database backup';
            }else{
                header("Cache-Control: public"); // needed for internet explorer
                header("Content-Type: text/plain");
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length:".filesize($pathname));
                header("Content-Disposition: attachment; filename=$filename");
                readfile($pathname);
                unlink($pathname);
                
                header("Refresh:0");
                exit;
            }
            break;
        case 1:
            $message =  'There was an error during the database backup';
            break;
        case 2:
            $message =  'There was an error during during the database backup';
            break;
    }
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Wordpres Utilities</title>
	</head>
	<body>
	
		<p><?php echo $message; ?></p>
		
		<div style="width:1000px;margin:30px auto;" >
			<h1>A simple utillity to export a wordpress database</h1>
		
		
			<form method="post" action="" style="background-color:#EEE;padding:20px;" >
				<table>
					<tr>
						<th style="width:100px;text-align:left;">Option</th>
						<th>Value</th>
					</tr>
					<?php foreach ($conf as $k=>$v): ?>
					<tr>
						<td style="width:100px;text-align:right;"><strong><?php echo $k; ?>:</strong></td>
						<td><?php echo $v; ?></td>
					</tr>
					<?php endforeach;?>
				</table>
				<input type="submit" name="export" value="Export Db" />
			</form>
		</div>
		
		
	</body>
</html>
