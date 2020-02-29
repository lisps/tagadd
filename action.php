<?php
/**
 * DokuWiki Plugin TagAdd (Action Component) 
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author lisps
 */
 

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class action_plugin_tagadd extends DokuWiki_Action_Plugin
{

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER',  $this, '_addjs');
        if($this->getConf('showPagetoolBtn')) {
    		$controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, '_addbutton');
    		$controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addsvgbutton');
        }
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE',  $this, '_ajax_call');
    }
    
    public function _addjs(Doku_Event $event, $param) {
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
    
    public function _addbutton(Doku_Event $event) {
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
	
	
	public function _ajax_call(Doku_Event $event, $param) {
	    if ($event->data !== 'plugin_tagadd') {
	        return;
	    }
	    //no other ajax call handlers needed
	    $event->stopPropagation();
	    $event->preventDefault();
	    
	    /* @var $INPUT \Input */
	    global $INPUT;
	    global $ID;
	    
	    #Variables
	    $action = $INPUT->str('action');
	    
	    
	    /* @var $Hajax \helper_plugin_ajaxedit */
	    $Hajax = $this->loadHelper('ajaxedit');
	    
	    $Htf = $this->loadhelper('tagfilter');
	    
	    //Action Save Tags
	    if($action === 'saveTags') {
	        $chked_tags=array();
	        foreach($INPUT->arr('form', array()) as $entry){
	            if($entry['name']=='tag')
	                $chked_tags[]=$entry['value'];
	        }
	        sort($chked_tags);
	        
	        $this->editWiki($chked_tags);
	        
	        $Htag= $this->loadHelper('tag');
	        
	        $links=$Htag->tagLinks($chked_tags);
	        p_set_metadata($ID,array('subject'=>$chked_tags));
	        if(!$Htf->isNewTagVersion()) {
	            $Htag->_updateTagIndex($ID,$chked_tags);
	        }
	        
	        $Hajax->success(array('links'=>$links));
	        
	    }
	    
	    //Action LoadForm
	    if($action === 'loadForm') {
	        global $INPUT;
	        $ns = $INPUT->str('ns');
	        
	        $form = $this->createForm($ID, $ns);
	        $Hajax->success(['form' => $form]);
	    }
	}
	
	/*
	 * Returns html breadcrumbs string for namspace selection
	 * @param string $ID Id of the site
	 * @return string html string
	 */
	protected function createNsSelector($ID, $ns_selected){
	    //root namespace selector
	    if($ns_selected === '' ) {
	        $output="<a href='' onclick='tagadd__loadForm(\"\");return false;'><b>[:]</b></a>->";
	    } else {
	        $output="<a href='' onclick='tagadd__loadForm(\"\");return false;'>[:]</a>->";
	    }
	    $ns=explode(':',$ID);
        $anz_ns=count($ns);
        $root = '';
        
        foreach($ns as $key => $part){
            //this is the site name
            if($key+1==$anz_ns) {
                $output.=$part;
            } else {
                $root .=$part.':';
            
                if($root == $ns_selected ) {
                    $output.="<a href='' onclick='tagadd__loadForm(\"".$root."\");return false;'><b>[".$part."]</b></a>->";
                } else {
                    $output.="<a href='' onclick='tagadd__loadForm(\"".$root."\");return false;'>[".$part."]</a>->";
                }
            }
        }
        return $output;
	}
	
	
	/**
	 * Returns html string with the accordion to select the tags
	 * loads the tags from the given site and the tags of the given namespace
	 *
	 * @param string $ID site id
	 * @param string $ns namespace from which the tags should be loaded
	 */
	protected function createForm($ID,$ns)
	{
	    
	    $Htagfilter= $this->loadHelper('tagfilter');
	    $siteTags = $Htagfilter->getTagsBySiteID($ID);
	    $nsTags   = $Htagfilter->getTagsByNamespace(trim($ns,':'));
	    
	    sort($siteTags);
	    sort($nsTags);
	    //print_r($nsTags);
	    //workaround for empty entries in the arrays
	    $siteTags=array_filter($siteTags);
	    $nsTags=array_filter($nsTags);
	    /*
	     echo '<pre>';
	     //print_r($nsTags);
	     print_r($siteTags);
	     echo '</pre>';
	     */
	    $html =  $this->createNsSelector($ID, $ns);
	    
	    if(count($nsTags)<1) return $html."<br><br>no Tags found";
	    $form = new Doku_Form('tagadd__form');
	    $form->_content[]='<div id="tagadd__accordion" height="800px">';
	    
	    $this->createAccordion($form,$nsTags,$siteTags);
	    
	    $html .= $form->getForm();
	    return $html;
	}
	
	/**
	 * Categorysize Tags by the first part before a ':'
	 * @param array $tags Array of tags
	 * <pre>
	 * array('category1:tag1','category1:tag2','category2:tag1','category2:tag2')
	 * </pre>
	 * @returns array multidimensional array
	 * <pre>
	 * [category1] => 'category1:tag1'
	 *             => 'category1:tag2'
	 * [category2] => 'category2:tag1'
	 *             => 'category2:tag2'
	 * </pre>
	 */
	protected function categorysizeTags($tags)
	{
	    $catTags = array();
	    foreach($tags as $nsTag){
	        $category=substr($nsTag,0,strpos($nsTag,':'));
	        $catTags[$category][]=$nsTag;
	    }
	    ksort($catTags);
	    return $catTags;
	}
	
	/**
	 * creates the accordion with the checkbox fields
	 * @param Doku_Form $form doku form instance
	 * @param array $nsTags Selectable tags
	 * @param array $siteTags Checked tags
	 * @return string html code
	 */
	protected function createAccordion($form,$nsTags,$siteTags)
	{
	    $nsTags_cat = $this->categorysizeTags($nsTags);
	    $siteTags_cat = $this->categorysizeTags($siteTags);
	    
	    foreach($nsTags_cat as $category=>$tags) {
	        $catTagsCount = array_key_exists($category, $siteTags_cat) ? count($siteTags_cat[$category]) : '0';
	        $form->_content[]='<h3><a href="#">'.$category.' ('.$catTagsCount.'/'.count($tags).')</a></h3><div>';
	        foreach($tags as $tag){
	            $chk_attrs=array();
	            
	            if(in_array($tag,$siteTags)){
	                $chk_attrs['checked']='checked';
	            }
	            $form->addElement(form_makeCheckboxField('tag', $tag, $tag, 'ad_'.$tag, 'tagadd', $chk_attrs));
	            
	        }
	        $form->_content[]='</div>';
	    }
	    $form->_content[]='</div>';
	}
	
	/**
	 * save the tags to the raw wiki page
	 * @tags array tags
	 */
	protected function editWiki($tags) {
	    $Hajax = $this->loadHelper('ajaxedit');
	    $idcount = 0;

	    $data=$Hajax->getWikiPage();
	    //find "our" fsinput fields
	    $found=explode("{{tag>",$data);
	    
	    if ($idcount < count($found) && count($found)>1) {
	        
	        $found[$idcount+1] = ltrim($found[$idcount+1]);
	        $stop=strpos($found[$idcount+1],"}}");
	        if ($stop === FALSE) {
	            $Hajax->error('cant find object');
	        }
	        else {
	            $oldstr = substr($found[$idcount+1],0,$stop);
	            $newstr=implode(" ",$tags);
	            if($stop == 0)
	                $found[$idcount+1]= " ".$newstr." ".$found[$idcount+1];
	                else
	                    $found[$idcount+1]=str_replace($oldstr," ".$newstr." ",$found[$idcount+1]);
	        }
	        //create new pagesource
	        $data=implode("{{tag>",$found);
	        
	        //get removed and added tags
	        $oldtags_r=explode(" ",$oldstr);
	        $oldtags_r = array_filter($oldtags_r);
	        $diff =array_diff($oldtags_r,$tags);
	        $rem = array_intersect($oldtags_r,$diff);
	        $diff =array_diff($tags,$oldtags_r);
	        $add = array_intersect($tags,$diff);
	    } else if(!empty($tags)){
	        $text = '{{tag>' . implode(" ",$tags) .'}}';
	        $data.=DOKU_LF.$text;
	        
	        $rem = array();
	        $add = $tags;
	    }
	    else{
	        $Hajax->error('no tags selected');
	    }
	    
	    
	    $log = "";
	    if(!empty($add)) {
	        $log.=' Added: '.implode(",",$add);
	    }
	    if(!empty($rem)) {
            $log.=' Removed: '.implode(",",$rem);
	    }
	            
        $summary= "Tag".$idcount." ".$log;
        $Hajax->saveWikiPage($data,$summary,true, [],false);
	            
	}
	

}

