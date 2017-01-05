<?php
namespace Lib\Email\IMAP;

class Result implements \Countable{
	
	/*======== Message structure types ========*/
	/*( convert php defined constants - for readabillity inside class)*/
	/**
	 * = 0
	 * @var int
	 */
	const TYPE_TEXT 			= TYPETEXT;
	
	/**
	 * = 1
	 * @var int
	 */
	const TYPE_MULTIPART 		= TYPEMULTIPART;
	
	/**
	 * = 2
	 * @var int
	 */
	const TYPE_MESSAGE 			= TYPEMESSAGE;
	
	/**
	 * = 3
	 * @var int
	 */
	const TYPE_APPLICATION 		= TYPEAPPLICATION;
	
	/**
	 * = 4
	 * @var int
	 */
	const TYPE_AUDIO 			= TYPEAUDIO;
	
	/**
	 * = 5
	 * @var int
	 */
	const TYPE_IMAGE 			= TYPEIMAGE;
	
	/**
	 * = 6
	 * @var int
	 */
	const TYPE_VIDEO 			= TYPEVIDEO;
	
	/**
	 * = 7
	 * @var int
	 */
	const TYPE_MODEL 			= TYPEMODEL;
	
	/**
	 * = 8
	 * @var int
	 */
	const TYPE_OTHER 			= TYPEOTHER;
	
	
	/*======== Message encoding types ========*/
	/*( convert php defined constants - for readabillity inside class)*/
	/**
	 * = 0
	 * @var int
	 */
	const ENC_7BIT				= ENC7BIT;
	
	/**
	 * = 1
	 * @var int
	 */
	const ENC_8BIT				= ENC8BIT;
	
	/**
	 * = 2
	 * @var int
	 */
	const ENC_BINARY			= ENCBINARY;
	
	/**
	 * = 3
	 * @var int
	 */
	const ENC_BASE64			= ENCBASE64;
	
	/**
	 * = 4
	 * @var int
	 */
	const ENC_QUOTEDPRINTABLE 	= ENCQUOTEDPRINTABLE;
	
	/**
	 * = 5
	 * @var int
	 */
	const ENC_OTHER				= ENCOTHER;
	

	/*======== Mail Structure Keys =======*/
	/**
	 * Primary body type ( matches the TYPE_* constants )
	 * @var int
	 */
	const KEY_TYPE = 'type';
	
	/**
	 * Body transfer encoding ( matches the ENC_* constants )
	 * @var int
	 */
	const KEY_ENCODING = 'encoding';
	
	/**
	 * TRUE if there is a subtype string
	 * @var bool
	 */
	const KEY_IFSUBTYPE = 'ifsubtype';
	
	/**
	 * MIME subtype
	 * @var string
	 */
	const KEY_SUBTYPE = 'subtype';
	
	/**
	 * TRUE if there is a description string
	 * @var bool
	 */
	const KEY_IFDESCRIPTION = 'ifdescription';	
	
	/**
	 * Content description string
	 * @var string
	 */
	const KEY_DESCRIPTION  = 'description';		
	
	/**
	 * TRUE if there is an identification string
	 * @var string
	 */
	const KEY_IFID = 'ifid';	 	
	
	/**
	 * Identification string
	 * @var string
	 */
	const KEY_ID = 'id';	 	
	
	/**
	 * Number of lines
	 * @var string
	 */
	const KEY_LINES = 'lines';	 	
	
	/**
	 * Number of bytes
	 * @var string
	 */
	const KEY_BYTES = 'bytes';  	
	
	/**
	 * TRUE if there is a disposition string
	 * @var string
	 */
	const KEY_IFDISPOSITION = 'ifdisposition'; 	
	
	/**
	 * Disposition string
	 * @var string
	 */
	const KEY_DISPOSITION = 'disposition'; 	
	
	/**
	 * TRUE if the dparameters array exists
	 * @var string
	 */
	const KEY_IFDPARAMETERS = 'ifdparameters'; 	
	
