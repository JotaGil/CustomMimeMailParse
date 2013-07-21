<?php
/**
 * This is a customized Mime Mail parser Class using PHP's MailParse Extension.
 * It is based on the original project php-mime-mail-parser, hosted on:
 * http://code.google.com/p/php-mime-mail-parser/
 * 
 * I've introduced changes that solves some issues I've found using it.
 * Also, I've created and modified some functions that seems useful.
 * Removed funtions are why I do not consider necessary due changes made
 * (f.e. inLine functions).
 * 
 * Avoiding any incompatibility with the original project, 
 * this is published under same licenses as the original project is.
 * 
 * Really special thanks to the original author and people who has maintained,
 * developed, and any case contributed to php-mime-mail-parser project. 
 * I've found it really useful, especially without an extensive documentation 
 * from PHP's MailParse Extension.
 * 
 * @original_author gabe@fijiwebdesign.com
 * @original_url http://www.fijiwebdesign.com/
 * @original_license http://creativecommons.org/licenses/by-sa/3.0/us/
 * @based_version $Id$ (Revision: r27, Jun 19 , 2013)
 * 
 * @author  Jose Manuel Gil Carrillo
 * @mail    jose.gilcarrillo@gmail.com
 * @license http://creativecommons.org/licenses/by-sa/3.0/us/
 * @version 1.0 (Jul 21 , 2013)
 */

require_once('CustomMimeMailParserAttachment.class.php');

// CustomMimeMailParser Class

class CustomMimeMailParser {

    /**
     * @var $resource PHP MimeParser Resource ID
     */
    private $resource;

    /**
     * @var $stream A file pointer to email
     */
    private $stream;

    /**
     * @var $data A text of an email
     */
    private $data;
    
    /**
     * @var $parts Mime Parts
     */
    private $parts;
    
    /**
     * @var $attachments Stream Resources for Attachments
     */
    private $attachments;
    
    /**
     * @var $images Stream Resources for Images
     */
    private $images;
    
    /**
     * @var $parse_files Boolean to know if parse files directly
     */
    private $parse_files;
    

    /**
     * Initialize some stuff
     * @public
     * @return void
     */
    public function __construct( $parse_attachmens = TRUE ) {
        $this->parts = array();
        $this->attachments = array();
        $this->images = array();
        $this->parse_attachment = $parse_attachmens;
    }

    /**
     * Free the held resources
     * @public
     * @return void
     */
    public function __destruct() {
        // clear email file resource
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        // clear MailParse resource
        if (is_resource($this->resource)) {
            mailparse_msg_free($this->resource);
        }
    }
    
// Input methods
    /*
     * Input method 1 (preferred)
     * Specify a file path to the mime mail
     */
    /**
     * Set file path we use to get the email text
     * @public
     * @param $mail_path (Path to a mail file)
     * @return Object (CustomMimeMailParser Instance)
     */
    public function setPath( $path ) {
        // should parse message incrementally from file
        $this->resource = mailparse_msg_parse_file($path);
        $this->stream = fopen($path, 'r');
        $this->parse();
        return $this;
    }

    /*
     * Input method 2
     * Specify a php file resource (stream) to MIME mail
     */
    /**
     * Set the Stream resource we use to get the email text
     * @public
     * @return Object (CustomMimeMailParser Instance)
     * @param $stream (Resource)
     */
    public function setStream( $stream ) {
        // streams have to be cached to file first
        if (get_resource_type($stream) == 'stream') {
            $tmp_fp = tmpfile();
            if ($tmp_fp) {
                while(!feof($stream)) {
                    fwrite($tmp_fp, fread($stream, 2028));
                }
                fseek($tmp_fp, 0);
                $this->stream =& $tmp_fp;
            } else {
                throw new 
                    Exception('Could not create temporary files for attachments. '
                             .'Your tmp directory may be unwritable by PHP.');
                return false;
            }
            fclose($stream);
        } else {
            $this->stream = $stream;
        }

        $this->resource = mailparse_msg_create();
        // parses message incrementally. Low memory usage, but slower.
        while(!feof($this->stream)) {
            mailparse_msg_parse($this->resource, fread($this->stream, 2082));
        }
        $this->parse();
        return $this;
    }

