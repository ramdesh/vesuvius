<?
// PLUS SOAP Server

global $APPROOT;
global $server;
global $conf;

if(!isset($conf['enable_plus_web_services']) || (isset($conf['enable_plus_web_services']) &&  $conf['enable_plus_web_services'] == false)) {
	echo "web services disabled";
	die();
}

require_once($APPROOT."3rd/nusoap/lib/nusoap.php");

// init vars
$serviceName = "plusWebServices";
$ns          = "urn:".$serviceName;
$endpoint     = makeBaseUrl()."?wsdl";

$server = new nusoap_server;
$server->configureWSDL($serviceName, $ns, $endpoint, 'document');
$server->wsdl->schemaTargetNamespace = $ns;

require_once("api.inc");
shn_plus_register_all($ns);

//if in safe mode, raw post data not set:
if(!isset($HTTP_RAW_POST_DATA)) {
	$HTTP_RAW_POST_DATA = implode("\r\n", file('php://input'));
}

$server->service($HTTP_RAW_POST_DATA);


function makeBaseUrl() {
	if(isset($_SERVER['HTTPS'])) {
		$protocol = "https://";
	} else {
		$protocol = "http://";
	}
	$link = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
	$link = str_replace("index.php", "", $link);
	return $link;
}