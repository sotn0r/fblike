<?php

/* FBLike v0.9.1
 *
 * Plugin by sotn0r.nc1.eu
 * This plugin is intended for Maniaplanet servers with XASECO2.
 *
 * Settings are configurable in the fblike.xml file.
 *
 * This plugin uses the Facebook Graph API.
 */

Aseco::registerEvent('onStartup',                   'fbl_setup');
Aseco::registerEvent('onSync',                      'fbl_sync');
Aseco::registerEvent('onPlayerConnect',             'fbl_player');

if ( defined('XASECO2_VERSION') )
    Aseco::registerEvent('onBeginMap',                  'fbl_check');
else if ( defined('XASECO_VERSION') )
    Aseco::registerEvent('onBeginRace',                  'fbl_check');


Aseco::registerEvent('onEndRound',					'fbl_off');
Aseco::registerEvent('onShutdown',					'fbl_shutdown');

Aseco::addChatCommand('fblreload', '/fblreload');

$json = null;

function fbl_sync($aseco) {
    global $fbl;

    $aseco->plugin_versions[] = array(
        'plugin'   => 'plugin.fblike.php',
        'author'   => 'sotn0r.nc1.eu',
        'version'   => '0.9.3'
    );

}

function fbl_setup($aseco) { //Read fblike.xml and load settings
    global $fbl, $json;

    $fbl = array();

    if ($config = $aseco->xml_parser->parseXml('fblike.xml', true)) {

        $config = $config['SETTINGS'];

        if (strtolower($config['ENABLED'][0]) == 'true') {
            $fbl['active'] = true;
            $fbl['position'] = floatval($config['POSX'][0]).' '.floatval($config['POSY'][0]).' 1';
            $fbl['facebook_id'] = $config['FACEBOOK_ID'][0];
            $fbl['icon_url'] = $config['ICON_URL'][0];
        } else {
            $fbl['active'] = false;
        }

        $fbl['manialink'] = '6687413';

        $aseco->console('[FBLike] setup completed.');

        $json = json_decode(file_get_contents("http://graph.facebook.com/".$fbl['facebook_id']));

    } else {
        trigger_error('[FBLikes] Could not read/parse settings file fblike.xml!', E_USER_ERROR);
        return false;
    }
}

function fbl_check($aseco) { //Check map and load widgets
    global $fbl, $json;

    if ( $fbl['active'] )
    {
        $json = json_decode(file_get_contents("http://graph.facebook.com/".$fbl['facebook_id']));

        if ( isset($json->error) )
        {
            $json->likes = "--";
            $json->link = "http://facebook.com/nc1eu";

            $aseco->console('[FBLike] Facebook Graph error: ' . $json->error->message);
        }

        fbl_buildWidget($aseco, null);
    }
}

function fbl_player($aseco, $player) { //Check map and load widgets
    global $fbl;

    if ( $fbl['active'] )
    {
        fbl_buildWidget($aseco, $player);
    }
}


function fbl_off($aseco) {
    global $fbl;

    $xml = '<manialink id="'.$fbl['manialink'].'00">
	</manialink> <manialink id="'.$fbl['manialink'].'01">
	</manialink>';

    $aseco->client->addCall('SendDisplayManialinkPage', array($xml, 0, false));
}

function fbl_buildWidget($aseco, $player = null) {
    global $fbl, $json;

    if ( defined('XASECO_VERSION') )
        $json->link = str_replace("https://", "http://", $json->link);


    $xml = '<manialink id="'.$fbl['manialink'].'00">
  	  <frame posn="'.$fbl['position'].'">
  	    <quad posn="0 0 0" sizen="4.6 6.5" style="BgsPlayerCard" substyle="BgCardSystem" url="'.$json->link.'" />
        <quad posn="1.0 -0.44 0.1" sizen="2.5 2.78" image="'.$fbl['icon_url'].'"></quad>
   	    <label posn="2.25 -3.48 0.1" sizen="5 2" halign="center"  textsize="1" scale="1" textcolor="FFFF" text="'.$json->likes.'"/>
   	    <label posn="2.25 -4.87 0.1" sizen="8.4 2" halign="center"  textsize="1" scale="0.6" textcolor="FC0F" text="LIKES"/>
   	    <quad posn="-0.18 -4.6 0.002" sizen="2.1 2.1" image="http://tmserver.eu/i/edge-open-ld-light.png"/>
   	 </frame>
		</manialink>';

    if ( $player == null )
        $aseco->client->addCall('SendDisplayManialinkPage', array($xml, 0, false));
    else
        $aseco->client->addCall('SendDisplayManialinkPageToLogin', array($player->login, $xml, 0, false));
}


function fbl_shutdown($aseco) {
    global $fbl;
    fbl_off($aseco);

}


function chat_fblreload ($aseco, $command)
{


    if (!$aseco->isMasterAdmin($command['author']))
        return;


    $aseco->console('[FBLike] Reloading config');

    fbl_off($aseco);
    fbl_setup($aseco);
    fbl_check($aseco);


}


?>