    /*
     * Input method 3
     * Specify raw MIME mail text
     */
    /**
     * Set email text
     * @public
     * @return Object (CustomMimeMailParser Instance)
     * @param $data String
     */
    public function setText( $data ) {
        $this->resource = mailparse_msg_create();
        // does not parse incrementally, fast memory hog might explode
        mailparse_msg_parse($this->resource, $data);
        $this->data = $data;
        $this->parse();
        return $this;
    }
// END Input methods

// Parse mail    
    /**
     * Parse Message into parts
     * @private
     * @return void 
     */
    private function parse() {
        $structure = mailparse_msg_get_structure($this->resource);
        foreach($structure as $part_id) {
            $part = mailparse_msg_get_part($this->resource, $part_id);
            $this->parts[$part_id] = mailparse_msg_get_part_data($part);           
            // Process files
            if ($this->parse_files){
                $attachment = $this->getAttachment($this->parts[$part_id]);
                if ($attachment)
                    $this->attachments[] =  $attachment;
                $image = $this->getImage($this->parts[$part_id]);
                if ($image)
                    $this->images[] =  $image;
            }
            // END process files
        }
    }
// END Parse mail
    
// MIME message part process
    /**
     * Returns Headers from a MIME part
     * @private
     * @return Array (Mime part headers)
     * @param $part Array (Mime Part)
     */
    private function getPartHeaders( $part ) {
        if (isset($part['headers'])) {
            return $part['headers'];
        }
        return false;
    }

    /**
     * Returns a specific Header from a MIME part
     * @private
     * @return Array
     * @param $part Array (MIME part)
     * @param $header String (Header Name)
     */
    private function getPartHeader( $part, $header ) {
        if (isset($part['headers'][$header])) {
            return $part['headers'][$header];
        }
        return false;
    }

    /**
     * Returns ContentType from a MIME part
     * @private
     * @return String
     * @param $part Array (Mime Part)
     */

    private function getPartContentType( $part ) {
        if (isset($part['content-type'])) {
            return $part['content-type'];
        }
        return false;
    }

     /**
     * Returns ContentName from a MIME part
     * @private
     * @return String
     * @param $part Array (Mime Part)
     */
    private function getPartContentName( $part ) {
        if (isset($part['content-name'])) {
            return $part['content-name'];
        }
        return false;
    }

    /**
     * Returns Content Disposition
     * @private
     * @return String
     * @param $part Array (Mime Part)
     */
    private function getPartContentDisposition( $part ) {
        if (isset($part['content-disposition'])) {
            return $part['content-disposition'];
        }
        return false;
    }

    /**
     * Returns Content id
     * @private
     * @return String
     * @param $part Array (Mime Part)
     */
    private function getPartContentId( $part ) {
        if (isset($part['content-id'])) {
            return $part['content-id'];
        }
        return false;
    }

    /**
     * Retrieve raw Header from a MIME part
     * @private
     * @return String
     * @param $part Array (Mime Part)
     */
    private function getPartHeaderRaw( $part ) {
        $header = '';
        if ($this->stream) {
            $header = $this->getPartHeaderFromFile($part);
        } else if ($this->data) {
            $header = $this->getPartHeaderFromText($part);
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() '
                               .'must be called before retrieving email parts.');
        }
        return $header;
    }

    /**
     * Retrieve Body from a MIME part
     * @private
     * @return String
     * @param $part Array (Mime Part)
     */
    private function getPartBody( $part ) {
        $body = '';
        if ($this->stream) {
            $body = $this->getPartBodyFromFile($part);
        } else if ($this->data) {
            $body = $this->getPartBodyFromText($part);
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() '
                                .'must be called before retrieving email parts.');
        }
        return $body;
    }

    /**
     * Retrieve Header from a MIME part file
     * @private
     * @return String (Mime Header Part)
     * @param $part Array (Mime Part)
     */
    private function getPartHeaderFromFile( $part ) {
        $start = $part['starting-pos'];
        $end = $part['starting-pos-body'];
        fseek($this->stream, $start, SEEK_SET);
        $header = fread($this->stream, $end-$start);
        return $header;
    }
    
    /**
     * Retrieve Body from a MIME part file
     * @private
     * @return String (Mime Body Part)
     * @param $part Array (Mime Part)
     */
    private function getPartBodyFromFile( $part ) {
        $start = $part['starting-pos-body'];
        $end = $part['ending-pos-body'];
        fseek($this->stream, $start, SEEK_SET);
        $body = fread($this->stream, $end-$start);
        return $body;
    }

    /**
     * Retrieve Header from a MIME part text
     * @private
     * @return String Mime Header Part
     * @param $part Array (Mime Part)
     */
    private function getPartHeaderFromText( $part ) {
        $start = $part['starting-pos'];
        $end = $part['starting-pos-body'];
        $header = substr($this->data, $start, $end-$start);
        return $header;
    }
    
    /**
     * Retrieve Body from a MIME part text
     * @private
     * @return String (Mime Body Part)
     * @param $part Array (Mime Part)
     */
    private function getPartBodyFromText($part) {
        $start = $part['starting-pos-body'];
        $end = $part['ending-pos-body'];
        $body = substr($this->data, $start, $end-$start);
        return $body;
    }
