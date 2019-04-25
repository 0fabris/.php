<html>
	<head>
		<title>
			Pannelli Solari
		</title>
	</head>

<?php

function UltimaDataFunzionante($data){
	$f = fopen('pannelli.txt','a');
	fwrite($f,'Ultima Data Funzionante='.$data);
	fclose($f);
}

function AvvisaMail($messaggio){
	$chi = 'your_email';
	$oggetto = 'ALLERTA! Pannelli non Vanno!';
	$headers = "From: Bot Pannelli <PHP@pannellisolari.it>\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1";
	mail($chi,$oggetto,$messaggio,$headers);
}

function AvvisaTelegram($messaggio){
	$botid = 'your_bot_id';
	$bottoken = 'your_bot_token';
	$chatid = 'your_chat_id';
	$boturl = 'https://api.telegram.org/bot'.$botid.':'.$bottoken.'/sendMessage';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $boturl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "chat_id=".$chatid.'&parse_mode=markdown&text='.$messaggio);
	if(!curl_exec($ch)){
		echo "Non ho Avvisato il Capo!";
	}	
}

function getRiga($divisore1,$divisore2,$testo){
	return explode($divisore2,explode($divisore1,$testo)[1])[0];
}

function getDati($riga) {
	$r1 = explode('">',$riga);
	$a = array();
	foreach($r1 as $el){
		if(strpos($el,'</td>') !== false){
			$a[] = explode('<td',str_replace('</td>','',$el))[0];
		}
	}
	return $a;
}

function QuantoPerso($data,$data2){
	$a=array();
	$ngiorni = date('d',strtotime($data2)-(strtotime($data2)-strtotime($data)));
	$nore = $ngiorni*24;
	$prezzo = 0.35;
	$mese = explode('/',$data)[1];
	if ($mese <=4){
		$a[] = round(((6000/30)/24)*$nore,2);
		$a[] = round(($a[0])*$prezzo,2).'€';
	}elseif($mese<=9){
		$a[] = round(((7000/30)/24)*$nore,2);
		$a[] = round(($a[0])*$prezzo,2).'€';
	}elseif($mese<=12){
		$a[] = round(((3200/30)/24)*$nore,2);
		$a[] = round(($a[0])*$prezzo,2).'€';
	}
	$a[]=$ngiorni;
	return $a;
}

function Produzione($testo,$mode){
	switch($mode){		
		case 1:
			//leggo
			$f = fopen('produzione.txt','r');
			$a = fread($f,1024);
			fclose($f);
			return $a;
			break;
		case 2:
			//scrivo
			$f = fopen('produzione.txt','w');
			fwrite($f,$testo);
			fclose($f);
			break;
	}
}

function DataLetta(){
	$f = fopen('pannelli.txt','r');
	$s = fread($f,1024);
	fclose($f);
	return explode('=',$s)[1];
}

function getPublicChartValues($page){
  	//Array associativo con PCVs.
	$assoc = array();
	$page = explode('<td class="base-grid-item-cell">',str_replace('<td class="base-grid-header-cell"><td class="base-grid-header-cell">Fabris_sas<br>Rendimento totale<br>Modifica contatore  [kWh]','',str_replace('<tr class="base-grid-item-alternate">','',str_replace('<tr class="base-grid-item">','',str_replace('</td>','',str_replace(' align="right">','>',str_replace('</tr>','',explode('</table>',explode('<tr>',$page)[1])[0])))))));
	for($i = 0;$i<(sizeof($page)-1);$i+=2){
		$assoc[$page[$i+1]] = $page[$i+2];
	}
	return($assoc);	
}

$linkpannelli = 'https://www.sunnyportal.com/Templates/PublicPageOverview.aspx?page=##UrData1&plant=##UrData2&splang=it-IT';

$page = file_get_contents($linkpannelli);

$ar_dati = getDati(getRiga('<tr class="base-grid-item">','</tr>',$page));

$data = explode('/>',getDati(getRiga('<tr class="base-grid-header">','</tr>',$page))[1])[2];

if ((($ar_dati[1] != '0,00')or($ar_dati[1] != 'Nessun dato disponibile'))and(!isset($_GET['test']))){	
	echo '<body bgcolor=\'lightgreen\'>';
	echo '<center><h1>Il giorno '.$data.', l\'impianto ha prodotto '.$ar_dati[1].' kWh.<br><br>Per ora Funziona!</h1></center>';
  
	UltimaDataFunzionante($data);
	
	//Se la richiesta GET la faccio da IFTTT mi avvisa dello stato via Telegram
	if($_GET['bot']=='ifttt'){
		
   		$mess = '*'.$data.'*%0D%0A%0AImpianto: *OK*%0D%0A%0AOggi: *'.$ar_dati[1].' kWh*%0D%0A%0AQuesto mese: *'.$ar_dati[2].' kWh*';
		
    		$old = Produzione(0,1);
		if ($old == $ar_dati[1]){
			$mess .= '%0D%0A%0AStai attento che dall\' ultima rilevazione il prodotto non è aumentato';
		}
		
    		Produzione($ar_dati[1],2);
		
    		AvvisaTelegram($mess);
	}
	
}
else{
	echo '<body bgcolor=\'orange\'>';
	$p = QuantoPerso($data,DataLetta());
	echo '<center><font color="blue"><h1>Il giorno '.$data.', l\' impianto non ha prodotto niente.<br><br>Possibile errore! Avviso Capo<br><br>';
	
  	echo 'Stai perdendo circa <font color="red">'.($p[0]).'</font> kW/giorno, <br>Quindi circa <font color="red">'.$p[1].'</h1></font><h2><br><br>';
	
 	echo 'Impianto Fuori uso da: <font color="red">'.$p[2]. ' giorni!</font></h2></font>';
	
  	//Mi avvisa sia via Mail che Telegram
  	AvvisaMail('<html><font color="red"><h1>Hai gia perso circa '.$p[0].' kW, guarda l\' impianto che non sta andando</h1></font></html></center>');
	AvvisaTelegram('*'.$data.'*%0D%0A%0A*Hai gia perso circa '.$p[0].' kW*%0D%0A%0A*L\' Impianto non sta andando, Verifica TUTTO!*');

}

$charturl = 'https://www.sunnyportal.com/Templates/PublicChartValues.aspx?ID=##YourPCV-ID&endTime='.$data.'%2023:59:59&splang=it-IT&plantTimezoneBias=120&name=';

echo '<center><iframe src="'.$charturl.'" width="500" height="400"></iframe></center>';
?>

		<center><a href='<?php echo $linkpannelli; ?>'>Click per andare a SunnyPortal</a></center>
	</body>
</html>
