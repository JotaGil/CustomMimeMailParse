CustomMimeMailParse
===================

This is a customized Mime Mail parser Class using PHP's MailParse Extension.
It is based on the original project php-mime-mail-parser, hosted on:
http://code.google.com/p/php-mime-mail-parser/

I've introduced some changes that solve some issues that I've found using it.
Also, I've created and modified some functions that are useful to me.
Removed funtions are why I haven't found necessary due changes made (f.e. inLine functions).
 
Avoiding any incompatibility, this is published under same licenses as the original project is.


******************************************************************************

Really special thanks to the original author and people who has maintained and developed the php-mime-mail-parser project. I've found it very useful, especially without an extensive documentation of PHP's MailParse Extension.

******************************************************************************


EXAMPLES OF USE:

I think they are self-explanatory.

If you want to manually handle attachments:

    $to = FALSE;
    $from = FALSE;
    $cc = FALSE;
    $subject = FALSE;
    $text = FALSE;
    $html = FALSE;
    $attachmentsFileName = FALSE;  
    $attachments = FALSE;
    $imagesFileName = FALSE;
    $images = FALSE;
    $save_dir = '';/*
    
    $path="/path/to/my/mail/mail.local";    

    echo    "Parsed MIME mail message:\n";
    echo    "FILE:\n    ".$path."\n";
    
    try{
        $Parser = new CustomMimeMailParser(false);
        $Parser->setPath($path);

        $to = $Parser->getHeader('to');
        $from = $Parser->getHeader('from');
        $cc = $Parser->getHeader('cc');
        $replyTo = $Parser->getHeader('reply-to');
        
        $subject = $Parser->getHeader('subject');
        
        $text = $Parser->getMessageBody('text');
        $html = $Parser->getMessageBody('html');
        
        $attachmentsFileName = $Parser->getAttachmentsFileName();    
        $attachments = $Parser->getAttachments();
        
        $imagesFileName = $Parser->getImagesFileName();
        $images = $Parser->getImages();

        if($from) echo    "From:\n    ".$from."\n";
        if($to) echo    "To:\n    ".$to."\n";
        if($cc) echo    "Cc:\n    ".$cc."\n";
        if($replyTo)  echo    "Reply-to:\n    ".$replyTo."\n";
            
        if($subject)  echo    "Subject:\n    ".$subject."\n";
            
        if($text) echo    "Message text content:\n".$text."\n";
        if($html) echo    "Message html content:\n".$html."\n"; 
        
        // Handling attachments manually
        foreach ( $attachments as $attachment ) {        
            $file_name = $attachment->getFileName();
            $extension = $attachment->getFileExtension();
            echo    "Attachment FileName:\n    ".$file_name."\n"; 
            echo    "Attachment Extension:\n    ".$extension."\n";
        }
        // End Handling attachments manually
        
        foreach ( $imagesFileName as $file_name ) {
            echo    "Image FileName:\n ".$file_name."\n";    
        }

        // Save all files to disk
        $Parser->saveFilesToDisk($save_dir);
        
    }   //catch exception
    catch(Exception $ex){
      echo "Message:\n    " .$ex->getMessage()."\n";    
    }



If you don't want to manually handle attachments:

    $to = FALSE;
    $from = FALSE;
    $cc = FALSE;
    $subject = FALSE;
    $text = FALSE;
    $html = FALSE;
    $attachmentsFileName = FALSE;  
    $attachments = FALSE;
    $imagesFileName = FALSE;
    $images = FALSE;
    $save_dir = '';/*
    
    $path="/path/to/my/mail/mail.local";
    
    echo    "Parsed MIME mail message:\n";
    echo    "FILE:\n    ".$path."\n";
    
    try{
        $Parser = new CustomMimeMailParser(false);
        $Parser->setPath($path);

        $to = $Parser->getHeader('to');
        $from = $Parser->getHeader('from');
        $cc = $Parser->getHeader('cc');
        $replyTo = $Parser->getHeader('reply-to');
        
        $subject = $Parser->getHeader('subject');
        
        $text = $Parser->getMessageBody('text');
        $html = $Parser->getMessageBody('html');
        
        $attachmentsFileName = $Parser->getAttachmentsFileName();        
        $imagesFileName = $Parser->getImagesFileName();

        if($from) echo    "From:\n    ".$from."\n";
        if($to) echo    "To:\n    ".$to."\n";
        if($cc) echo    "Cc:\n    ".$cc."\n";
        if($replyTo)  echo    "Reply-to:\n    ".$replyTo."\n";
            
        if($subject)  echo    "Subject:\n    ".$subject."\n";
            
        if($text) echo    "Message text content:\n".$text."\n";
        if($html) echo    "Message html content:\n".$html."\n";
        
        foreach ( $attachmentsFileName as $file_name ) {
            echo    "Attachment FileName:\n ".$file_name."\n";
            
            // Get attachment plain text content
            if(  strrchr( $file_name, "." ) == ".txt" )
                echo    "Plain text attachment content:\n"
                        .($Parser->getPlainTextAttachmentContent( $file_name ))."\n";
            
            // Save files                        
            elseif(  strrchr( $file_name, "." ) == ".pdf" )
                $Parser->saveFilesToDisk($save_dir,$file_name);
        }
        
        foreach ( $imagesFileName as $file_name ) {
            echo    "Image FileName:\n ".$file_name."\n";    
        }
        
    }   //catch exception
    catch(Exception $ex){
      echo "Message:\n    " .$ex->getMessage()."\n";    
    }