// END MIME message part process
    
// Headers
    
    /**
     * Retrieve Email Headers
     * @public
     * @return Array
     */
    public function getHeaders() {
        if (isset($this->parts[1])) {
            return $this->getPartHeaders($this->parts[1]);
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() '
                               .'must be called before retrieving email headers.');
            return false;
        }
    }
    
    /**
     * Retrieve raw Email Headers
     * @public
     * @return string
     */
    public function getHeadersRaw() {
        if (isset($this->parts[1])) {
            return $this->getPartHeaderRaw($this->parts[1]);
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() '
                               .'must be called before retrieving email headers.');
        }
        return false;
    }

    /**
     * Retrieve a specific Email Header
     * @public
     * @return String
     * @param $name String (Header name)
     */
    public function getHeader( $name ) {
        if (isset($this->parts[1])) {
            $headers = $this->getPartHeaders($this->parts[1]);
            if (isset($headers[$name])) {
                return $headers[$name];
            }
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() '
                               .'must be called before retrieving email headers.');
        }
        return false;
    }
    
    /**
     * Get headers from message body part.
     * @public
     * @return Array
     * @param $type String(Optional) (Body message type)
     */
    public function getMessageBodyHeaders( $type = 'text' ) {
        $headers = false;
        $mime_types = array(
                'text'=> 'text/plain',
                'html'=> 'text/html'
        );
        if (in_array($type, array_keys($mime_types))) {
            foreach($this->parts as $part) {
                if ($this->getPartContentType($part) == $mime_types[$type]) {
                    $headers = $this->getPartHeaders($part);
                    break;
                }
            }
        } else {
            throw new Exception('Invalid type specified for MimeMailParser::getMessageBody. '
                               .'"type" can either be text or html.');
        }
        return $headers;
    }

// END Headers

// Decode
    
    /**
     * Decode a string depending on encoding type.
     * @private
     * @return String (Decoded string)
     * @param $encodedString    (String in it's original encoded state)
     * @param $encodingType     (Encoding type from the Content-Transfer-Encoding header's part)
     */
    private function decode( $encodedString, $encodingType ) {
        if (strtolower($encodingType) == 'base64') {
            return base64_decode($encodedString);
        } else if (strtolower($encodingType) == 'quoted-printable') {
            return quoted_printable_decode($encodedString);
        } else {
            return $encodedString;
        }
    }
    
// END Decode
    
// Message body

    /**
     * Returns email message body with specified format
     * @public
     * @return String (Mixed String Body or False if not found)
     * @param $type String(Optional) (Body message type)
     */
    public function getMessageBody( $type = 'text' ) {
        $body = false;
        $mime_types = array(
                'text'=> 'text/plain',
                'html'=> 'text/html'
        );
        if ( in_array( $type, array_keys($mime_types) ) ) {
            foreach($this->parts as $part) {
                if ($this->getPartContentType($part) == $mime_types[$type]) {
                    $headers = $this->getPartHeaders($part);
                    $body    = $this->decode(
                                        $this->getPartBody($part), 
                                        array_key_exists('content-transfer-encoding', $headers) 
                                        ? $headers['content-transfer-encoding'] : '');
                    break;
                }
            }
        } else {
            throw new Exception('Invalid type specified for MimeMailParser::getMessageBody. '
                               .'"type" can either be text or html.');
        }
        return $body;
    }

