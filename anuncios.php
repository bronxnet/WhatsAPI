<?php
set_time_limit(600);
require_once '/var/www/WhatsAPI/src/whatsprot.class.php';
date_default_timezone_set('America/Manaus');

$username = "";
$password = "";
$identity = "CauxiEventos";
$nickname = "Cauxi01";
$target = "559293858217-1427915902";
$debug = false;


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

$conn = mysql_connect("localhost","root","s3cr3tp@ss");
if (!$conn) {
    $w->sendMessage($this->target, "Connection failed: " . mysql_connect_error());
}
$sql = mysql_query("SELECT * FROM whatsbot.contato;");
$sqlevento = mysql_query("SELECT * FROM whatsbot.eventos ORDER BY id DESC;");
$eventoinf = mysql_fetch_array($sqlevento);
while ($info = mysql_fetch_array($sql)){
    if ($info['lastsend'] <= date("Y-m-d")) {
	    echo "Enviando propaganda para: ".$info['nome']."\n\n";
	    $nrnovo = "55".$info['telefone']."@s.whatsapp.net";
	    $w->sendMessage($nrnovo, "Ola, ".$info['nome']);
	    $w->sendMessage($nrnovo, "No dia: [".$eventoinf['data']."] teremos [".$eventoinf['evento']."]");
	    $w->sendMessage($nrnovo, "Local do evento: ".$eventoinf['local']);
	    $w->sendMessage($nrnovo, "Endereco: ".$eventoinf['endereco']);
	    $w->sendMessageImage($nrnovo, $eventoinf['flyer']);
    } else {
	echo $info['nome']."- Ja recebeu essa propaganda hoje. \n\n"
    }
}

while($w->pollMessage());

/**
 * You can create a ProcessNode class (or whatever name you want) that has a process($node) function
 * and pass it through setNewMessageBind, that way everytime the class receives a text message it will run
 * the process function to it.
 */
$pn = new ProcessNode($w, $target);
$w->setNewMessageBind($pn);


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
    }
} 
