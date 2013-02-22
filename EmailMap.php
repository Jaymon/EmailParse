<?php
/**
 *  this is the support email class, its main purpose is to allow easy access to
 *  the elements in the email
 ******************************************************************************/
class EmailMap implements ArrayAccess {

  /**#@+
   *  parsed out of the email but don't map to a specific header
   */
  const BODY = 'body';
  const TIMESTAMP = 'timestamp';
  /**#@-*/

  /**
   *  holds the path where the attachment can be found
   */
  const ATTACHMENT_PATH = 'attachment_path';

  /**
   *  used to hold extraneous headers that could not be understood
   */
  const UNPARSED_HEADER = 'email_map_unparsed_headers';

  /**#@+
   *  some handy content types that are common.
   */
  const TYPE_TEXT = 'text/plain';
  const TYPE_HTML = 'text/html';
  /**#@-*/

  /**#@+
   *  different types of content that the email body can be composed of
   */
  /**
   *  not all emails put in the inline content-disposition, so if it doesn't have a disposition
   *  you can just assume it is inline
   */
  const DISPOSITION_INLINE = 'inline';
  /**
   *  All the emails I have looked at always put in the attachment disposition, with the filename
   */
  const DISPOSITION_ATTACHMENT = 'attachment';
  /**#@-*/

  /**
   *  when values are set into the class, they are set into this array, basically it allows
   *  arrayAccess, iteratorAggregate, etc. to work as expected   
   */     
  protected $map = array();

  public function getSubject(){ return $this->offsetGet('subject'); }//method
  public function setSubject($subject){ return $this->offsetSet('subject', $subject); }//method
  public function hasSubject($subject){ return $this->offsetExists('subject'); }//method

  /**
   *  return boolean whether content type is considered multipart or not
   *
   *  @return boolean
   */
  function isMultipart(){

    $ret_bool = false;

    // get content type...
    $content_type = $this->offsetGet('content-type','');
    if(!empty($content_type)){
      if(mb_stripos($content_type,'multipart') !== false){ $ret_bool = true; }//if
    }//if

    return $ret_bool;

  }//method

  /**
   *  check indexes for the specified header
   *
   *  @param  string|array  the header name to check, can either be a str (eg, 'X-header')
   *                        or list (eg, array('X-header-1','X-header-2')) in which case the
   *                        first match will be returned
   *  @param  string  null if the header was not found, otherwise the header
   */
  function getHeader($header_name){

    // sanity...
    if(empty($header_name)){ return null; }//if
    if(!is_array($header_name)){ $header_name = array($header_name); }//if

    $ret_str = null;

    foreach($header_name as $name){
      $name = mb_strtolower($name);
      if($this->offsetExists($name)){
        $ret_str = $this->offsetGet($name);
        break;
      }//if
    }//foreach

    return $ret_str;

  }//method

  /**
   *  returns the plain text body of the email, a stripped html version if the plain text isn't available...
   *
   *  @return string
   */
  function getPlainBody(){

    $ret_str = '';

    $body_list = $this->offsetGet(self::BODY);
    if(is_array($body_list)){
      foreach($body_list as $body_map){
        if(!empty($body_map[self::BODY])){
          if(isset($body_map['content-type'])){
            if(mb_stripos($body_map['content-type'],self::TYPE_TEXT) !== false){
              $ret_str = $body_map[self::BODY];
              break;

            }else if(mb_stripos($body_map['content-type'],self::TYPE_HTML) !== false){
              # use this as a backup, but keep looking for true plain text
              $ret_str = strip_tags($body_map[self::BODY]);

            }//if/else
          }//if/else
        }//if
      }//foreach
    }//if

    return $ret_str;

  }//method

  /**
   *  check to, cc, and bcc looking for emails that match $host
   *
   *  this is more encompassing than to() because it will check bcc, etc. looking
   *  for recipient emails. Also, this let's you filter based on host so you can just
   *  get recipient emails that you care about
   *
   *  @param  string  $host something like 'hostname.com' so you can filter on a host, must include
   *                        the .com, .edu or whatever.
   *  @return array list of found recipient emails
   */
  function getRecipient($host = ''){

    $ret_list = array();

    $offset_list = array('to','cc','bcc');
    foreach($offset_list as $offset){

      if($this->offsetExists($offset)){

        $list = $this->getEmail($this->offsetGet($offset),$host);
        if(!empty($list)){
          $ret_list = array_merge($ret_list,$list);
        }//if

      }//if

    }//foreach

    // let's return only unique emails...
    if(!empty($ret_list)){
      $ret_list = array_unique($ret_list);
    }//if

    return $ret_list;

  }//method

  /**
   *  get who the email was sent to
   *
   *  @return list  the email address(es) of the to recipient(s)
   */
  function GetTo(){
    $ret_list = array();
    $to_email = $this->offsetGet('to');
    if(!empty($to_email)){
      $ret_list = $this->getEmail($to_email);
    }//if
    return $ret_list;
  }//method

