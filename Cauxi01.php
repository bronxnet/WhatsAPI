<?php
set_time_limit(600);
require_once '/var/www/WhatsAPI/src/whatsprot.class.php';
//Change to your time zone
date_default_timezone_set('America/Manaus');

///////////////////////CONFIGURATION///////////////////////
//////////////////////////////////////////////////////////
$username = "559282182562";                      // Telephone number including the country code without '+' or '00'.
$password = "EYpGmM1ok/DrU9pNhKfxtMWqIo0=";      // A server generated Password you received from WhatsApp. This can NOT be manually created
$identity = "CauxiEventos";                      // Obtained during registration with this API or using MissVenom (https://github.com/shirioko/MissVenom) to sniff from your phone.
$nickname = "Cauxi01";                       // This is the username (or nickname) displayed by WhatsApp clients.
$target = "559293858217-1427915902";             // Destination telephone number including the country code without '+' or '00'.
$debug = false;                                  // Set this to true, to see debug mode.
///////////////////////////////////////////////////////////

function fgets_u($pStdn)
{
    $pArr = array($pStdn);

    if (false === ($num_changed_streams = stream_select($pArr, $write = NULL, $except = NULL, 0))) {
        print("\$ 001 Socket Error : UNABLE TO WATCH STDIN.\n");

        return FALSE;
    } elseif ($num_changed_streams > 0) {
        return trim(fgets($pStdn, 1024));
    }
    return null;
}

//This function only needed to show how eventmanager works.
function onGetProfilePicture($from, $target, $type, $data)
{
    if ($type == "preview") {
        $filename = "preview_" . $target . ".jpg";
    } else {
        $filename = $target . ".jpg";
    }
    $filename = "/var/www/WhatsAPI/img/profiles/" . $filename;
    $fp = @fopen($filename, "w");
    if ($fp) {
        fwrite($fp, $data);
        fclose($fp);
    }

    echo "- Profile picture saved in /".WhatsProt::PICTURES_FOLDER."\n";
}

function onPresenceReceived($username, $from, $type)
{
	$dFrom = str_replace(array("@s.whatsapp.net","@g.us"), "", $from);
		if($type == "available")
    		echo "<$dFrom is online>\n\n";
    	else
    		echo "<$dFrom is offline>\n\n";
}

echo "[] Logging in as '$nickname' ($username)\n";
//Create the whatsapp object and setup a connection.
$w = new WhatsProt($username, $identity, $nickname, $debug);
$w->connect();

// Now loginWithPassword function sends Nickname and (Available) Presence
$w->loginWithPassword($password);

echo "[*] Connected to WhatsApp\n\n";

//Retrieve large profile picture. Output is in /src/php/pictures/ (you need to bind a function
//to the event onProfilePicture so the script knows what to do.
$w->eventManager()->bind("onGetProfilePicture", "onGetProfilePicture");
$w->sendGetProfilePicture($target, true);

//Print when the user goes online/offline (you need to bind a function to the event onPressence
//so the script knows what to do)
$w->eventManager()->bind("onPresence", "onPresenceReceived");


//update your profile picture
$w->sendSetProfilePicture("/var/www/WhatsAPI/img/profile.jpg");

//send picture
//$w->sendMessageImage($target, "demo/x3.jpg");

//send video
//$w->sendMessageVideo($target, 'http://techslides.com/demos/sample-videos/small.mp4');

//send Audio
//$w->sendMessageAudio($target, 'http://www.kozco.com/tech/piano2.wav');

//send Location
//$w->sendLocation($target, '4.948568', '52.352957');

// Implemented out queue messages and auto msgid
//$w->sendMessage($target, "Guess the number :)");
//$w->sendMessage($target, "Sent from WhatsApi at " . date('H:i'));

while($w->pollMessage());

/**
 * You can create a ProcessNode class (or whatever name you want) that has a process($node) function
 * and pass it through setNewMessageBind, that way everytime the class receives a text message it will run
 * the process function to it.
 */
$pn = new ProcessNode($w, $target);
$w->setNewMessageBind($pn);

while (1) {
    $w->pollMessage();
    $msgs = $w->getMessages();
    foreach ($msgs as $m) {
    # process inbound messages
        //print($m->NodeString("") . "\n");
    }
}

