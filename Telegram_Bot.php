<html>
	<head>
		<title>
			Bot Telegram
		</title>
	</head>

<?php

/*
 * Titolo: "Bot Telegram Classe"
 * Realizzato da me
 * Start: 02/2019
 * Version: 1.1
 * ChangeLog: Start Project, upload project on GitHub
 * Note: Perche risponda ai messaggi settare APIURL/setWebhook?url=https://url/script.php
 */


// ------- Variabili varie ------- //

//Leggo dati ricevuti
$content = file_get_contents("php://input");
$getinp = json_decode($content,1)['message'];

//Dati Vari
$classe = 'Classe della scuola';

$bot_user = 'user_bot';

$bottoken = 'tokpart1:tokpart2';

$boturl = 'https://api.telegram.org/bot'.$bottoken;

//Ottengo chat info
$chatid = $getinp['chat']['id'];

//Ottengo mittente
$from = $getinp['from']['first_name'];

//Leggo messaggio ricevuto
$messric = $getinp['text'];

// ----- Altro ----- //
$vaacapo=PHP_EOL;

// ------- Funzioni Usate ------- //
function logSys($inpric)
{
	$fl = fopen('input_bot.txt','w');
	fwrite($fl,print_r($inpric,1));
	fclose($fl);
}

function logErr($inpric)
{
	$fl = fopen('errore_bot.txt','w');
	fwrite($fl,print_r($inpric,1));
	fclose($fl);
}

function leggifile($nome){
	$fl = fopen($nome,'r');
	$a = fread($fl,1024);
	fclose($fl);
	return $a;
}

function getEventi(){
	return 'Funzione non ancora aggiunta!';
}

function AvvisaTelegram($messaggio){	
	//Richiesta all'API Telegram
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $GLOBALS['boturl'].'/sendMessage');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "chat_id=".$GLOBALS['chatid'].'&parse_mode=html&text='.urlencode($messaggio));
	$res = curl_exec($ch);
	logErr(print_r(json_decode($res,1),1));
}

// ------- Inizio Programma ------- //
logSys($getinp); //Memorizzo l'ultimo dato ricevuto
if(strpos($getinp['chat']['type'],'group')!==false)
{
	//Se e' in gruppo
	$command = explode(' ',$messric);
	$messfin = "";
	//Comandi da gruppo
	switch($command[0]){
		case '/ciao':
			$messfin = 'Ciao, <b>'.$from.'</b>, benvenuto al comando /ciao di questo bot!'.$vaacapo.'<i>Questa è una prova di messaggio su gruppo, in privato il messaggio è diverso!</i>';
			break;
		case '/orario':
			if(!isset($command[1]))
				$messfin = 'Ciao <b>'.$from.'</b>, '.$vaacapo.'<i>L\'orario per la giornata odierna per la classe '.$classe.' è il seguente:</i>'.$vaacapo;
			else
				$messfin = 'Ciao <b>'.$from.'</b>, '.$vaacapo.'<i>L\'orario tra '.$command[1].' giorni per la classe '.$classe.' sarà il seguente:</i>'.$vaacapo;
			$messfin.=$vaacapo.'<pre>'.leggifile('orario_'.date('l', time()+($command[1]*86400)).'.txt').'</pre>';
			break;
		case '/orariodomani':
			$messfin = 'Ciao <b>'.$from.'</b>, '.$vaacapo.'<i>L\'orario per la giornata di domani per la classe '.$classe.' è il seguente:</i>'.$vaacapo;
			$messfin.=$vaacapo.'<pre>'.leggifile('orario_'.date('l', time()+86400).'.txt').'</pre>';
			break;
		case '/eventi':
			$messfin = 'Ciao <b>'.$from.'</b>, '.$vaacapo.'<i>Gli eventi per la '.$classe.' questa settimana sono i seguenti:</i>'.$vaacapo;
			if(strpos($getinp['chat']['title'],$classe)!==false)
				$messfin .= $vaacapo.'<pre>'.getEventi().'</pre>';
			break;
	}
	if($messfin != "")
		AvvisaTelegram($messfin);
}else if(strpos($getinp['chat']['type'],'private')!==false){
	//Se e' chat in privato
	//comandi da privato
	AvvisaTelegram("<b>Bot utilizzabile solo da Gruppo!</b>");
}
?>

		<center><input type="button" onclick="location.href='https://t.me/<?php echo $bot_user;?>'" value="Click per TG"/></center>
	</body>
</html>
