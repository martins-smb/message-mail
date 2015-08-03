# Message Mail

### Example Usage
```php

$obj = new MessageMail(
		$user, 
		$pass, 
		$user, 
		$imap_host, 
		'imap', 
		$imap_port, 
		false
);
		
$obj->connect();
		
$head = $obj->getHeaders($email);
$email_head = "<b>Subject ::</b> " . fix_text($head['subject']) . "<br>";
$email_head .= "<b>To ::</b> "     . $head['to']                . "<br>";
$email_head .= "<b>Cc ::</b> "     . $head['toOth']             . " " . $head['toNameOth']          . "<br>";
$email_head .= "<b>From ::</b> "   . $head['from']              . " " . fix_text($head['fromName']) . "<br>";
$email_head .= "<br><br>";
		
$str = $obj->GetAttach($email,"anexos/");
$ar = explode(",",$str);
foreach($ar as $key=>$value) {
  $email_anexo = ($value=="") ? "" : "<font face=\"Tahoma, Geneva, sans-serif\" color=\"#333333\" size=\"-2\"><b>Anexo :: </b><a href=\"anexos/" . $value . "\" style=\"text-decoration:none;\" target=\"_blank\">" . $value . "</a></font><br>";
}
		
$email_anexo .= "<br><br>";
		
$email_body = $obj->getBody($email);
$email_body = str_replace("<a", "<a target=_blank", $email_body);
		
$obj->close_mailbox();

```
