<?php
/**
 *  EmailParse class
 *
 *  this will parse emails into an EmailMap object
 *
 *  @link http://www.ehow.com/how_2041574_decode-email-mime-format.html
 *
 ******************************************************************************/
class EmailParse {

  /**
   *  all email bodies will be converted to this encoding, why? because it is easier that way
   */
  const UTF8_ENCODING = 'UTF-8';

  /**
   *  attachments will only be saved in folders with this prefix, in the attachment_path, this is to allow
   *  the cleanup to go through and kill attachments without deleting files it shouldn't
   */
  const ATTACHMENT_PREFIX = '_email-';

  /**
   *  hold the path that attachments will be saved to
   *  @var  string
   */
  protected $attachment_path = '';

  /**
   *  if you only want to save certain attachments, use this extension filter
   *
   *  a string of accepted extensions for the attachments
   *  if set, only attachments with the given ext will be allowed,
   *  more than one, use an OR (eg, txt|jpg)
   *  @var  string
   */
  protected $ext_filter = '';

  /**#@+
   *  used internally for the class to keep track where in the email it is
   */
  /**
   *  all the lines of the raw email, one line per index of the array
   */
  protected $msg_lines = array();
  /**
   *  the current line number the email parser is on
   */
  protected $line_index = 0;
  /**
   *  the current line the email parser is on
   */
  protected $current_line = '';
  /**
   *  holds total line count of the email being parsed
   */
  protected $total_lines = 0;
  /**
   *  all the found boudaries of the email
   */
  protected $boundary_list = array();
  /**
   *  holds the parsed email that will be returned from {@link parse()}
   *  
   *  @var  EmailMap
   */
  protected $email_map = null;
  /**#@-*/

  /**
   *  override default constructor
   *
   *  @param  string  $attachment_path  the path that will be used to save attachment files
   *  @param  string  $ext_filter a regex OR string of accepted extensions (eg, "txt" or "txt|jpg|jpeg"), if this
   *                              is null then no extensions will be saved
   */
  function __construct($attachment_path = '',$ext_filter = ''){

    $this->attachment_path = $attachment_path;
    $this->ext_filter = $ext_filter;

  }//method

  /**
   *  parse a given raw email into a well structured email_map object
   *
   *  so it is easy to get things from the email like to, from, subject, body, attachments, etc.
   *
   *  @param  string|array  $msg_lines  the raw email either in string, or array of lines format
   *  @return EmailMap  an email_map object of the given raw email
   */
  function parse($msg_lines){

    if(empty($msg_lines)){
      return null;
    }else{
      if(!is_array($msg_lines)){
        // NOTE 6-8-09: I have had to disable this regex to split the string and keep the newlines
        //  because it is not working consistently across all the installations of php this script runs
        //  on, sometimes it splits right, other times it will add a newline onto the next line instead of
        //  just have the newline in a line by itself (eg, text\n\nmore text becomes [0]text\n, [1]\nmore text instead of
        //  [0]text\n, [1]\n, [2]more text like it should)
        ///$msg_lines = preg_split('/^/mu',trim($msg_lines),-1,PREG_SPLIT_NO_EMPTY);

        // split the message on newlines then go back and add a new line to the end of every string...
        $msg_lines = preg_split('/\r\n|\n|\r/u',trim($msg_lines));
        foreach($msg_lines as $key => $line){
          $msg_lines[$key] .= "\r\n";
        }//foreach

      }//if
    }//if/else

    // get the class ready to parse the email...
    $this->msg_lines = $msg_lines;
    $this->current_line = '';
    $this->line_index = 0;
    $this->total_lines = count($msg_lines);
    $this->email_map = new EmailMap();
    $this->boundary_list = array();

    // actually parse the email...
    $this->email_map = $this->parseHeaders($this->email_map);
    $this->email_map = $this->parseBodies($this->email_map);

    return $this->email_map;

  }//method

  /**
   *  return true if we are still in a header
   *
   *  headers always end when a line consisting of just a newline is encountered
   *  @return boolean
   */
  protected function currentIsHeader(){
    $ret_bool = $this->incrementLine();
    if(preg_match('#^[\r\n]+$#u',$this->current_line)){
      $ret_bool = false;
    }//if
    return $ret_bool;
  }//method

