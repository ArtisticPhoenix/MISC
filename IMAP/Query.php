<?php
namespace Lib\Email\IMAP;

class Query{
	
	/**
	 * 
	 * @var GmailClient
	 */
	protected $_GmailClient;
	
	/**
	 * 
	 * @param GmailClient $GmailClient
	 */
	public function __construct( GmailClient $GmailClient ){
		$this->_GmailClient = $GmailClient;	
	}
	
	/**
	 * 
	 * @param string $criteria
	 * 	A string, delimited by spaces, Any multi-word arguments (e.g.
	 * 	FROM "joey smith") must be quoted. Results will match
	 * 	all criteria entries. Use the SEARCH_* constants
	 * @param int $options 
	 * bitmask SE_UID, or SE_FREE
	 * @param string $charset
	 * @return Result
	 */
	public function search(  $criteria, $options = null, $charset = null  ){
		if( $charset ){
			$res = imap_search( $this->_GmailClient->getConnection(), $criteria, $options, $charset);
		}else{
			$res = imap_search( $this->_GmailClient->getConnection(), $criteria, $options );
		}
		if( !$res ){
			$res = array();
		}
		//put most recent emails first
		//rsort($res);
		return new Result($this->_GmailClient, $res);
	}
	
	
	
	

	
	
	
	
}