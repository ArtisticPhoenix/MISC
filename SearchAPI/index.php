<?php
require_once __DIR__.'/SearchAPI.php';

/*
    query = {
    	"groupOp":"AND",
    	"group" : [{
        	   "groupOp":"OR",
        	   "expr":[{
    				"field":"id",
    				"op":"eq",
    				"data":"2"
        		},{
    				"field":"id",
    				"op":"eq",
    				"data":"2"
        		}]
        	},{
    	         "groupOp":"AND",
    	           "expr":[{
    				    "field":"created_date",
    				    "op":"lte",
    				    "data":"2017-10-01"
                    },{
    				    "field":"created_date",
    				    "op":"gte",
    				    "data":"2017-10-01"
    			     }]
    		}],
    	}

 */