	/**
	 * An array of objects where each object has an "attribute" and a "value" property corresponding to the parameters on the Content-disposition MIME header.
	 * @var string
	 */
	const KEY_DPARAMETERS = 'dparameters'; 
	
	/**
	 * TRUE if the parameters array exists
	 * @var string
	 */
	const KEY_IFPARAMETERS = 'ifparameters';	 	
	
	/**
	 * An array of objects where each object has an "attribute" and a "value" property.
	 * @var string
	 */
	const KEY_PARAMETERS = 'parameters';	 	
	
	/**
	 * An array of objects identical in structure to the top-level object, each of which corresponds to a MIME body part.
	 * @var string
	 */
	const KEY_PARTS = 'parts'; 	
	
	/**
	 * parameter type field ( such as for dparameters or parameters )
	 * attributes apear to be array(array('attribute' => 'NAME', 'value' => 'content')); pair setup
	 * @var string
	 */
	const KEY_ATTRIBUTE = 'attribute';
		/**
		 * 
		 * @var string
		 */
		const KEY_ATTRIBUTE_NAME = 'NAME';
		/**
		 * 
		 * @var string
		 */
		const KEY_ATTRIBUTE_FILENAME = 'FILENAME';
		/**
		 * 
		 * @var string
		 */
		const KEY_ATTRIBUTE_VALUE = 'value';
	
	
	/*======== OTHER KEYWORDS =======*/
	
	const DISPOSITION_ATTACHMENT = 'ATTACHMENT';
	
	
	
	/*======== Mimes =======*/
	/* MIMES we need to be on the lookout for - these corrispond to the KEY_TYPE and KEY_SUBTYPE */
	
	const MIME_TEXT 						= 'TEXT';
	// ---------- text sub - type  - : for escaping reasons --------//
		const MIME_TEXT_HTML 					= 'TEXT:HTML';
		const MIME_TEXT_PLAIN 					= 'TEXT:PLAIN';
		
	
	const MIME_MULTIPART 					= 'MULTIPART';
	// ---------- text sub - type  - double \\ for escaping reasons --------//
		const MIME_MULTIPART_MIXED 				= 'MULTIPART:MIXED';
		const MIME_MULTIPART_RELATED 			= 'MULTIPART:RELATED';
		const MIME_MULTIPART_ALTERNATIVE 		= 'MULTIPART:ALTERNATIVE';
	
	
	const MIME_MESSAGE 						= 'MESSAGE';
	

	const MIME_APPLICATION 					= 'APPLICATION';
	// ---------- text sub - type  - double \\ for escaping reasons --------//
		const MIME_APPLICATION_PDF 				= 'APPLICATION:PDF';
		const MIME_APPLICATION_MSWORD			= 'APPLICATION:MSWORD';
		const MIME_APPLICATION_MSWORD_DOCX 		= 'APPLICATION:VND.OPENXMLFORMATS-OFFICEDOCUMENT.WORDPROCESSINGML.DOCUMENT';

	const MIME_AUDIO 				= 'AUDIO';
	const MIME_IMAGE 				= 'IMAGE';
	const MIME_VIDEO 				= 'VIDEO';
	const MIME_MODEL 				= 'MODEL';
	const MIME_OTHER 				= 'OTHER';
	
	
	/**
	 * Primary Mime map for Message Types
	 * @var array
	 */
	public static $PRIMARY_MIME_TYPES = array(
		self::TYPE_TEXT 			=> self::MIME_TEXT,
		self::TYPE_MULTIPART 		=> self::MIME_MULTIPART,
		self::TYPE_MESSAGE 			=> self::MIME_MESSAGE,
		self::TYPE_APPLICATION 		=> self::MIME_APPLICATION,
		self::TYPE_AUDIO 			=> self::MIME_AUDIO,
		self::TYPE_IMAGE 			=> self::MIME_IMAGE,
		self::TYPE_VIDEO 			=> self::MIME_VIDEO,
		self::TYPE_MODEL 			=> self::MIME_MODEL,
		self::TYPE_OTHER 			=> self::MIME_OTHER
	);

