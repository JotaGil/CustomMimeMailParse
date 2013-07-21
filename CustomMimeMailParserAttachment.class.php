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

// CustomMimeMailParserAttachment

class CustomMimeMailParserAttachment {
    /**
     * @var $filename Filename
     */
    private  $filename;
    /**
     * @var $content_type Mime Type
     */
    private  $content_type;
    /**
     * @var $content File Content
     */
    private  $content;
    /**
     * @var $extension Filename extension
     */
    private $extension;
    /**
     * @var $content_disposition Content-Disposition
     */
    private $content_disposition;
    /**
     * @var $headers An Array of attachment's headers
     */
    private $headers;
    /**
     * @var $stream A file pointer 
     */    
    private $stream;

    /**
     * Initialize vars
     * @public
     * @return void
     */
    public function __construct( $filename, 
                                 $content_type, 
                                 $stream, 
                                 $content_disposition, 
                                 $headers = array()
                                ) {
        $this->filename = $filename;
        $this->content_type = $content_type;
        $this->stream = $stream;
        $this->content = null;
        $this->content_disposition = $content_disposition;
        $this->headers = $headers;
    }

    /**
     * Free the held resouces
     * @public
     * @return void
     */
    public function __destruct() {
        // clear the MailParse resource stream
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    /**
     * Retrieve attachment filename
     * @public
     * @return String
     */
    public function getFileName() {
        return $this->filename;
    }

    /**
     * Retrieve Attachment Content-Type
     * @public
     * @return String
     */
    public function getContentType() {
        return $this->content_type;
    }

    /**
     * Retrieve Attachment Content-Disposition
     * @public
     * @return String
     */
    public function getContentDisposition() {
        return $this->content_disposition;
    }

    /**
     * Retrieve Attachment Headers
     * @public
     * @return String
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Retrieve attachment's file extension
     * @public
     * @return String
     */
    public function getFileExtension() {
        if (!$this->extension) {
            $ext = substr(strrchr($this->filename, '.'), 1);
            switch ($ext) {
                case 'gz':
                    // special case, tar.gz
                    // todo: other special cases?
                    $this->extension =  preg_match("/\.tar\.gz$/i", $ext) ? 'tar.gz' : 'gz';
                break;
                default:
                    $this->extension = $ext;
                break;                    
            }
        }
        return $this->extension;
    }

    /**
     * Reads few bytes content each time until completed
     * Once read is completed, rewinds file pointer, and returns false.
     * @public
     * @return String
     * @param $bytes Int(optional) (Bytes to read)
     */
    public function read($bytes = 2082) {
        if(feof($this->stream)){
            rewind($this->stream);
            return FALSE;
        }
        else return fread($this->stream, $bytes);
    }

    /**
     * Retrieve file content in one time.
     * @public
     * @return String
     */
    public function getContent() {
        if ($this->content === null) {
            fseek($this->stream, 0);
            while(($buf = $this->read()) !== false) { 
                $this->content .= $buf; 
            }
        }
        return $this->content;
    }

    /**
     * Allow properties 
     * 	MimeMailParser_attachment::$name,
     * 	MimeMailParser_attachment::$extension 
     * to be retrieved as public properties
     * @public
     * @param $name String
     */
    public function __get( $name ) {
        if ($name == 'content') {
            return $this->getContent();
        } else if ($name == 'extension') {
            return $this->getFileExtension();
        }
        return null;
    }

}
// END CustomMimeMailParserAttachment