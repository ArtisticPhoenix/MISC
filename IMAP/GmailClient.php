<?php
namespace Lib\Email\IMAP;

use Lib\Exception\Exception;
use Lib\Exception\Code;
use Lib\Exception\ErrorCode;
use Lib\Exception\Severity;
class GmailClient{
	
	  /*==============================================*/
	 /*================= OPEN FLAGS =================*/
	/*==============================================*/
	/**
	 * mailbox access service, default is "imap"
	 * @var string
	 * @example
	 * self::FLAG_SERVICE.self::FLAG_IMAP
	 */
	const FLAG_SERVICE = 'service=';
	
	/**
	 * remote user name for login on the server
	 * @var string
	 */
	const FLAG_USER = 'user=';
	
	/**
	 * remote authentication user; if specified this is the user name whose password is used (e.g. administrator)
	 * @var string
	 */
	const FLAG_AUTHUSER = 'authuser=';
 	
	/**
	 * remote access as anonymous user
	 * @var string
	 */
	const FLAG_ANONYMOUS = 'anonymous';
	
	/**
	 * record protocol telemetry in application's debug log
	 * @var string
	 */
	const FLAG_DEBUG = 'debug';
	
	/**
	 * do not transmit a plaintext password over the network
	 * @var string
	 */
	const FLAG_SECURE = 'secure';
	
	/**
	 * equivalent to /service=imap
	 * @var string
	 */
	const FLAG_IMAP = 'imap';
	
	/**
	 * equivalent to /service=imap
	 * @var string
	 */
	const FLAG_IMAP2 = 'imap2';
	
	/**
	 * equivalent to /service=imap
	 * @var string
	 */
	const FLAG_IMAP2BIS = 'imap2bis';
	
	/**
	 * equivalent to /service=imap
	 * @var string
	 */
	const FLAG_IMAP4 = 'imap4';
	
	/**
	 * equivalent to /service=imap
	 * @var string
	 */
	const FLAG_IMAP4REV1 = 'imap4rev1';
	
	/**
	 * equivalent to /service=pop3
	 * @var string
	 */
	const FLAG_POP3 = 'pop3';
	
	/**
	 * equivalent to /service=nntp
	 * @var string
	 */
	const FLAG_NNTP = 'nntp';
	
	/**
	 * do not use rsh or ssh to establish a preauthenticated IMAP session
	 * @var string
	 */
	const FLAG_NORSH = 'norsh';
	
	/**
	 * use the Secure Socket Layer to encrypt the session
	 * @var string
	 */
	const FLAG_SSL = 'ssl';	
	
	/**
	 * validate certificates from TLS/SSL server (this is the default behavior)
	 * @var string
	 */
	const FLAG_VALIDATE_CERT = 'validate-cert';
	
	/**
	 * do not validate certificates from TLS/SSL server, needed if server uses self-signed certificates
	 * @var string
	 */
	const FLAG_NOVALIDATE_CERT  = 'novalidate-cert';	
	
	/**
	 * force use of start-TLS to encrypt the session, and reject connection to servers that do not support it
	 * @var string
	 */
	const FLAG_TLS = 'tls';
	
	/**
	 * do not do start-TLS to encrypt the session, even with servers that support it
	 * @var string
	 */
	const FLAG_NOTLS = 'notls';
		
	/**
	 * request read-only mailbox open (IMAP only; ignored on NNTP, and an error with SMTP and POP3)
	 * @var string
	 */
	const FLAG_READONLY = 'readonly';

	
	/*==============================================*/
	/*============== OPEN OPTIONS ==============*/
	/*==============================================*/
	/**
	 * Open mailbox read-only
	 * @var int ( bitmask )
	 */
	const OP_READONLY = OP_READONLY;
	
	/**
	 * Don't use or update a .newsrc for news (NNTP only)
	 * @var int ( bitmask )
	 */
	const OP_ANONYMOUS = OP_READONLY; 
    
	/**
	 * For IMAP and NNTP names, open a connection but don't open a mailbox.
	 * @var int ( bitmask )
	 */
    const OP_HALFOPEN = OP_HALFOPEN; 
	
    /**
	 * Expunge mailbox automatically upon mailbox close (see also imap_delete() and imap_expunge()
	 * @var int ( bitmask )
	 */
    const CL_EXPUNGE = CL_EXPUNGE;
   
    /**
	 * Expunge mailbox automatically upon mailbox close (see also imap_delete() and imap_expunge())
     * alias of self::CL_EXPUNGE ( for normalization reasons )
	 * @var int ( bitmask )
	 */
    const OP_EXPUNGE = CL_EXPUNGE; 
	
