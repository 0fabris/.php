<?php

/*
 *  Titolo: Bot MeteoFVG
 *  Dev: github.com/0fabris
 *  Data: 10/2019
 *  v1.0
 *  Note: Versione molto basilare creata dopo gita scolastica ad OSMER Arpa e PC-FVG.
 */

/* Funzioni varie */
function urlenc($d)
{
    $ret = "";
    foreach ($d as $k => $v) {
        $ret .= urlencode($k) . '=' . urlencode($v) . '&';
    }
    return $ret;
}

function dataoggits()
{
    return strtotime(date('Y-m-d'));
}

/* Funzioni di invio messaggi a telegram */

function InviaBottoni($mess, $idpers)
{
    /* Questa parte non l'ho testata, spero funzioni */
    $postf = urlenc([
        "chat_id" => $idpers,
        'parse_mode' => 'html',
        'text' => $mess,
        'reply_markup' => ["keyboard" => [
                [["text" => "/domani"], ["text" => "/tendenza"]],
                [["text" => "/dopodomani"], ["text" => "/bollettino"]],
                [["text" => "/osservazioni"], ["text" => "/help"]]
            ]
        ]
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $GLOBALS['boturl'] . '/sendMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postf);
    //in caso sostituire $postf nella riga precedente con
    //'chat_id=' . $idpers . 'parse_mode=html&text=' . urlencode($mess) . '&reply_markup=%7B%22keyboard%22%3A+%5B%5B%7B%22text%22%3A+%22%2Fdomani%22%7D%2C+%7B%22text%22%3A+%22%2Ftendenza%22%7D%5D%2C+%5B%7B%22text%22%3A+%22%2Fdopodomani%22%7D%2C+%7B%22text%22%3A+%22%2Fbollettino%22%7D%5D%2C+%5B%7B%22text%22%3A+%22%2Fosservazioni%22%7D%2C+%7B%22text%22%3A+%22%2Fhelp%22%7D%5D%5D%7D'
    $res = curl_exec($ch);
}

function InviaFoto($linkp, $text, $idpers)
{
    $postf = urlenc([
        "chat_id" => $idpers,
        'parse_mode' => 'html',
        'photo' => $linkp,
        'caption' => $text,
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $GLOBALS['boturl'] . '/sendPhoto');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postf);
    $res = curl_exec($ch);
}

function Invia($mess, $idpers)
{
    $postf = urlenc([
        "chat_id" => $idpers,
        'parse_mode' => 'html',
        'text' => $mess,
    ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $GLOBALS['boturl'] . '/sendMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postf);
    $res = curl_exec($ch);
}

/* Funzioni per messaggi di broadcast */

function getListaMeteo()
{
    $fl = fopen('listapersone_bollettino.json', 'r');
    $lista = json_decode(fread($fl, filesize('listapersone_bollettino.json')), 1);
    fclose($fl);
    return $lista;
}

function saveListaMeteo($lista)
{
    $fl = fopen('listapersone_bollettino.json', 'w');
    fwrite($fl, json_encode($lista));
    fclose($fl);
    return $lista;
}

function getBollettinoReg()
{
    $nf = '';
    foreach (scandir('.') as $f) {
        if (strpos($f, 'PW') !== false) {
            $nf = $f;
        }
    }
    if (str_replace('PW', '', str_replace('.xml', '', $nf)) == date('Ymd')) {
        //Se ho bollettino oggi
        $nomefile = $nf;
    } else {
        //Invia($nf,$GLOBALS['persona']);
        unlink($nf);
        $lnk = 'https://dev.meteo.fvg.it/';
        $sito = file_get_contents($lnk);
        preg_match_all('/<a target="_blank" href="(.*?)">/', $sito, $output_array);
        $nomefile = end(explode('/', $output_array[1][0]));
        if (!file_exists($nomefile)) {
            $retstr = file_get_contents($lnk . $output_array[1][0]);
            scrivifile($nomefile, $retstr);
        }
    }
    $xmlobj = simplexml_load_file($nomefile);
    return $xmlobj;
}

function broadcastBollettino($lista)
{
    $boll = getBollettinoReg();
    foreach ($lista as $persona)
        InviaFoto('https://www.meteo.fvg.it/previ/oggi.png?v=' . dataoggits(), 'Bollettino Meteo Regionale: ' . PHP_EOL . PHP_EOL . html_entity_decode($boll->previsioni->SITUAZIONEGENERALE_TESTO), $persona);
}

function scrivifile($nome, $cosa)
{
    $fl = fopen($nome, 'w');
    fwrite($fl, $cosa);
    fclose($fl);
}

/* Parser Vari */

function parseMonti($fl, $idzona)
{
    $base = $fl->previsioni->scadenze->scadenza[0]->zone->zona;
    $txt = "";
    foreach ($base as $zona) {
        if ($zona->attributes()['nome'] == $idzona) {
            $txt .= '<b>Situazione di ' . str_replace('_', ' ', $zona->attributes()['descrizione']) . '</b>' . PHP_EOL . PHP_EOL;
            $txt .= '<i>' . html_entity_decode($zona->TESTO) . '</i>' . PHP_EOL . PHP_EOL;
            $txt .= 'Probabilit' . html_entity_decode('&agrave;') . PHP_EOL;
            $txt .= ' - Precipitazioni: ' . $zona->PROBABILITAPRECIPITAZIONI . $zona->PROBABILITAPRECIPITAZIONI->attributes()['um'] . PHP_EOL . ' - Temporali: ' . $zona->PROBABILITATEMPORALI . $zona->PROBABILITATEMPORALI->attributes()['um'] . PHP_EOL . PHP_EOL;
            $txt .= 'Neve a ' . $zona->QUOTANEVICATA . ' ' . $zona->QUOTANEVICATA->attributes()['um'] . PHP_EOL . PHP_EOL;
            return $txt;
        }
    }
    return '';
}

function parseCitta($fl, $idcitta)
{
    $base = $fl->previsioni->scadenze->scadenza[0]->zone->zona;
    $txt = "";
    foreach ($base as $zona) {
        if ($zona->attributes()['nome'] == $idcitta) {
            $txt .= '<b>Situazione di ' . $zona->attributes()['descrizione'] . '</b>' . PHP_EOL . PHP_EOL;
            $txt .= "<i>Attendibilit" . html_entity_decode("&agrave;") . " dell'" . $zona->ATTENDIBILITA . "%</i>" . PHP_EOL;
            $txt .= '<i>Cielo ' . $zona->CIELO_DESCRIZIONE . '</i>' . PHP_EOL;
            $txt .= '<i>Pioggia ' . (($zona->PIOGGIA_DESCRIZIONE == "") ? 'assente' : $zona->PIOGGIA_DESCRIZIONE) . '</i>' . PHP_EOL;
            $txt .= '<i>Neve ' . (($zona->NEVE_DESCRIZIONE == "") ? 'assente' : $zona->NEVE_DESCRIZIONE) . '</i>' . PHP_EOL;
            $txt .= '<i>Temporale ' . (($zona->TEMPORALE_DESCRIZIONE == "") ? 'assente' : $zona->TEMPORALE_DESCRIZIONE) . '</i>' . PHP_EOL;
            $txt .= '<i>Nebbia ' . (($zona->NEBBIA_DESCRIZIONE == "") ? 'assente' : $zona->NEBBIA_DESCRIZIONE) . '</i>' . PHP_EOL;
            $txt .= '<i>Vento ' . (($zona->VENTO_DESCRIZIONE == "") ? 'assente' : $zona->VENTO_DESCRIZIONE) . '</i>' . PHP_EOL;
            return $txt;
        }
    }
    return '';
}

function parseFascia($fl, $idf)
{
    $base = $fl->previsioni->scadenze->scadenza[0]->zone->zona;
    $txt = "";
    foreach ($base as $zona) {
        if ($zona->attributes()['nome'] == $idf) {
            $txt .= '<b>Situazione Fascia ' . str_replace('_', '', $zona->attributes()['descrizione']) . '</b>' . PHP_EOL . PHP_EOL;
            $txt .= 'Probabilit' . html_entity_decode('&agrave;') . PHP_EOL;
            $txt .= ' - Precipitazioni: ' . $zona->PROBABILITAPRECIPITAZIONI . $zona->PROBABILITAPRECIPITAZIONI->attributes()['um'] . PHP_EOL . ' - Temporali: ' . $zona->PROBABILITATEMPORALI . $zona->PROBABILITATEMPORALI->attributes()['um'] . PHP_EOL . PHP_EOL;
            $txt .= 'Neve a ' . $zona->QUOTANEVICATA . ' ' . $zona->QUOTANEVICATA->attributes()['um'] . PHP_EOL . PHP_EOL;

            return $txt;
        }
    }
    return '';
}

function parseZona($fl, $idz)
{ //REGIONE = zona
    $base = $fl->previsioni->scadenze->scadenza[0]->zone->zona;
    $txt = "";
    foreach ($base as $zona) {
        if ($zona->attributes()['nome'] == $idz) {
            $txt .= '<b>Situazione della Zona ' . str_replace('_', ' ', $zona->attributes()['descrizione']) . '</b>' . PHP_EOL . PHP_EOL;
            $txt .= '<i>' . html_entity_decode($zona->TESTO) . '</i>' . PHP_EOL . PHP_EOL;
            $txt .= "<i>Attendibilit" . html_entity_decode("&agrave;") . " dell'" . $zona->ATTENDIBILITA . "%</i>" . PHP_EOL . PHP_EOL;
            $txt .= "Evoluzione:" . PHP_EOL;
            $txt .= " - Ore 00 : <b>" . $zona->EVOLUZIONE00_DESCRIZIONE . "</b>" . PHP_EOL;
            $txt .= " - Ore 12 : <b>" . $zona->EVOLUZIONE12_DESCRIZIONE . "</b>" . PHP_EOL;
            $txt .= " - Ore 24 : <b>" . $zona->EVOLUZIONE24_DESCRIZIONE . "</b>" . PHP_EOL;
            return $txt;
        }
    }
    return '';
}

/* Ottengo informazioni persona che mi scrive */

$in = file_get_contents('php://input');
$fl = fopen('richiesta.json', 'w');
fwrite($fl, print_r($in, 1));
fclose($fl);

/* Decodifico messaggio che ricevo da telegram */

$inpdata = json_decode($in, 1)['message'];

/* Dichiaro informazioni BOT */

$bot_user = 'usertelegram'; //Username Telegram

$bottoken = 'code:token'; //Token da BotFather

$boturl = 'https://api.telegram.org/bot' . $bottoken; //Url per inviare messaggi a Telegram


//In caso di visualizzazione da Browser e non da Telegram mostro una pagina casuale
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mozilla') !== false) {
    ?>
    <html>

    <head>
        <title>
            Pagina WEB MeteoFVG Bot
        </title>
    </head>

    <body>
        <h1>
            <a href="<?php echo "http://t.me/" . $bot_user; ?>">Rispondo su TG</a>
        </h1>
    </body>

    </html>
<?php
    exit;
}

/* 
    Vedo se il messaggio da inviare corrisponde al bollettino 
    (sistema automatico basato su richieste GET attrib)
*/

$listapersboll = getListaMeteo();

if ($_GET['broadcast']) {
    broadcastBollettino($listapersboll);
    exit;
}

//Capisco che comando mi ha inviato il mittente
$cmd = explode(' ', $inpdata['text']);
$persona = $inpdata['chat']['id'];
$gruppo = false;

/* Vedo se la conversazione rimane privata o gruppo */
if ($inpdata['chat']['type'] != 'private') {
    $cmd[0] = explode('@' . $bot_user, $cmd[0])[0];
    $gruppo = true;
}

/* Dividi Comandi */

switch ($cmd[0]) {
    case '/help':
        Invia('I comandi disponibili sono visibili cliccando l\'icona / vicino al campo per scrivere messaggi di testo!', $persona);
        break;

    case '/newson':
        foreach ($listapersboll as $pers)
            if ($persona == $pers) {
                Invia('Sei gia presente!', $persona);
                exit;
            }
        array_push($listapersboll, $persona);
        saveListaMeteo($listapersboll);
        Invia('Sei Stato Aggiunto alla Lista di Ricezione Bollettino! Ogni giorno a Mezzogiorno (Ora Italiana) ti arrivera\' il bollettino meteo', $persona);
        break;

    case '/newsoff':
        $flag = false;
        foreach ($listapersboll as $k => $pers)
            if ($persona == $pers) {
                $flag = true;
                unset($listapersboll[$k]);
                saveListaMeteo($listapersboll);
                break;
            }
        if ($flag)
            Invia('Sei Stato Rimosso dalla Lista di Ricezione Bollettino!', $persona);
        else
            Invia('Non sei presente nella Lista!', $persona);
        break;

    case '/bollettino':
        InviaFoto('https://www.meteo.fvg.it/previ/oggi.png?v=' . dataoggits(), html_entity_decode(getBollettinoReg()->previsioni->SITUAZIONEGENERALE_TESTO), $persona);
        break;

    case '/tendenza':
        Invia(html_entity_decode(getBollettinoReg()->previsioni->TENDENZA_TESTO), $persona);
        break;

    case '/domani':
        $rd = getBollettinoReg();
        $txt = '<b>Previsioni per il ' . $rd->previsioni->scadenze->scadenza[0]->attributes()['data_validita'] . '</b>' . PHP_EOL . PHP_EOL;
        $regione = $rd->previsioni->scadenze->scadenza[0]->zone->zona[0];
        $txt .= html_entity_decode($regione->TESTO) . PHP_EOL . PHP_EOL;
        $txt .= "<i>Attendibilit" . html_entity_decode("&agrave;") . " dell'" . $regione->ATTENDIBILITA . "%</i>";
        InviaFoto('https://www.meteo.fvg.it/previ/domani.png?v=' . dataoggits(), $txt, $persona);
        break;

    case '/dopodomani':
        $rd = getBollettinoReg();
        $txt = '<b>Previsioni per il ' . $rd->previsioni->scadenze->scadenza[1]->attributes()['data_validita'] . '</b>' . PHP_EOL . PHP_EOL;
        $regione = $rd->previsioni->scadenze->scadenza[0]->zone->zona[0];
        $txt .= html_entity_decode($regione->TESTO) . PHP_EOL . PHP_EOL;
        $txt .= "<i>Attendibilit" . html_entity_decode("&agrave;") . " dell'" . $regione->ATTENDIBILITA . "%</i>";
        InviaFoto('https://www.meteo.fvg.it/previ/dopodomani.png?v=' . dataoggits(), $txt, $persona);
        break;

    case '/osservazioni':
        $fl = getBollettinoReg();
        $send = 'Osservazioni del ' . $fl->osservazioni->attributes()['data'] . PHP_EOL . PHP_EOL;
        foreach ($fl->osservazioni->stazioni->stazione as $staz) {
            if ($staz->attributes()['nome'] != "") {
                $send .= "<b>Stazione di " . ucfirst(strtolower($staz->attributes()['nome'])) . '</b>' . PHP_EOL;
                $send .= " - Min.: " . $staz->TMIN . $staz->TMIN->attributes()['um'] . PHP_EOL;
                $send .= " - Max.: " . $staz->TMAX . $staz->TMAX->attributes()['um'] . PHP_EOL;
                $send .= " - RR.: " . $staz->RR . ' ' . $staz->RR->attributes()['um'] . PHP_EOL . PHP_EOL;
            }
        }
        Invia($send, $persona);
        break;

    case '/start':
        if (!$gruppo)
            InviaBottoni('Ciao, Questo bot visualizza le previsioni metereologiche riguardanti il FVG, dati presi da www.meteo.fvg.it!', $persona);
        else
            Invia('Ciao, Questo bot visualizza le previsioni metereologiche riguardanti il FVG, dati presi da www.meteo.fvg.it!', $persona);
        break;

    case '/monti':
        $boll = getBollettinoReg();
        $ids = ['A1', 'A2', 'A3', 'A4'];
        $txt = "";
        Invia(parseZona($boll, 'Z1'), $persona);
        foreach ($ids as $id) {
            Invia(parseMonti($boll, $id), $persona);
        }
        break;

    case '/altapianura': //Udine e Pordenone
        $boll = getBollettinoReg();
        $ids = ['A5', 'A6'];
        Invia(parseZona($boll, 'Z2'), $persona);
        foreach ($ids as $id) {
            Invia(parseCitta($boll, $id), $persona);
        }
        break;

    case '/bassapianura': //Gorizia
        $boll = getBollettinoReg();
        $ids = ['A7'];
        Invia(parseZona($boll, 'Z3'), $persona);
        foreach ($ids as $id) {
            Invia(parseCitta($boll, $id), $persona);
        }
        break;

    case '/costa': //Trieste Lignano
        $boll = getBollettinoReg();
        $ids = ['A8', 'A9'];
        Invia(parseZona($boll, 'Z4'), $persona);
        foreach ($ids as $id) {
            Invia(parseCitta($boll, $id), $persona);
        }
        break;

    default:
        if (!$gruppo)
            InviaBottoni('Purtroppo il messaggio non e` stato riconosciuto! Riprova con /help', $persona);
        break;
}
?>