  /**
   *  return true if we are still in a boundary
   *
   *  bodies end with either a boundary line, or there are no more lines
   *
   *  a boundary line is a line like this: --SOME_TEXT
   *
   *  @return boolean
   */
  protected function currentIsBody(){
    $ret_bool = $this->incrementLine();
    return $ret_bool ? !$this->isBoundary($this->current_line) : $ret_bool;
  }//method

  /**
   *  return true if the passed in line is a boundary
   *
   *  a boundary line is a line like this: --SOME_TEXT, or --SOME_TEXT--
   *
   *  @note this was the previous regex for ignoring ending boundaries: '#^\-{2}[^\-]\S*(?<!\-{2})$#u'
   *
   *  @param  string  $line the line to check if it is a boundary
   *  @param  boolean $is_begin_only  if true, then look only for --SOME_TEXT boundaries
   *  @return boolean
   */
  protected function isBoundary($line,$is_begin_only = false){
    $ret_bool = false;
    $line = trim($line);
    if(!empty($line) && !empty($this->boundary_list)){
      $regex = '#^\-{2}(?:'.join('|',$this->boundary_list).')';
      if(!$is_begin_only){
        // true on any boundary: --SOME_TEXT or --SOME_TEXT--
        $regex .= '(?:\-{2})?';
      }//if
      $regex .= '$#u';
      if(preg_match($regex,$line)){
        $ret_bool = true;
      }//if
    }//if
    return $ret_bool;
  }//method

  /**
   *  increment the internal class pointer of the current line
   *
   *  @return  boolean  if there are still more lines, retunr true, otherwise false
   */
  protected function incrementLine(){
    $ret_bool = false;
    if($this->line_index < $this->total_lines){
      $this->current_line = &$this->msg_lines[$this->line_index++]; // make it easier to access the current line
      $ret_bool = true;
    }else{
      $this->current_line = '';
    }//if/else
    return $ret_bool;
  }//method

  /**
   *  seek the internal class pointer to the next beginning boundary line
   *
   *  a beginning boundary line is defined as 2 dashes followed by chars that doesn't end with
   *  2 more dashes (eg, --THIS_IS_BEGINNING_BOUNDARY --THIS_IS_NOT--)
   *
   *  @return boolean
   */
  protected function seekToNextBoundary(){
    $ret_bool = false;
    do{
      if($this->isBoundary($this->current_line,true)){
        $ret_bool = true;
        break;
      }//if
    }while($this->incrementLine());
    return $ret_bool;
  }//method

  /**
   *  parse out the headers of an email
   *
   *  a header is defined as something with the form NON-WHITESPACE COLON TEXT
   *
   *  this parses every line from current until a line with just a newline is hit, it uses
   *  {@link currentIsHeader()} to decided when there are no more header lines
   *
   *  @param  object|array  $map  where the headers should be stored
   *  @return object|array  the same $map that was passed in, hopefully with more headers set
   */
  protected function parseHeaders($map){

    $matched = array();
    $current_header = '';
    $current_header_body = '';

    while($this->currentIsHeader()){

      if(preg_match('#^(\S+)\s*:(.*)$#siu',$this->current_line,$matched)){

        if(!empty($matched[1])){

          $map = $this->setHeader($current_header,$current_header_body,$map);

          // now begin the new current header...
          $current_header = $matched[1];
          $current_header_body = $matched[2];

        }//if

      }else{

        $current_header_body .= $this->current_line;

      }//if/else

    }//while

    // we would be on the headers boundary, so increment past it...
    $this->incrementLine();

    $map = $this->setHeader($current_header,$current_header_body,$map);

    ///out::e($map);
    return $map;

  }//method

  /**
   *  parse out the bodies of an email
   *
   *  a body is either from the end of headers to the EOF, or between boundary lines
   *
   *  a body is either an attachment, or text, or html, etc.
   *
   *  this parses every line from current until the EOF is reached, it uses
   *  {@link seekToNextBoundary()} to decided when there are no more bodies
   *
   *  @param  object  $map  email_map object where the bodies should be stored
   *  @return object  the same $map that was passed in, hopefully with body/bodies set
   */
  protected function parseBodies($map){

    $body_map_list = array();

    do{

      $body_map = array();

      // decide if this is a basic or multipart email body...
      if($map->isMultipart()){

        $this->seekToNextBoundary();
        $body_map = $this->parseHeaders($body_map);

      }else{

        // some of the main headers will belong to a simple plain text email...
        $keys = array('content-type','content-transfer-encoding','content-disposition');
        foreach($keys as $key){
          if(isset($map[$key])){ $body_map[$key] = $map[$key]; }//if
        }//foreach

      }//if/else

      $body_map = $this->parseBody($body_map);
      $body_map_list[] = $body_map;
      ///out::e($body_map);

    }while($this->seekToNextBoundary());

    $map[EmailMap::BODY] = $body_map_list;

    return $map;

  }//method

