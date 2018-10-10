<?php
namespace dokuwiki\plugin\tagadd;

use dokuwiki\Menu\Item\AbstractItem;

/**
 * 
 * @author peterfromearth
 * 
 * @package dokuwiki\plugin\tagadd
 *
 */
class MenuItem extends AbstractItem {
    protected $type = 'plugin_tagadd__addtags';
    protected $svg = __DIR__ . '/images/tagadd_new.svg';
    protected $method = 'post';
    
    public function getLabel() {
        return plugin_load('action', 'tagadd')->getLang('btn_addTagButton');
    }

}
