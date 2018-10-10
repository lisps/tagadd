<?php
/**
 * DokuWiki Plugin TagAdd (Action Component) 
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author lisps
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class action_plugin_tagadd extends DokuWiki_Action_Plugin
{

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER',  $this, '_addjs');
        if($this->getConf('showPagetoolBtn')) {
    		$controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, '_addbutton');
    		$controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addsvgbutton');
        }
    }
    
    function _addjs(Doku_Event $event, $param) {
        global $ID;
        global $JSINFO;
        global $ACT;
		
		$perm = auth_quickaclcheck($ID);
		if ($perm > AUTH_READ)
			$JSINFO['acl_write'] = '1';
        $JSINFO['currentNamespace'] = (($ns = getNS($ID))?$ns:'');
        
        if(!isset($JSINFO['act'])) {
            $JSINFO['act'] = $ACT;
        }
        
        $JSINFO['tagadd_altKey'] = $this->getConf('altKey');
        $JSINFO['tagadd_ctrlKey'] = $this->getConf('ctrlKey');
        $JSINFO['tagadd_keyCode'] = array_map('trim',explode(',',$this->getConf('keyCode')));
        
    }
    
    function _addbutton(Doku_Event $event) {
		global $ID;

        $perm = auth_quickaclcheck($ID);
		if ($perm > AUTH_READ) {
			$event->data['items'][] = '<li class="plugin_tagadd__addtags">' . tpl_link(wl($ID), '<span>'.$this->getLang('btn_addTagButton').'</span>',
												'class="action tagadd" title="'.$this->getLang('btn_addTagButton').'"', 1) . '</li>';
		}
	}
	

	public function addsvgbutton(Doku_Event $event) {
	    if($event->data['view'] != 'page') return;
	    $event->data['items'][] = new \dokuwiki\plugin\tagadd\MenuItem();
	}
	
	

}