  /**
   *  grab a body that is between the boundaries
   *
   *  this will either grab the body, or save the attachment to the attachment directory
   *
   *  this parses every line from current until the body end is reached, it uses
   *  {@link currentIsBody()} to decided when the body is done
   *
   *  @note this function uses {@link $ext_filter} and {@link $attachment_path}
   *  @note everything is converted to UTF-8 encoding
   *
   *  @param  object|array  $map  where the body should be stored
   *  @return object|array  the same $map that was passed in, hopefully with body set
   */
  protected function parseBody($map){

    // sanity...
    if($this->isBoundary($this->current_line)){ return $map; }//if

    $body = ''; // either a string if inline or file pointer if attachment

    // find out if base 64 encoded....
    $is_base_64 = false;
    if(!empty($map['content-transfer-encoding'])){
      if(mb_stripos($map['content-transfer-encoding'],'base64') !== false){
        $is_base_64 = true;
      }//if
    }//if

    $is_attachment = false;
    $attachment_filename = '';
    if(!empty($map['content-disposition'])){

      if(mb_stripos($map['content-disposition'],'attachment') !== false){

        $is_attachment = true;
        $matched = array();
        if(preg_match('#filename\s*=(\S+)#iu',$map['content-disposition'],$matched)){
          $attachment_filename = trim($matched[1],'"');

          // make sure we can save this attachment...
          // if you pass in null for the ext filter, then all attachments are ignored...
          if($this->ext_filter !== null){

            if(empty($this->ext_filter) || preg_match('/(?:'.$this->ext_filter.')$/u',$attachment_filename)){

              $this->attachment_path = $this->assurePath($this->attachment_path);

              // create a file pointer for the attachment file to be written too...
              $file_path = $this->attachment_path.self::ATTACHMENT_PREFIX.md5(microtime(true).rand(0,5000)).DIRECTORY_SEPARATOR;
              $file_path = $this->assurePath($file_path);

              if($body = fopen($file_path.$attachment_filename,'wb')){
                $map[EmailMap::ATTACHMENT_PATH] = $file_path.$attachment_filename;
              }//if

            }//if

          }//if

        }//if

      }//if

    }//if

    do{

      if($is_attachment){

        // if the current boundary is an attachment, then write it out to the file line by line...

        if($body){

          $line = trim($this->current_line);

          if(!empty($line)){

            if($is_base_64){
              $line = base64_decode($line);
            }//if

            fwrite($body,$line);

          }//if

        }//if

      }else{

        // so some mails can be base64 encoded even if they are plain text, which is weird...

        $body .= ($is_base_64) ? trim($this->current_line) : $this->current_line;

      }//if/else

    }while($this->currentIsBody());

    if($is_attachment){

      // close the file pointer if it's open...
      if($body){ fclose($body); }//if

    }else{

      // if we have a body, convert it to utf-8
      if(!empty($body)){

        $charset = '';
        if(!empty($map['content-type'])){
          $matched = array();
          if(preg_match('#charset\s*=\s*(\S+)#iu',$map['content-type'],$matched)){
            $charset = trim($matched[1],';"\'');
          }//if
        }//if

        if(empty($charset)){
          $body = mb_convert_encoding($body,self::UTF8_ENCODING);
        }else{
          if(mb_stripos($charset,self::UTF8_ENCODING) === false){

            if(in_array($charset,mb_list_encodings())){

              // update 6-20-09: if we keep getting an invalid encoding email, we could use mb_list_encodings()
              // and see if the encoding found in the email is valid, if it isn't then just ignore
              // it, I don't want to do this until I know it is a legitimate problem and not a one off.
              // update 1-25-12 - I've been getting a lot of emails with "windows-1250" encoding that
              // throw a warning, so I am finally fixing this

              $body = mb_convert_encoding($body,self::UTF8_ENCODING,$charset);

            }else{

              $body = mb_convert_encoding($body,self::UTF8_ENCODING);

            }//if/else

          }//if
        }//if/else

        if($is_base_64){
          $body = base64_decode($body);
        }//if

        $map[EmailMap::BODY] = $body;

      }//if

    }//if/else

    ///out::e($map);
    return $map;

  }//method

