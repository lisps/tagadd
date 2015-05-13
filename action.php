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

    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER',  $this, '_addjs');
		$controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, '_addbutton');
    }
    
    function _addjs(&$event, $param) {
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
    
    function _addbutton(&$event) {
		global $ID;
		
        $perm = auth_quickaclcheck($ID);
		if ($perm > AUTH_READ) {
			$event->data['items'][] = '<li>' . tpl_link(wl($ID), '<span>'.$this->getLang('addTagButton').'</span>',
												'class="action tagadd" title="'.$this->getLang('addTagButton').'"', 1) . '</li>';
		}
	}

}