// END Message body

    
// File process - Attachments and Images

    /**
     * Reads attachment body and it saves on a temporary file resource.
     * @private
     * @return Resource (File pointer)
     * @param $part Array (Mime Part)
     */
    private function getAttachmentStream( array $part ) {
	$temp_fp = tmpfile();
	array_key_exists('content-transfer-encoding', $part['headers']) 
		? $encoding = $part['headers']['content-transfer-encoding'] 
		: $encoding = '';
	if ($temp_fp) {
	    if ($this->stream) {
		$start = $part['starting-pos-body'];
		$end = $part['ending-pos-body'];
		fseek($this->stream, $start, SEEK_SET);
		$len = $end-$start;
		$written = 0;
		/*
		 * Solution to ISSUE 7
		 * http://code.google.com/p/php-mime-mail-parser/issues/detail?id=7
		 * Proposed By: eckhard....@arcor.de
		 * Date: May 14, 2013
		 */
		$write = ( $len<2028 ) ? $len : 2028;
		$body = '';
		while($written < $len) {
		    if (($written+$write < $len ))
			$write = $len - $written;
		    $part = fread($this->stream, $write);
		    fwrite($temp_fp, $this->decode($part, $encoding));
		    $written += $write;
		}
	    } else if ($this->data) {
		$attachment = $this->decode($this->getPartBodyFromText($part), $encoding);
		fwrite($temp_fp, $attachment, strlen($attachment));
	    }
	    fseek($temp_fp, 0, SEEK_SET);
	} else {
	    throw new Exception('Could not create temporary files for attachments. '
                               .'Your tmp directory may be unwritable by PHP.');
	    return false;
	}
	return $temp_fp;
    }
    
    /*
     * I've notice that some mail clients do not include content 
     * disposition-filename attribute on sent mails. 
     * However, it is as a content-name attribute.
     * That's why I've included this function.
     * This solves some attachment issues posted in the original project page.
     */
    /**
     * Returns filename
     * @private
     * @return String
     * @param $part Array (Mime Part)
     */
    private function getName( array $part ){
	$name = NULL;
	if (isset($part['disposition-filename']) === TRUE) 
	    $name = $part['disposition-filename'];
	elseif (isset($part['content-name']) === TRUE) 
	    $name = $part['content-name'];
        else $name = md5(uniqid());
        
	return $name;
    }
    
    /**
     * Returns filename.
     * If file is set, check if both parameters are equal. 
     * This is useful when you know exactly which file do you need.
     * @private
     * @return String
     * @param $name String
     * @param $file String (optional)
     */
    private function matchFiles ( $name, $file = FALSE ){
	if ($file !== FALSE)
	    $name = ($name == $file ? $name : NULL);
	return $name;
    }
    
    /**
     * Returns attachment's/image's filenames
     * @private
     * @return Array
     * @param $files Array (CustomMimeMailParserAttachment instances Array)
     */
    private function getFileNames( $files ) {
        $file_names = array();
        foreach($files as $file)
            $file_names[] = $file->getFileName();
        return $file_names;
    }    
    
    /**
     * Returns file content (atachment/image)
     * @private
     * @return Object (CustomMimeMailParserAttachment instance)
     * @param $part Array (Mime Part)
     * @param $type String (file type)
     */
    private function getFile( $part , $type = 'attachment' ) {
	$file = FALSE;	    
        $file_name = $this->getName($part);
        
        if($type == 'attachment')   $is_type='isAttachment';
        else $is_type='isImage';
        
        if ( ($this->$is_type($part) !== FALSE)
             && ($file_name !== NULL)
        ) {
            $file = new CustomMimeMailParserAttachment(
                            $file_name,
                            $this->getPartContentType($part),
                            $this->getAttachmentStream($part),
                            $this->getPartContentDisposition($part),
                            $this->getPartHeaders($part)
                        );
        }
	return $file;
    }    
    
    /**
     * Returns if message part is an Attachment.
     * @private
     * @return Boolean
     * @param $part Array (Mime Part)
     */
    private function isAttachment( array $part ){
	$disposition = $this->getPartContentDisposition($part);
	$dispositions = array("attachment","inline");
	return in_array($disposition, $dispositions);
    }
      
    /**
     * Parse email's attachments
     * @private
     * @return void
     */
    private function parseAttachments() {
        $this->attachments = array();
        foreach($this->parts as $part) {
            $attachment = $this->getFile($part,'attachment');
            if ($attachment)
                $this->attachments[] = $attachment;
        }
    }
        
    /**
     * Returns attachments' content in order of appearance
     * @public
     * @return Array (CustomMimeMailParserAttachment instances Array)
     * @param $file String(optional)
     */
    public function getAttachments( $file = FALSE ) {
        $out = array();
        if(!$this->attachments)
            $this->parseAttachments();
        
        if ($file !== FALSE){
            foreach($this->attachments as $attachment)
                if($this->matchFiles($attachment->getFileName(),$file)){
                    $out[]=$attachment;
                    break;
                }
        } else
            return $this->attachments;
        return $out;
    }
    
    /**
     * Returns attachments' filename
     * @public
     * @return Array
     */
    public function getAttachmentsFileName() {
        if(!$this->attachments)
            $this->parseAttachments();        
        return $this->getFileNames( $this->attachments );
    }
    
    /**
     * Returns if message part is an Image
     * @private
     * @return Boolean
     * @param $part Array (Mime Part)
     */
    private function isImage( array $part ){
	$type = $this->getPartContentType($part);
	return stripos($type,'image');
    }    
    
    /**
     * Parse images
     * @private
     * @return void
     */
    private function parseImages() {
        $this->images = array();
        foreach($this->parts as $part) {
            $image = $this->getFile($part,'image');
            if ($image)
                $this->images[] = $image;
        }
    }           
    
    /**
     * Returns images in order of appearance
     * @public
     * @return Array (CustomMimeMailParserAttachment instances Array)
     * @param $file String (optional)
     */
    public function getImages( $file = FALSE ) {
        $out = array();
    	if(!$this->images)
            $this->parseImages();
        
        if ($file !== FALSE){
            foreach($this->images as $image)
                if($this->matchFiles($image->getFileName(),$file)){
                    $out[]=$image;
                    break;
                }                    
        } else
            return $this->images;
        return $out;
    }
    
    /**
     * Returns images' filename
     * @public
     * @return Array (CustomMimeMailParserAttachment instances Array)
     * @param $type Object[optional]
     */
    public function getImagesFileName () {
        if(!$this->images)
            $this->parseImages();
        return $this->getFileNames( $this->images );
    }
    
    /**
     * Save files to disk.
     * Path's write permission needed.
     * @public
     * @param $path String
     * @param $file_archive String (optional)
     */
    public function saveFilesToDisk( $path, $file_archive = FALSE ){
        if($file_archive !== FALSE)
            $files = array_merge(
                        $this->getAttachments($file_archive),
                        $this->getImages($file_archive)
                     );
        else
            $files = array_merge(
                        $this->attachments,
                        $this->images
                     );
        
	foreach($files as $file) {
	    // Get file name
	    $filename = $file->getFileName();
	    // Write file to a directory
	    if ($fp = fopen($path.$filename, 'w')) {
		while( $bytes = $file->read() )
		    fwrite($fp, $bytes);
		fclose($fp);
	    }else{
                throw new Exception('Could not save files. '
                               .'Directory "'.$path.'" may be unwritable by PHP.');
                return false;
            }
	}
    }

    /**
     * Reads attachment content if it is plain text.
     * This function is useful for me, not needed to save attachment.
     * @public
     * @return String
     */
    public function getPlainTextAttachmentContent( $file_name ){
        $text = "";
        foreach($this->attachments as $attachment){
            if( $attachment->getFileName() == $file_name){
                if ( in_array( $attachment->getContentType(), array('text/plain') ) ) {
                    while ( $textPart = $attachment->read() )
                        $text .= $textPart;
                    break;
                }
            }        
        }            
        return $text;
    }

// END File process - Attachments and Images   
}
// END CustomMimeMailParser Class