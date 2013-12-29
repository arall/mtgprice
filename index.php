<?php

include("simple_html_dom.php");

$list = file_get_contents("list.txt");

$cards = explode("\n", $list);
if(count($cards)){
	$price = 0;
	foreach($cards as $card){
		//Card title
		$tmp = explode(" ", $card);
		$num = str_replace("x", "", $tmp[0]);
		$cardTitle = trim(str_replace($tmp[0], "", $card));
		//Search
		echo colorize("\nFinding: ".$cardTitle, "notice");
		$results = searchCard($cardTitle);
		if(count($results)){
			foreach($results as $result){
				if($result['lang']=="Español"){
					echo colorize("Price: ".$result['price']);
					echo colorize("Set: ".$result['set']);
					echo colorize("Status: ".$result['status']);
					$price += $num*$result['price'];
					break;
				}
			}
		}else{
			echo colorize("Card not found", "error");
		}
	}
	echo colorize("\nTotal price: ".$price, "success");
}else{
	die(colorize("No cards found", "error"));
}

function searchCard($cardTitle){
	$post = array(
		"game"=>"magic",
		"nombrecarta"=>$cardTitle,
		"busqsimple"=>"Buscar",
	);
	$res = curl("http://www.rebellion.es/tiendarebel/buscador.php", $post);
	$html = str_get_html($res);
	if(is_object($html)){
		// Find all images 
		$table = $html->find('table.tablabasica', 0);
		if(is_object($table)){
			//Card Set
	    	foreach($table->find('tr[valign=top]') as $edition){
	    		$data = array();
	    		$data['set'] = @$edition->find('td img', 1)->title;
	    		$subtable = $edition->find('table', 0);
	       		if(is_object($subtable)){
	       			//Card type (lang+status+price)
	       			foreach($subtable->find('tr') as $type){
		       			if(is_object($type)){
			       			$data['lang'] = @$type->find('td img', 0)->title;
			       			$data['status'] = @$type->find('td[width=35px]', 0)->plaintext;
			       			$data['price'] = @current(explode(" ", $type->find('td font', 0)->plaintext));
			       			$results[] = $data;
		       			}
		       		}
				}
			}
		}
	}
	return $results;
}

function curl($url, $post=""){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.41 Safari/537.36');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    if($post){
    	curl_setopt($ch, CURLOPT_POST , true);
    	@curl_setopt($ch, CURLOPT_POSTFIELDS , $post);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $exec = curl_exec($ch);
    curl_close($ch);
    return $exec;
}

//http://softkube.com/blog/generating-command-line-colors-with-php/
function colorize($text, $status=null) {
    $out = "";
    switch(strtolower($status)){
        case "banner":
        	$out = "[0;32m"; //Green
        break;
        case "title":
			$text = "[+] ".$text;
			$out = "[0;34m"; //Blue
		break;
		case "debug":
			if($config['general']['vervose']>=2){
				$text = " * ".$text;
			}else{
				$text = "";
			}
		break;
		case "success":
			$text = " - ".$text;
			$out = "[0;32m"; //Green
		break;
		case "error":
			$text = " ! ".$text;
			$out = "[0;31m"; //Red
		break;
		case "warning":
			$text = " X ".$text;
			$out = "[1;33m"; //Yellow
		break;
		case "notice":
			$text = " - ".$text;
			$out = "[0;34m"; //Blue
		break;
		default:
			$text = " | ".$text;
		break;
		case "table":
			$lines = explode("\n", $text);
			$text = "";
			if($lines){
				$l = count($lines)-1;
				foreach($lines as $i=>$line){
					$text .= " ".$line;
					if($i<$l) $text .= "\n";
				}
		}
		break;
    }
    if($text){
        if($out)
            return chr(27).$out.$text.chr(27)."[0m \n";
        else
            return $text."\n";
    }
}