# Changelog

3-18-07 - initial writing of the class

7-28-07 - cleaned it up and added it to the standard lib, this is now a core class

8- 15-20 -07 - made this class into a pop3 fetching class also, now it can fetch email
  from a pop3 email box, organize attachments and place them in a directory.

8-20-07 - added the delete and close functions, delete gets rid of attachments also 

3-10-08 - added the UTF-8 encoding to sent messages, hopefully it works

4-1-08 - cleaned up the code, made it easier to pick up and use by getting rid of the
  internal stuff, it now returns pretty much everything. Made parse generic and static.
  Added the email_map class to assist with getting the parsed data

9-1-08 - added cleanUp function, also fixed a small parse bug where if the attachment
  filename was too long it would put the name on another line and the parser wouldn not catch
  that line, it does now 

5-20-09 - added support for retrieving arbitrary headers in anticipation of a twitter bot
  also added phpdoc comments

5-21-09 - fixed a major bug with parse() when $message_lines was a string, the email lost all
  the linebreaks, so the content body was line break less. Fixed by changing the preg split regex from
  '/\r?\n|\r/u' to  '/^/mu' so that it kept the line breaks at the end of every line

6-5-09 - sadly, the email parser never seemed to work right, so the parse function was turned into a shell
  and the parse_email class was born to make parsing emails more stable and compatible. We kept running into
  emails that the old parser could not handle, the new parser has handled everything I have thrown at it and the code
  is easier to follow to boot (though there is a lot more of it)

9-7-09 - fixed a bug where parseBody() was catching a ; at the end of a charset (eg, ISO-8859-5; instead of ISO-8859-5) and
  that was causing a mb_convert_encoding() notice to pop up, hopefully that will not happen anymore.

11-21-09 - fixed a __destruct bug where it was trying to close when it had never opened, causing
  a warning to be thrown

2-21-13 - Moved this from my standard library into its own lib so I could push it to Github, cleaned
up a lot of the code to bring it kind of up to date