	/**
	 *
	 * @var array
	 */
	protected $_message_sequence = array();
	
	/**
	 *
	 * @var GmailClient
	 */
	protected $_GmailClient;
	
	/**
	 * 
	 * @param GmailClient $GmailClient
	 * @param array $message_sequence
	 */
	public function __construct( GmailClient $GmailClient, array $message_sequence){
		$this->_GmailClient = $GmailClient;
		$this->_message_sequence = $message_sequence;
	}
	
	/**
	 * return the current item, advance to the next one
	 * @param int $type
	 * GmailClient::FETCH_* constant
	 */
	public function fetch( $type = GmailClient::FETCH_SEQUENCE ){
		
		$current = current( $this->_message_sequence );
		if( !$current ){
			return false;
		}
		
		$res = false;
		switch ( $type ){
			case GmailClient::FETCH_SEQUENCE:
				$res = $current;
			break;
			case GmailClient::FETCH_EMAIL:
				$res = $this->_create_message( $current );
			break;
		}
		next( $this->_message_sequence );
		return $res;
	}
	
	
	/**
	 * return all
	 * @param int $type 
	 * GmailClient::FETCH_* constant
	 */
	public function fetchAll( $type = GmailClient::FETCH_SEQUENCE ){
		
		if( empty( $this->_message_sequence )){
			return false;
		}
		
		$res = false;
		switch ( $type ){
			case GmailClient::FETCH_SEQUENCE:
				$res = $this->_message_sequence;
			break;
			case GmailClient::FETCH_EMAIL:
				foreach ( $this->_message_sequence as $a){
					$res[] = $this->_create_message( $a );
				}
			break;
		}
		return $res;
	}
	
	/**
	 * 
	 * @param int $sequence_no
	 */
	protected function _create_message( $sequence_no ){
		$imap = $this->_GmailClient->getConnection(); //localize
		
		$overview = imap_fetch_overview($imap, $sequence_no);	
		$message = (array)$overview[0];
		$structure = imap_fetchstructure ( $imap, $sequence_no);
		
		if( $structure ){	
			$this->_add_part($imap, $sequence_no, false, $structure, $message);
		}
			
		return $message;
	}
	
	/**
	 * 
	 * @param resource $imap
	 * @param int $sequence_no
	 * @param string $part_number
	 * @param \stdClass $structure
	 * @param array $parts
	 */
	protected function _add_part($imap, $sequence_no, $part_number = false, $structure, array &$message ){	
		$mime = $this->_get_mime_type($structure);
//		//print_rr( "> EMAIL_ID[ $sequence_no ] PART_NO[ $part_number ] MIME[ $mime ] ----------------------" );
		$attachment = array();
		switch ( $mime ){
			case self::MIME_MULTIPART:
			case self::MIME_MULTIPART_MIXED:
			case self::MIME_MULTIPART_RELATED:
			case self::MIME_MULTIPART_ALTERNATIVE:
				if( $structure->{self::KEY_PARTS} && count($structure->{self::KEY_PARTS}) > 0){
					$c = 1;
					foreach ($structure->{self::KEY_PARTS} as $part ){
						$part_no = '';
						if( $part_number ){
							$part_no .= "{$part_number}.{$c}";
						}else{
							$part_no = $c;
						}
				
						$this->_add_part($imap, $sequence_no, $part_no, $part, $message); // ~ recurse
						++$c;
					}
				}
			break;
			case self::MIME_TEXT_PLAIN:
				$body = $this->decode($structure->{self::KEY_ENCODING}, imap_fetchbody($imap, $sequence_no, $part_number) );
				//print_rr( $structure );
				//print_rr( $body );
				if( (int)$this->_is_attachment($structure) ){
					//print_rr( "IS_ATTACHMENT" );
					$attachment['body'] = $body;
					$this->_set_attachment_meta( $structure, $attachment );
					$message['attachments'][] = $attachment;
				}else{
					$message['text_body'] = $body;
				}
			break;
			case self::MIME_TEXT_HTML:
				$body = $this->decode($structure->{self::KEY_ENCODING}, imap_fetchbody($imap, $sequence_no, $part_number) );		
				//print_rr( $structure );
				//print_rr( $body );
				if( (int)$this->_is_attachment($structure) ){
					//print_rr( "IS_ATTACHMENT" );
					$attachment['body'] = $body;
					$this->_set_attachment_meta( $structure, $attachment );
					$message['attachments'][] = $attachment;
				}else{
					$message['html_body'] = $body;
				}
			break;
			case self::MIME_APPLICATION_PDF:
			case self::MIME_APPLICATION_MSWORD:
			case self::MIME_APPLICATION_MSWORD_DOCX:		
				//these should always be an attachment
				$attachment['body'] = $this->decode($structure->{self::KEY_ENCODING}, imap_fetchbody($imap, $sequence_no, $part_number) );
				/*if( $this->_is_attachment($structure) ){
					print_rr( "IS_ATTACHMENT" );
				}*/				
				$this->_set_attachment_meta( $structure, $attachment );
				$message['attachments'][] = $attachment;
			break;
			default:
				//ignore
		}	
		//print_rr( "---------------------------------------------------- <" );
	}
	
