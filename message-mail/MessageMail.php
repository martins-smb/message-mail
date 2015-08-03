<?php
	class MessageMail {
		const TEXT_PLAIN_MIME_TYPE  	= "TEXT/PLAIN";
		const TEXT_HTML_MIME_TYPE  	= "TEXT/HTML";
		const TEXT_MIME_TYPE  		= "TEXT";
		const MULTIPART_MIME_TYPE  	= "MULTIPART";
		const MESSAGE_MIME_TYPE  	= "MESSAGE";
		const APPLICATION_MIME_TYPE	= "APPLICATION";
		const AUDIO_MIME_TYPE  		= "AUDIO";
		const IMAGE_MIME_TYPE  		= "IMAGE";
		const VIDEO_MIME_TYPE  		= "VIDEO";
		const OTHER_MIME_TYPE  		= "OTHER";
		
		private $server		='';
		private $username	='';
		private $password	='';
		private $marubox		='';	
		private $email		='';			
		
		public function __construct(
				$username,
				$password,
				$EmailAddress,
				$mailserver		='localhost',
				$servertype		='pop',
				$port			='110',
				$ssl 			= false
		) {
			if($servertype=='imap') {
				if($port=='') $port='143'; 
				$strConnect='{'.$mailserver.':'.$port. '}INBOX'; 
			} else {
				$strConnect='{'.$mailserver.':'.$port. '/pop3'.($ssl ? "/ssl" : "").'}INBOX'; 
			}
			
			$this->server			=	$strConnect;
			$this->username			=	$username;
			$this->password			=	$password;
			$this->email				=	$EmailAddress;
		}
		
		public function connect() {
			$this->marubox=@imap_open($this->server,$this->username,$this->password);
			
			if(!$this->marubox) {
				echo "Error: Connecting to mail server";
				exit;
			}
		}
		
		public function getHeaders($mid) {
			if(!$this->marubox)
				return false;
	
			$mail_header=imap_header($this->marubox,$mid);
			$sender=$mail_header->from[0];
			$sender_replyto=$mail_header->reply_to[0];
			if(strtolower($sender->mailbox)!='mailer-daemon' && strtolower($sender->mailbox)!='postmaster') {
				$mail_details=array(
						'from'=>strtolower($sender->mailbox).'@'.$sender->host,
						'fromName'=>$sender->personal,
						'toOth'=>strtolower($sender_replyto->mailbox).'@'.$sender_replyto->host,
						'toNameOth'=>$sender_replyto->personal,
						'subject'=>$mail_header->subject,
						'to'=>strtolower($mail_header->toaddress)
					);
			}
			
			return $mail_details;
		}
		
		private function get_mime_type(&$structure) { 
			$primary_mime_type = array(
					MessageMail::TEXT_MIME_TYPE, 
					MessageMail::MULTIPART_MIME_TYPE, 
					MessageMail::MESSAGE_MIME_TYPE, 
					MessageMail::APPLICATION_MIME_TYPE, 
					MessageMail::AUDIO_MIME_TYPE, 
					MessageMail::IMAGE_MIME_TYPE, 
					MessageMail::VIDEO_MIME_TYPE, 
					MessageMail::OTHER_MIME_TYPE
			); 
			
			if($structure->subtype) { 
				return $primary_mime_type[(int) $structure->type] . '/' . $structure->subtype; 
			}
			 
			return MessageMail::TEXT_PLAIN_MIME_TYPE; 
		}
		 
		private function get_part(
				$stream, 
				$msg_number, 
				$mime_type, 
				$structure = false, 
				$part_number = false
		) { 
			if(!$structure) { 
				$structure = imap_fetchstructure($stream, $msg_number); 
			} 
			
			if($structure) { 
				if($mime_type == $this->get_mime_type($structure)) { 
					if(!$part_number) { 
						$part_number = "1"; 
					}
					 
					$text = imap_fetchbody($stream, $msg_number, $part_number); 
					
					if($structure->encoding == 3) { 
						return imap_base64($text); 
					} else if($structure->encoding == 4) { 
						return imap_qprint($text); 
					} else { 
						return $text; 
					} 
				} 
				
				if($structure->type == 1) { 
					while(list($index, $sub_structure) = each($structure->parts)) { 
						if($part_number) { 
							$prefix = $part_number . '.'; 
						}
						 
						$data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure, $prefix . ($index + 1)); 
						if($data) { 
							return $data; 
						} 
					} 
				} 
			} 
			return false; 
		} 
		
		public function getTotalMails() {
			if(!$this->marubox)
				return false;
	
			$headers=imap_headers($this->marubox);
			return count($headers);
		}
		
		public function GetAttach($mid,$path) {
			if(!$this->marubox)
				return false;
	
			$struckture = imap_fetchstructure($this->marubox,$mid);
			$ar="";
			if($struckture->parts) {
				foreach($struckture->parts as $key => $value) {
					$enc=$struckture->parts[$key]->encoding;
					if($struckture->parts[$key]->ifdparameters) {
						$name = $struckture->parts[$key]->dparameters[0]->value;
						$message = imap_fetchbody($this->marubox,$mid,$key+1);
						
						if ($enc == 0)
							$message = imap_8bit($message);
						if ($enc == 1)
							$message = imap_8bit ($message);
						if ($enc == 2)
							$message = imap_binary ($message);
						if ($enc == 3)
							$message = imap_base64 ($message); 
						if ($enc == 4)
							$message = quoted_printable_decode($message);
						if ($enc == 5)
							$message = $message;
						
						$fp=fopen($path.$name,"w");
						fwrite($fp,$message);
						fclose($fp);
						$ar=$ar.$name.",";
					}
					
					if($struckture->parts[$key]->parts) {
						foreach($struckture->parts[$key]->parts as $keyb => $valueb) {
							$enc=$struckture->parts[$key]->parts[$keyb]->encoding;
							if($struckture->parts[$key]->parts[$keyb]->ifdparameters) {
								$name = $struckture->parts[$key]->parts[$keyb]->dparameters[0]->value;
								$partnro = ($key+1).".".($keyb+1);
								$message = imap_fetchbody($this->marubox,$mid,$partnro);
								
								if ($enc == 0)
									   $message = imap_8bit($message);
								if ($enc == 1)
									   $message = imap_8bit ($message);
								if ($enc == 2)
									   $message = imap_binary ($message);
								if ($enc == 3)
									   $message = imap_base64 ($message);
								if ($enc == 4)
									   $message = quoted_printable_decode($message);
								if ($enc == 5)
									   $message = $message;
								
								$fp=fopen($path.$name,"w");
								fwrite($fp,$message);
								fclose($fp);
								$ar=$ar.$name.",";
							}
						}
					}				
				}
			}
			$ar=substr($ar,0,(strlen($ar)-1));
			return $ar;
		}
		
		public function getBody($mid) {
			if(!$this->marubox)
				return false;
	
			$body = $this->get_part(
					$this->marubox, 
					$mid, 
					MessageMail::TEXT_HTML_MIME_TYPE
			);
			
			if ($body == "")
				$body = $this->get_part(
						$this->marubox, 
						$mid, 
						MessageMail::TEXT_PLAIN_MIME_TYPE
				);
			
			if ($body == "") { 
				return "";
			}
			
			return $body;
		}
		
		public function deleteMails($mid) {
			if(!$this->marubox)
				return false;
		
			imap_delete($this->marubox,$mid);
		}
		
		public function close_mailbox() {
			if(!$this->marubox)
				return false;
	
			imap_close($this->marubox,CL_EXPUNGE);
		}
	}
?>