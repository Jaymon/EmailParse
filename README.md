# EmailParse

Easy parsing of emails in PHP

## Parse an email

    include_once('EmailMap.php');
    include_once('EmailParse.php');

    // $input is a raw email string
    // get $input from some other php library like a pop3 library or something

    $ep = new EmailParse();
    $email_map = $ep->parse($input);

    print_r($email_map->getTo()); // getTo() returns a list since there can be more than one to address
    echo $email_map->getFrom();

    echo $email->getSubject();
    echo $email->getPlainBody();

    print_r($email_map->getAttachments());

## Todo

Right now you have to have the full email before you can parse it, some email can be pretty big
if they have lots of attachments (or one really big attachment). So it would probably be prudent
to make `parse()` be able to take a file-pointer so it can read in the email line by line or something.
I haven't done this yet because it's never been a problem in the places I use this lib.

## License

MIT, pull requests encouraged.