    /**
	 * Debug protocol negotiations
	 * @var int ( bitmask )
	 */
    const OP_DEBUG = OP_DEBUG;
	
    /**
	 * Short (elt-only) caching
	 * @var int ( bitmask )
	 */
    const OP_SHORTCACHE = OP_SHORTCACHE;
	
    /**
	 * Don't pass up events (internal use)
	 * @var int ( bitmask )
	 */
    const OP_SILENT = OP_SILENT;
    
    /**
	 * Return driver prototype
	 * @var int ( bitmask )
	 */
    const OP_PROTOTYPE = OP_PROTOTYPE;
    
    /**
	 * Don't do non-secure authentication
	 * @var int ( bitmask )
	 */
    const OP_SECURE = OP_SECURE;
    
    /*==============================================*/
    /*============== CONNECTION PARAM CONSTANTS ==============*/
    /*==============================================*/
    /**
     * Disable authentication properties
     * @var string
     */
    const PARAM_DISABLE_AUTHENTICATOR = 'DISABLE_AUTHENTICATOR';
	
	
	/*==============================================*/
	/*============== SEARCH CONSTANTS ==============*/
	/*==============================================*/
	/**
	 * DateTime format string 8 August 2008, for searches
	 * @var string
	 */
	const DATE_FORMAT = 'n F YY';
	
	/**
	 * - return all messages matching the rest of the criteria
	 * @var string
	 */
	const SEARCH_ALL = 'ALL';
	
	/**
	 * - match messages with the \\ANSWERED flag set
	 * @var string
	 */
	const SEARCH_ANSWERED = 'ANSWERED';
	
	/**
	 * "string" - match messages with "string" in the Bcc: field
	 * @var string
	 */
	const SEARCH_BCC  = 'BCC';
	
	/**
	 * "date" - match messages with Date: before "date"
	 * @var string
	 */
	const SEARCH_BEFORE  = 'BEFORE';
	
	/**
	 * "string" - match messages with "string" in the body of the message
	 * @var string
	 */
	const SEARCH_BODY  = 'BODY';
	
	/**
	 * "string" - match messages with "string" in the Cc: field
	 * @var string
	 */
	const SEARCH_CC  = 'CC';
	
	/**
	 * - match deleted messages
	 * @var string
	 */
	const SEARCH_DELETED  = 'DELETED';
	
	/**
	 * - match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
	 * @var string
	 */
	const SEARCH_FLAGGED  = 'FLAGGED';
	
	/**
	 * "string" - match messages with "string" in the From: field
	 * @var string
	 */
	const SEARCH_FROM  = 'FROM';
	
	/**
	 * "string" - match messages with "string" as a keyword
	 * @var string
	 */
	const SEARCH_KEYWORD  = 'KEYWORD';
	
	/**
	 * - match new messages
	 * @var string
	 */
	const SEARCH_NEW  = 'NEW';
	
	/**
	 * - match old messages
	 * @var string
	 */
	const SEARCH_OLD  = 'OLD';
	
	/**
	 * "date" - match messages with Date: matching "date"
	 * @var string
	 */
	const SEARCH_ON  = 'ON';
	
	/**
	 * - match messages with the \\RECENT flag set
	 * @var string
	 */
	const SEARCH_RECENT  = 'RECENT';
	
	/**
	 * - match messages that have been read (the \\SEEN flag is set)
	 * @var string
	 */
	const SEARCH_SEEN  = 'SEEN';
	
	/**
	 * "date" - match messages with Date: after "date"
	 * @var string
	 */
	const SEARCH_SINCE  = 'SINCE';
	
	/**
	 * "string" - match messages with "string" in the Subject:
	 * @var string
	 */
	const SEARCH_SUBJECT  = 'SUBJECT';
	
	/**
	 * "string" - match messages with text "string"
	 * @var string
	 */
	const SEARCH_TEXT  = 'TEXT';
	
	/**
	 * "string" - match messages with "string" in the To:
	 * @var string
	 */
	const SEARCH_TO  = 'TO';
	
	/**
	 * - match messages that have not been answered
	 * @var string
	 */
	const SEARCH_UNANSWERED  = 'UNANSWERED';
	
	/**
	 * - match messages that are not deleted
	 * @var string
	 */
	const SEARCH_UNDELETED  = 'UNDELETED';
	
	/**
	 * - match messages that are not flagged
	 * @var string
	 */
	const SEARCH_UNFLAGGED  = 'UNFLAGGED';
	