  /**
   *  try and get a from email by any means necessary.
   *
   *  this will go through all the possible from values to find the best possible from
   *  email address
   *
   *  @return string
   */
  function getFrom(){
    $ret_str = '';
    $from_list = array('sender','return-path','reply-to','from');
    foreach($from_list as $name){
      if($this->offsetExists($name)){
        $list = $this->getEmail($this->offsetGet($name));
        if(!empty($list)){
          $ret_str = $list[0];
          break;
        }//if
      }//if
    }//foreach
    return $ret_str;
  }//method

  /**
   *  parse out the email address from an email header body
   *
   *  because some of the headers with email addresses can be in a ton of different forms
   *  we need a way to get just the email. Also, there can be more than one email address in the body,
   *  so this returns a list of all the found email addresses
   *
   *  the email can be in the form:
   *    - "to name" <to@email.com>
   *    - to@email.com
   *    - to name <to@email.com>
   *
   *  @param  string  $str  the header line containing an email address
   *  @param  string  $host something like 'hostname.com' so only emails with that host name will be returned
   *  @return array a list of the found email addresses
   */
  protected function getEmail($str,$host = ''){

    // santiy....
    if(empty($str)){ return array(); }//if

    $ret_list = array();
    $matched = array();
    $regex = '[^<>,\s\"]+@';
    if(empty($host)){
      $regex .= '[^<>,\s\"]+';
    }else{
      $regex .= preg_quote($host);
    }//if/else

    if(preg_match_all('/'.$regex.'/u',$str,$matched)){

      if(!empty($matched[0])){
        $ret_list = $matched[0];
      }//if

    }//if

    return $ret_list;

  }//method

  /**
   *  return the attachments, if ext_filter is present, only return attachments with the given extension.
   *
   *  @param  string  $ext_filter specify the extension of the attachments to get, to do more than one use | (eg, txt|html)
   *                              the extension type you want returned (eg, txt, zip, jpeg), more than one ext?
   *                              use an OR (eg, jpg|jpeg)
   *  @return array a list of the attachments that match
   */
  public function getAttachments($ext_filter = ''){

    $ret_list = array();

    $body_list = $this->offsetGet(self::BODY);
    if(is_array($body_list)){
      foreach($body_list as $body_map){
        if(!empty($body_map['content-disposition'])){
          if(mb_stripos($body_map['content-disposition'],self::DISPOSITION_ATTACHMENT) !== false){
            if(!empty($body_map[self::ATTACHMENT_PATH])){
              if(empty($ext_filter)){
                $ret_list[] = $body_map;
              }else{
                if(preg_match('/(?:'.$ext_filter.')$/ui',$body_map[self::ATTACHMENT_PATH])){
                  $ret_list[] = $content_map;
                }//if
              }//if/else
            }//if
          }//if
        }//if
      }//foreach
    }//if

    return $ret_list;

  }//method

  /**
   *  output all the headers in text
   *
   *  @param  array $exclude_header_list  a list of headers that should not be output
   *  @return string  all the headers in correct email header format
   */
  public function outHeaders($exclude_header_list = array()){

    $ret_str = '';
    $info_map = $this->map;

    // get rid of the info we don't want to add as a header...
    unset($info_map[self::BODY]);
    unset($info_map[self::UNPARSED_HEADER]);
    unset($info_map[self::TIMESTAMP]);

    foreach($exclude_header_list as $exclude_header){

      if(isset($info_map[$exclude_header])){
        unset($info_map[$exclude_header]);
      }//if

    }//foreach

    // now go through and concat all the headers...
    foreach($info_map as $key => $val){
      $ret_str .= ucfirst($key).': '.$val."\n";
    }//method

    return $ret_str;

  }//method

  /**
   * Set a value given it's key e.g. $A['title'] = 'foo';
   */
  public function offsetSet($key,$val){
    if(array_key_exists($key, get_object_vars($this))){
      $this->{$key} = $val;
    }else{
      $this->map[$key] = $val;
    }//if/else
  }//method
  
  /**
   * Return a value given it's key e.g. echo $A['title'];
   */
  public function offsetGet($key){
    if(isset($this->map[$key])){
      return $this->map[$key];
    }else if(array_key_exists($key, get_object_vars($this))){
      return $this->{$key};
    }//if/else if
  }//method

  /**
   * Unset a value by it's key e.g. unset($A['title']);
   */
  public function offsetUnset($key){
    if(isset($this->map[$key])){
      unset($this->map[$key]);
    }else if(array_key_exists($key, get_object_vars($this))){
      unset($this->{$key});
    }//if/else if
  }//method
  
  /*
   * Check value exists, given it's key e.g. isset($A['title'])
   */
  public function offsetExists($key){
    $ret_bool = false;
    if(isset($this->map[$key])){
      $ret_bool = true;
    }else if(array_key_exists($key, get_object_vars($this))){
      $ret_bool = true;
    }//if/else if
    return $ret_bool;
  }//method

}//class