	/**
	 * 
	 * @param stdClass $structure
	 * @param array $attachment
	 * @return array
	 */
	protected function _set_attachment_meta( $structure, array &$attachment ){
		if( (int)$structure->{ self::KEY_IFDPARAMETERS } ){
			foreach($structure->{ self::KEY_DPARAMETERS } as $Attr){
				if($Attr->{self::KEY_ATTRIBUTE} == self::KEY_ATTRIBUTE_FILENAME){
					$attachment['filename'] = $Attr->{self::KEY_ATTRIBUTE_VALUE};
				}
			}
		}else if((int) $structure->{ self::KEY_IFPARAMETERS } ){
			foreach($structure->{ self::KEY_PARAMETERS } as $Attr){
				if($Attr->{self::KEY_ATTRIBUTE} == self::KEY_ATTRIBUTE_NAME){
					$attachment['filename'] = $Attr->{self::KEY_ATTRIBUTE_VALUE};
				}
			}
         }
	}
	
	/**
	 * 
	 * is this part an attachment
	 * @param \stdClass $structure
	 */
	protected function _is_attachment( $structure ){
		if( (int)$structure->{self::KEY_IFDISPOSITION} && $structure->{self::KEY_DISPOSITION} == self::DISPOSITION_ATTACHMENT ){
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * get the mime type of this part
	 * @param \stdClass $structure
	 */
	protected function _get_mime_type($structure){
		$mime = self::$PRIMARY_MIME_TYPES[(int)$structure->{self::KEY_TYPE}];
	
		if ((int)$structure->{self::KEY_IFSUBTYPE}) {
			$mime .= ":".$structure->{self::KEY_SUBTYPE};
		}
		return $mime;
	}
	
	/**
	 * 
	 * Decode a part based on it's encoding
	 * @param int $encoding
	 * @param string $text
	 * @return string string
	 */
	public function decode( $encoding, $text ){
		
		switch ($encoding) {
				# 7BIT
			case self::ENC_7BIT:
			return $text; 
				# 8BIT
			case self::ENC_8BIT:
			return quoted_printable_decode(imap_8bit($text));
				# BINARY
			case self::ENC_BINARY:
			return imap_binary($text);
				# BASE64
			case self::ENC_BASE64:
			return imap_base64($text);
				# QUOTED-PRINTABLE
			case self::ENC_QUOTEDPRINTABLE:
			return quoted_printable_decode($text);
				# OTHER
			case self::ENC_OTHER:
			return $text;
				# UNKNOWN
			default:
				return $text;
		}
		
	}
	
	/*----------------  Impliment Countable ------------------*/
	/**
	 * (non-PHPdoc)
	 * @see Countable::count()
	 */
	public function count(){
		return count( $this->_message_sequence );
	}
	
	/**
	 * alias of count
	 * @see Countable::count()
	 */
	public function rowCount() {
		return count( $this );
	}
	
}