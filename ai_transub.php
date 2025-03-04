<?php
  $conf = array(
    "url"=>"http://10.10.10.1:11434",
    "model"=>"llama3:latest",
    "cutoff"=>100,
    "debug"=>false,
    "sample_instruction"=>"Translate all next messages to Bahasa Indonesia, retain text formatting in translated text, only returned translated text with formatting only, neved include other text that not included in original message.",
    "sample_response"=>"Baik, saya akan menerjemahkan seluruh chat berikutnya ke dalam Bahasa Indonesia dengan tetap mempertahankan format teks yang ada.",
  );
  
  error_reporting(E_ERROR);
  
  function _getURL($url,$post=null,$file=null,$headers=null,$referer=null) {
		$ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_BINARYTRANSFER,true);
		curl_setopt($ch,CURLOPT_HEADER,false);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
		curl_setopt($ch,CURLOPT_DNS_USE_GLOBAL_CACHE,false);
		curl_setopt($ch,CURLOPT_DNS_CACHE_TIMEOUT,3);
		curl_setopt($ch,CURLOPT_IPRESOLVE,CURL_IPRESOLVE_V4);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		if ($post!=null) {
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
		}
		if ($headers==null) $headers = $defhead;
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
		if ($referer!=null) {
			curl_setopt($ch,CURLOPT_REFERER,$referer);
		}
		if (strtolower(substr($url,0,6))=="https:") {
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		}
		
		$retval = curl_exec($ch);
		if ($retval===false) {
			echo "cURL Error ".curl_errno($ch)." at ".date("d-m-Y H:i:s").", msg: ".curl_error($ch)."\n".var_export(curl_getinfo($ch),true)."\n";
      die();
		}
    curl_close($ch);
		return $retval;
	}
  
  function _translate($txt) {
    global $chat, $conf;
    
    if ((count($chat)==0) || (count($chat)>=$conf['cutoff'])) {
      $chat = array(
        array(
          "role"=>"user",
          "content"=>$conf['sample_instruction'],
        ),
        array(
          "role"=>"assistant",
          "content"=>$conf['sample_response'],
        ),
      );
    }
    $chat[] = array(
      "role"=>"user",
      "content"=>$txt,
    );
    $data = json_encode(array(
      "model"=>$conf['model'],
      "messages"=>$chat,
      "stream"=>false,
    ));
    try {
      $resp = json_decode(_getURL($conf['url']."/api/chat",$data,null,array("Content-Type: application/json")),true);
      //var_dump($resp);
      if (array_key_exists("message",$resp)) {
        $retval = $resp['message']['content'];
        $chat[] = array(
          "role"=>$resp['message']['role'],
          "content"=>$retval,
        );
      } else die("Invalid ollama return!\n");
    } catch (Exception $e) {
      die("Error ".$e->getMessage()."\n");
    }
    return $retval;
  }
  
  if (($_SERVER['argc']<2) || !file_exists($_SERVER['argv'][1])) {
    die("Usage:\nai_transub.php [.srt file]\n");
  }
  
  echo "Collecting lines to translate...\n";
  $chat = array();
  $srt = explode(chr(10),file_get_contents($_SERVER['argv'][1]));
  $lines = array();
  $stt = 0;
  $n = 0;
  foreach($srt as $key=>$value) {
    $value = trim($value);
    switch ($stt) {
      case 0:
        if (is_numeric($value)) {
          $stt = 1;
          $n = $key;
        }
        break;
      case 1:
        if ($key>$n+1) {
          if ($value=="") {
            $stt = 0;
          } else $lines[] = array($key,$value);
        }
        break;
    }
  }
  echo "Translating ".count($lines)." lines...\n";
  foreach($lines as $value) {
    $trans = _translate($value[1]);
    if ($conf['debug']) {
      echo "Original Text: ".$value[1]."\n";
      echo "Translation: ".$trans."\n";
    } else echo ".";
    $srt[$value[0]] = $trans;
  }
  file_put_contents("translated.srt",implode(chr(13).chr(10),$srt));
  echo "\nDone translating!\nYou can find 'translated.srt' in current directory (".getcwd().")\n";
?>