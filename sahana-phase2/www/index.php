<?php 
/**
* Sahana front controller, through which all actions are dispatched
*
* PHP version 4 and 5
*
* LICENSE: This source file is subject to LGPL license
* that is available through the world-wide-web at the following URI:
* http://www.gnu.org/copyleft/lesser.html
*
* @package    Sahana - http://sahana.sourceforge.net
* @author     http://www.linux.lk/~chamindra
* @copyright  Lanka Software Foundation - http://www.opensource.lk
*/

// Specify the base location of the Sahana insallation
// The base should not be exposed to the web for security reasons
// only the www sub directory should be exposed to the web
$APPROOT = realpath(dirname(__FILE__)).'/../';

// define global $APPROOT for convenience and efficiency;
$global['approot'] = $APPROOT;
$global['previous'] = false;

// Include error handling routines
require_once($global['approot'].'inc/lib_errors.inc');

// handle error reporting seperately and set our own error handler
if (true) { // set to false if you want to develop without the custom error handler
    error_reporting(0);
    set_error_handler('shn_sahana_error_handler');
}

// include the base libraries for both the web installer and main app 
// require_once ($APPROOT.'inc/handler_error.inc');
require_once ($APPROOT.'inc/lib_config.inc');
require_once ($APPROOT.'inc/lib_modules.inc'); 

// === filter the GET and POST ===
shn_main_filter_getpost();

// === Setup if not setup and load configuration === 

// if installed the sysconf.inc will exist in the conf directory
// if not start the web installer
if (!file_exists($APPROOT.'conf/sysconf.inc')){

    // Call the web installer 
    shn_main_web_installer();

} else {

    // define the configuration priority order
    require_once ($APPROOT.'conf/conf-order.inc');

    // include the main sysconf file
    require ($APPROOT.'conf/sysconf.inc'); 

    // include the main libraries the system depends on
    require_once ($APPROOT.'inc/handler_db.inc');
    require_once ($APPROOT.'inc/lib_session/handler_session.inc');
    require_once ($global['approot'].'inc/lib_security/handler_openid.inc');
    require_once ($APPROOT.'inc/lib_security/lib_auth.inc');
 	require_once ($APPROOT.'inc/lib_security/constants.inc');
    require_once ($APPROOT.'inc/lib_locale/handler_locale.inc'); 

    //include the user preferences
    include_once ($APPROOT.'inc/lib_user_pref.inc');
    shn_user_pref_populate();

    // load all the configurations based on the priority specified 
    // files and database, base and mods
    shn_config_load_in_order();

    // start the front controller pattern
    shn_main_front_controller();
}

// === process the GET and POST ===
function shn_main_filter_getpost()
{
    global $global;

    if(!$global['previous']){
        $global['action'] = (NULL == $_REQUEST['act']) ? 
                                "default" : $_REQUEST['act'];
        $global['module'] = (NULL == $_REQUEST['mod']) ? 
                                "home" : $_REQUEST['mod'];
    }
}

// === front controller ===
function shn_main_front_controller() 
{
    global $global, $APPROOT, $conf;
    $action = $global['action'];
    $module = $global['module'];

    // define which stream library to use base on POST "stream" 
    if(isset($_REQUEST['stream']) && file_exists($APPROOT."/inc/lib_stream_{$_REQUEST['stream']}.inc")){

        require_once ($APPROOT."/inc/lib_stream_{$_REQUEST['stream']}.inc");
        $stream_ = $_REQUEST['stream']."_";

    } else {
    // default to the HTML stream
        require_once $APPROOT."/inc/lib_stream_html.inc";
        $stream_ = null;
    }

    // Redirect the module based on the action performed
    // redirect admin functions through the admin module
    if (preg_match('/^adm/',$action)) {

        $global['effective_module'] = $module = 'admin';
        $global['effective_action'] = $action = 'modadmin';
    } // the orignal module and action is stored in $global

    // This is a redirect for the report action
    if (preg_match('/^rpt/',$action)) {

        $global['effective_module'] = $module = 'rs';
        $global['effective_action'] = $action = 'modreports';
    }
  
    // check the users access permissions for this action
    $module_function = 'shn_'.$stream_.$module.'_'.$action;
	
    // include the correct module file based on action and module
    $module_file = $APPROOT.'mod/'.$module.'/main.inc';

    // default to the home page if the module main does not exist
    if (file_exists($module_file)) {
        include($module_file); 
    } else {
        include($APPROOT.'mod/home/main.inc');
    }

    // stream (XHTML, XML, TEXT, etc) initialization
    // this includes the inclusion of various sections in XHTML including the HTTP header,
    // content header, menubar, login
    shn_stream_init();

        // compose and call the relevant module function 
        if (!function_exists($module_function)) {
            $module_function='shn_'.$stream_.$module.'_default';
        }

        $_SESSION['last_module']=$module;
        $_SESSION['last_action']=$action;
        $module_function(); 

    // close up the stream. In HTML send the footer
    shn_stream_close();
}

// Call the web installer 
function shn_main_web_installer()
{
    global $global, $APPROOT, $conf;

    // The 'help' action is a special case. The following allows the popup help text
    // to be accessed before the sysconf.inc file has been created.
    if ($global['action'] == "help"){
        require_once ($APPROOT."/inc/lib_stream_{$_REQUEST['stream']}.inc");

        $module = $global['module'];

        $module_file = $approot.'mod/'.$module.'/main.inc';

        include($module_file);

        shn_stream_init();

        $module_function='shn_'.$_REQUEST['stream'].'_'.$module.'_'.$global['action'];

        $_SESSION['last_module']=$module;
        $_SESSION['last_action']=$action; 

        $module_function();

        shn_stream_close();
    }
    else{ 
        // include the sysconfig template for basic conf dependancies
        require_once ($APPROOT.'conf/sysconf.inc.tpl'); 

        // launch the web setup wizard
        require ($APPROOT.'inst/setup.inc');
    }
}