  /**
   *  set the given header with the given body
   *
   *  {@link parseHeaders()} needs to save the current headers in more than one place, so this function
   *  exists to make it easy to arbitrarily set a header into the class entry map
   *
   *  @param  string  $header the header name
   *  @param  string  $body the header body
   *  @param  object|array  $map  the map to set the header into, if object, should extend arrayAccess
   */
  protected function setHeader($header,$body,$map){

    $body = trim($body);

    if(empty($header)){

      if(!empty($body)){

        if(!isset($map[EmailMap::UNPARSED_HEADER])){
          $map[EmailMap::UNPARSED_HEADER] = array();
        }//if

        $unparsed_headers = $map[EmailMap::UNPARSED_HEADER];
        $unparsed_headers[] = $body;
        $map[EmailMap::UNPARSED_HEADER] = $unparsed_headers;

      }//if

    }else{

      $header = mb_strtolower($header);

      // save the current header info...
      $map[$header] = $body;

      // if header is a content-type, check for multipart...
      if($header == 'content-type'){

        if(!empty($body)){
          if(mb_stripos($body,'multipart') !== false){
            // save the boundary...
            $matched = array();
            if(preg_match('#boundary\s*=(.+)#iu',$body,$matched)){
              $boundary = trim($matched[1],'"');
              if(!isset($this->boundary_list[$boundary])){
                $this->boundary_list[$boundary] = $boundary;
              }//if
            }//if
          }//if
        }//if

      }//if

    }//if/else

    return $map;

  }//method

  /**
   *  set the attachment path
   *
   *  attachments found in the emails will be saved to the directory set with this function
   *  set the attachment path, where the attachments will be downloaded to.
   *
   *  @see  $attachment_path
   *
   *  @param  string  $attachment_path  the path where attachments will be saved
   *  @return string  the current set attachment path
   */
  protected function assurePath($attachment_path = null){
    // set the attachment path, where the attachments will be downloaded to...
    if(empty($attachment_path)){
      $attachment_path = dirname(__FILE__).DIRECTORY_SEPARATOR.'attachments'.DIRECTORY_SEPARATOR;
    }//if
    if(!is_dir($attachment_path)){
      if(!mkdir($attachment_path,0755,true)){ $attachment_path = ''; }//if
    }//if

    if(!empty($attachment_path)){
      if(mb_substr($attachment_path,-1) != DIRECTORY_SEPARATOR){
        $attachment_path .= DIRECTORY_SEPARATOR;
      }//if
    }//if

    return $attachment_path;
  }//method

  /**
   *  clean up the attachment directory
   *
   *  after a while the cache directory could get littered with lost folders and
   *  files, there is no reason to keep those, so every now and again, cleanup
   */
  function cleanUp(){
    $this->attachment_path = $this->assurePath($this->attachment_path);
    $current_time = time();
    if($dir_list = glob($this->attachment_path.self::ATTACHMENT_PREFIX.'*',GLOB_ONLYDIR)){
      foreach($dir_list as $dir){
        if($file_list = glob($dir.DIRECTORY_SEPARATOR.'*')){
          foreach($file_list as $file){
            $create_time = filemtime($file);
            $age = $current_time - $create_time;
            if($age > 432000){ unlink($file); }//if
          }//foreach
        }//if
        // try and get rid of the directory since it should be empty...
        // check if dir empty is from here: http://iarematt.com/php-code-to-check-if-a-directory-is-empty/
        // Scans the path for directories and if there are less than 3
        // directories i.e. "." and ".." then the directory is empty and can
        // be deleted...
        if(($files = scandir($dir)) && (count($files) < 3)){ rmdir($dir); }//if
      }//foreach
    }//if
  }//method

  /**
   *  override default destructor
   *
   *  cleanup like cleaning up the attachment directory
   */
  function __destruct(){
    // only cleanup if we hit the magic number...
    $lucky = 8;
    if(rand(0,50) == $lucky){ $this->cleanUp(); }//if
  }//destructor

}//class