	/**
	 * "string" - match messages that do not have the keyword "string"
	 * @var string
	 */
	const SEARCH_UNKEYWORD  = 'UNKEYWORD';
	
	/**
	 * - match messages which have not been read yet
	 * @var string
	 */
	const SEARCH_UNSEEN  = 'UNSEEN';
	

	/*==============================================*/
	/*============= RESULT FETCH TYPES =============*/
	/*==============================================*/
	/**
	 * return the raw sequence numbers
	 * @var integer
	 */
	const FETCH_SEQUENCE = 1;
	
	/**
	 * return the email in as a message object
	 * @var integer
	 */
	const FETCH_EMAIL = 2;
	
	
	/*==============================================*/
	/*=============== GMAIL MAILBOXES ==============*/
	/*==============================================*/
	const MAILBOX_INBOX  		= 'INBOX';
	const MAILBOX_ALL  			= '[Gmail]/All Mail';
	const MAILBOX_DRAFTS  		= '[Gmail]/Drafts';
	const MAILBOX_IMPORTANT  	= '[Gmail]/Important';
	const MAILBOX_SENT 			= '[Gmail]/Sent Mail';
	const MAILBOX_SPAM  		= '[Gmail]/Spam';
	const MAILBOX_STARRED 		= '[Gmail]/Starred';
	const MAILBOX_TRASH  		= '[Gmail]/Trash';
	
	
	/**
	 * IMAP stream
	 * @var resource
	 */
	protected $_connection;
	
	/**
	 * imap connection string {host:port/flags}
	 * @var string
	 */
	protected $_connection_string = '';
	
	
	/**
	 * 
	 * @param string $hostname
	 * @param string $username
	 * @param string $password
	 * @param string $mailbox	[INBOX]
	 * @param number $port		[993]
	 * @param array $flags		[array('imap', 'ssl')]
	 * @param number $options	[null]
	 * bitmask use OP_READONLY for testing
	 * 
	 * @param number $n_retries	[null]
	 * @param array $params		[array()]
	 * @throws Exception
	 */
	public function __construct($hostname, $username, $password, $mailbox = self::MAILBOX_INBOX, $port = '993', array $flags = [], $options = null, $n_retries = null, array $params = []){
		if(!function_exists('imap_open')){
			throw new Exception('', ErrorCode::IMAP_NOT_ENABLED, Severity::E_USER_ERROR);
		}
		
		$this->connect($hostname, $username, $password, $mailbox, $port, $flags, $options, $n_retries, $params);
	}
	
	/**
	 * 
	 * @param string $hostname
	 * @param string $username
	 * @param string $password
	 * @param string $port
	 * @param string $mailbox
	 * @param string $flags
	 * @param string $options 
	 * @param number $n_retries
	 * @param array $params
	 */
	public function connect($hostname, $username, $password, $mailbox = self::MAILBOX_INBOX, $port = '993', array $flags = [], $options = null, $n_retries = null, array $params = []) {
		if( $this->_connection ){
			return;
		}
		
		$connection_string = $this->createConnectionString($hostname, $port, $flags);
		$connection_string .= $mailbox;
		
		print_rr( $connection_string );
		
		if( false === ( $this->_connection = @imap_open($connection_string, $username, $password, $options, $n_retries, $params ) ) ){
			throw new Exception( imap_last_error(), ErrorCode::IMAP_ERROR, Severity::E_USER_ERROR );
		}	
	}
	
	public function disconnect(){
		if( $this->_connection ){
			imap_close( $this->_connection );
			$this->_connection = false;
		}
	}
	
	/**
	 * 
	 * @return \Lib\Email\IMAP\resource
	 */
	public function getConnection(){
		return $this->_connection;
	}
	
	/**
	 * 
	 * @param string $hostname
	 * @param string $port
	 * @param array $flags
	 * @return string
	 */
	public function createConnectionString( $hostname, $port = '993', array $flags = []){
//		print_rr( $flags );
		if(!empty($flags)){
			$flags = '/'.implode('/', $flags);
		}else{
			$flags = '/'.self::FLAG_IMAP.'/'.self::FLAG_SSL;
		}
		$this->_connection_string = '{'.$hostname.':'.$port.$flags.'}';
		return $this->_connection_string;
	}
	
	/**
	 * @return Query
	 */
	public function getQuery(){
		return new Query( $this );
	}
	
	/**
	 * 
	 */
	public function __destruct(){
		$this->disconnect();
	}
	
}