/**
 * Demo class to show how you can process inbound messages
 */
class ProcessNode
{
    protected $wp = false;
    protected $target = false;

    public function __construct($wp, $target)
    {
        $this->wp = $wp;
        $this->target = $target;
    }

    /**
     * @param ProtocolNode $node
     */
    public function process($node)
    {
        $text = $node->getChild('body');
        $text = $text->getData();
	if ($text && ($text != "")) {
	$textnew = preg_split('/ /',  $text, -1, PREG_SPLIT_NO_EMPTY);
	}
        if ($text && ($text == "!flyer" || trim($text) == "!flyer")) {
	    $conn = mysql_connect("localhost","root","s3cr3tp@ss","eventos");
	    if (!$conn) {
	    	$this->wp->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
	    }
            $sql = mysql_query("SELECT * FROM whatsbot.eventos ORDER BY id DESC;");
            $info = mysql_fetch_array($sql);
            $this->wp->sendMessageImage($node->getAttribute("from"), $info['flyer']);
        }
        if ($text && ($text == "!evento" || trim($text) == "!evento")) {
	    $conn = mysql_connect("localhost","root","s3cr3tp@ss","eventos");
	    if (!$conn) {
	    	$this->wp->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
	    }
            $sql = mysql_query("SELECT * FROM whatsbot.eventos ORDER BY id DESC;");
            $info = mysql_fetch_array($sql);
            $this->wp->sendMessage($node->getAttribute("from"), "Proximo evento: ".$info['data']." - ".$info['evento']);
        }
        if ($text && ($text == "!local" || trim($text) == "!local")) {
	    $conn = mysql_connect("localhost","root","s3cr3tp@ss","eventos");
	    if (!$conn) {
	    	$this->wp->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
	    }
            $sql = mysql_query("SELECT * FROM whatsbot.eventos ORDER BY id DESC;");
            $info = mysql_fetch_array($sql);
            $this->wp->sendMessage($node->getAttribute("from"), "Local do evento: ".$info['local']);
            $this->wp->sendMessage($node->getAttribute("from"), "Endereco: ".$info['endereco']);
        }
        if ($text && ($text == "!data" || trim($text) == "!data")) {
	    $conn = mysql_connect("localhost","root","s3cr3tp@ss","eventos");
	    if (!$conn) {
	    	$this->wp->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
	    }
            $sql = mysql_query("SELECT * FROM whatsbot.eventos ORDER BY id DESC;");
            $info = mysql_fetch_array($sql);
            $this->wp->sendMessage($node->getAttribute("from"), "Data do evento: ".$info['data']);
        }
        if ($text && ($text == "!registros" || trim($text) == "!registros")) {
	    $conn = mysql_connect("localhost","root","s3cr3tp@ss");
	    if (!$conn) {
	    	$this->wp->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
	    }
            $sql = mysql_query("SELECT * FROM whatsbot.contato");
	    $sqlines = mysql_num_rows($sql);
	    $this->wp->sendMessage($this->target, "[Total de registros: ".$sqlines."]");
	    while ($info = mysql_fetch_array($sql)) {
	    	$this->wp->sendMessage($this->target, $info['nome']." - ".$info['telefone']);
	    }
        }
        if ($text && ($textnew[0] == "!add")) {
	    echo $textnew[0];
	    $telefoneNR = $textnew[1];
            $telefoneNOME = $textnew[2]." ".$textnew[3];
	    $conn = mysql_connect("localhost","root","s3cr3tp@ss");
	    if (!$conn) {
	    	$this->wp->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
	    }
            $sql = "INSERT INTO whatsbot.contato (`telefone`, `nome` , `registro`, `lastsend`) VALUES ('".$telefoneNR."', '".$telefoneNOME."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d")."');";
	    if (mysql_query($sql, $conn)) {
		$telefoneFULL = "55".$telefoneNR."@s.whatsapp.net";
	        $this->wp->sendMessage($telefoneFULL, "Telefone [".$telefoneNR."] Cadastrado com sucesso!");
	        $this->wp->sendMessage($telefoneFULL, "Para receber a lista de comandos digite: !comandos");
	        $this->wp->sendMessage($this->target, "[CADASTRO]: ".$telefoneNR." - ".$telefoneNOME);
	    } else {
		$telefoneFULL = "55".$telefoneNR."@s.whatsapp.net";
	        $this->wp->sendMessage($telefoneFULL, "[ERRO]: Nao foi possivel cadastrar seu numero!");
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: Gerado por: ".$telefoneNR);
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: ".mysql_error($conn));
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: Query: ".$sql);
	    }
        }
        if ($text && ($text == "!comandos" || trim($text) == "!comandos")) {
            $this->wp->sendMessage($node->getAttribute("from"), "[Lista de comandos]");
            $this->wp->sendMessage($node->getAttribute("from"), "!evento - Exibe o proximo evento");
            $this->wp->sendMessage($node->getAttribute("from"), "!flyer - Envia um flyer do proximo evento");
            $this->wp->sendMessage($node->getAttribute("from"), "!local - Exibe o local do proximo evento");
            $this->wp->sendMessage($node->getAttribute("from"), "!data - Exibe a data do proximo evento");
            $this->wp->sendMessage($node->getAttribute("from"), "!registro - Cadastro para receber noticias");
            $this->wp->sendMessage($node->getAttribute("from"), "!remover - Remove registro do sistema");
            $this->wp->sendMessage($node->getAttribute("from"), "!sobre - Informacoes sobre o autor");
        }
        if ($text && ($text == "!uptime" || trim($text) == "!uptime")) {
	    $loadresult = @exec('uptime');
            $this->wp->sendMessage($node->getAttribute("from"), "$loadresult");
        }
        if ($text && ($text == "!sobre" || trim($text) == "!sobre")) {
            $this->wp->sendMessage($node->getAttribute("from"), "Whatsapp Bot 0.1 desenvolvido por Matheus Nunes [9293858217]");
        }
        if ($text && ($text == "!remover" || trim($text) == "!remover")) {
	    $telefone1 = str_replace(array("@s.whatsapp.net","@g.us"), "", $node->getAttribute("from"));
            $telefone2 = substr($telefone1, 2, 12);
	    $conn = mysql_connect("localhost","root","s3cr3tp@ss","whatsbot");
	    if (!$conn) {
	    	$this->wp->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
	    }
            $sql = "DELETE FROM whatsbot.contato WHERE telefone=".$telefone2;
	    if (mysql_query($sql, $conn)) {
		$this->wp->sendMessage($this->target, "[CADASTRO]: ".$telefone2." - Removido do sistema!");
	        $this->wp->sendMessage($node->getAttribute("from"), "[REGISTRO] Seu numero foi removido do sistema!");
	    } else {
	        $this->wp->sendMessage($node->getAttribute("from"), "[ERRO]: Nao foi possivel remover seu numero!");
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: Gerado por: ".$telefone2);
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: ".mysql_error($conn));
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: Query: ".$sql);
	    }
        }
        if ($text && ($text == "!registro" || trim($text) == "!registro")) {
	    $telefone1 = str_replace(array("@s.whatsapp.net","@g.us"), "", $node->getAttribute("from"));
            $telefone2 = substr($telefone1, 2, 12);
	    $conn = mysql_connect("localhost","root","s3cr3tp@ss","whatsbot");
	    if (!$conn) {
	    	$this->wp->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
	    }
            $sql = "INSERT INTO whatsbot.contato (`telefone`, `nome` , `registro`, `lastsend`) VALUES ('".$telefone2."', '".$node->getAttribute("notify")."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d")."');";
	    if (mysql_query($sql, $conn)) {
	        $this->wp->sendMessage($node->getAttribute("from"), "Telefone [".$telefone2."] Cadastrado com sucesso!");
	        $this->wp->sendMessage($telefoneFULL, "Para receber a lista de comandos digite: !comandos");
		$this->wp->sendMessage($this->target, "[CADASTRO]: ".$telefone2." - ".$node->getAttribute("notify"));
	    } else {
	        $this->wp->sendMessage($node->getAttribute("from"), "[ERRO]: Nao foi possivel cadastrar seu numero!");
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: Gerado por: ".$telefone2);
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: ".mysql_error($conn));
		$this->wp->sendMessage($this->target, "[ERRO ".mysql_errno($conn)."]: Query: ".$sql);
	    }
        }
    }
} 
