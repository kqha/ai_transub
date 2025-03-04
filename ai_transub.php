<?php
  $conf = array(
    "url"=>"http://172.17.6.185:11434",
    "model"=>"llama3:latest",
    "cutoff"=>100,
    "autosave"=>50,
    "debug"=>false,
    "tmpdir"=>"C:/Temp",
    "sample"=>array(
      "Translate all next messages to Bahasa Indonesia, retain text formatting in translated text, only returned translated text with formatting only, neved add other text that not included in original message.",
      "Baik, saya akan menerjemahkan seluruh chat berikutnya ke dalam Bahasa Indonesia dengan tetap mempertahankan format teks yang ada.",
      "Good morning",
      "Selamat pagi",
    ),
  );
  
  error_reporting(E_ERROR);
  
  function _autosave() {
		global $tmpdata,$tmpfile,$srt,$lines,$pos;
		
		$tmpdata['pos'] = $pos;
		$tmpdata['srt'] = $srt;
		$tmpdata['lines'] = $lines;
		file_put_contents($tmpfile,serialize($tmpdata));
	}
	
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
    
    if ((count($chat)==0) || ((count($chat)-count($conf['sample']))>=($conf['cutoff']*2))) {
			if (count($chat)>0) {
				echo ($conf['debug'])? "Cutoff Translation\n":"[C]";
			}
      $chat = array(array("role"=>"user","content"=>$conf['sample'][0],),array("role"=>"assistant","content"=>$conf['sample'][1],),array("role"=>"user","content"=>$conf['sample'][2],),array("role"=>"assistant","content"=>$conf['sample'][3],),);
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
  
	//Checking temporary save
	$tmpfile = $conf['tmpdir']."/ai_transub.tmp";
	$hash = sha1_file($_SERVER['argv'][1]);
	if (file_exists($tmpfile)) {
		$tmpdata = unserialize(file_get_contents($tmpfile));
		if ($hash==$tmpdata['hash']) {
			echo "Continuing previous progress...\n";
			$pos = $tmpdata['pos'];
			$srt = $tmpdata['srt'];
			$lines = $tmpdata['lines'];
		} else {
			echo "WARNING! Overwriting other temporary progress!\n";
			$tmpdata = array("hash"=>$hash);
		}
	} elseif ($conf['tmpdir']!="") $tmpdata = array("hash"=>$hash);
	//End of checking temporary save
	
	if (!isset($tmpdata) || (count($tmpdata)==1)) {
		echo "Collecting lines to translate...\n";
		$srt = explode(chr(10),file_get_contents($_SERVER['argv'][1]));	//break every line to an array
		$lines = array();
		$stt = 0;
		$n = 0;
		$pos = 0;
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
		if (isset($tmpdata)) _autosave();
	}
	
  echo "Translating ".(count($lines)-$pos)." lines...\n";
	$chat = array();
  for ($a=$pos;$a<count($lines);$a++) {
		$value = $lines[$a];
    $trans = _translate($value[1]);
    if ($conf['debug']) {
      echo "Original Text: ".$value[1]."\n";
      echo "Translation: ".$trans."\n";
    } else echo ".";
    $srt[$value[0]] = $trans;
		if (($a+1)%$conf['autosave']==0) {
			echo ($conf['debug'])? "Autosaving\n":"[S]";
			$pos = $a;
			_autosave();
		}
  }
	
	if (file_exists($tmpfile)) unlink($tmpfile);
  file_put_contents("translated.srt",implode(chr(13).chr(10),$srt));
  echo "\nDone translating!\nYou can find 'translated.srt' in current directory (".getcwd().")\n";
?>
