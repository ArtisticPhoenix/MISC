<pre>
<?php
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '1');
    
    $result = $_POST['input'] ?? '';
    if('POST' == $_SERVER['REQUEST_METHOD']){

        require_once 'lib/Converter.php';
        $input = $_POST['input'];
        
        $Converter = new Converter();
        $inputType = $Converter->detectOutputType($input);
        switch ($inputType){
            case Converter::VAR_DUMP:
                $result = $Converter->convertFromVarDump($input);
            break;
            case Converter::PRINT_R:
                $result = $Converter->convertFromPrintR($input);
            break;
        }  
    }
    
    
    
?>
</pre>
<!DOCTYPE html>
<html>
	<head>
		<title>PHP Ouput Converter</title>
		<style type="text/css" >
		  table th, table td{
		      border : 1px solid black;
		  }
		  button {
		      font-weight: bold;
		      padding: 10px;
		      font-size: 22px;
		  }
		  code {
		      background: #EEE;
		      border: 1px solid #DDD;
		      display: block;
		      white-space: pre;
		      font-style: normal;
		  }
		  
		  
		</style>
	</head>

	<body>
		<div style="margin:20px; width:1000px;margin:auto;">
			<h1>Ouput Converter</h1>
			<h3>Converts various types of output to a pastable PHP array</h3>
             <dl>
              <dt>Accepts the following input types:</dt>
              <dd> - Output from var_dump</dd>
              <dd>
              	 - Output from print_r<br>
              </dd>
            </dl> 
			
    		<form method="post" style="width:100%" >
        		<textarea name="input" style="max-width:1000px;min-width:1000px;height:500px;"><?php echo $result;?></textarea>
        		<button type="submit">Convert!</button>
        	</form>
        	<div style="margin-top: 30px;" >
        	 <em>Because of the freeform nature of print_r, some limitations apply. For example parsing values that result from things like this:<br>
              	 <code>
    print_r(["
        foo [1] => bar
    "]);
              	 </code>
				 Which results in the following output: <br>
				 <code>
    Array
    (
        [0] => foo
    [1] => bar
    )		 
    			</code>
    			Is practially indistinguishable from:
    			<code>
    print_r(["foo","bar"]);
              	</code>
              	Which results in the following output: <br>
              	<code>
    Array
    (
        [0] => foo
        [1] => bar
    )		 
    			</code>
    			Whitespace cannot be relied upon. So I have simply done my best to account for these things. 
    			<br><br>
    			Enjoy!
        	 </em>
        	</div>
    	</div>
    	
	</body>
</html>
<html>

