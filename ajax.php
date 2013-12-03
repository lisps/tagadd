<?php
/**
 * DokuWiki Plugin TagAdd (AJAX Component) 
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author lisps
 */
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
require_once(DOKU_INC.'inc/init.php');

$Hajax = plugin_load('helper', 'ajaxedit');
$Htf = plugin_load('helper', 'tagfilter');

#Variables  
$action = $_POST['action'];

//Action Save Tags
if($action === 'saveTags') {
    $chked_tags=array();
    foreach($_POST["form"] as $entry){
        if($entry['name']=='tag')
            $chked_tags[]=$entry['value'];
    }
    sort($chked_tags);
    
    editWiki($chked_tags);
    
    $Htag=& plugin_load('helper', 'tag');
    $links=$Htag->tagLinks($chked_tags);
    p_set_metadata($ID,array('subject'=>$chked_tags));
    if(!$Htf->isNewTagVersion())
        $Htag->_updateTagIndex($ID,$chked_tags);
    

    $Hajax->success(array('links'=>$links));
    
}

//Action LoadForm
if($action === 'loadForm') {
    global $ID;
    $ns=trim($_POST['ns']);
    $form = createForm($ID,$ns);
    $Hajax->success(array('form'=>$form));
}

/*
 * Returns html breadcrumbs string for namspace selection
 * @param string $ID Id of the site
 * @return string html string
 */
function createNsSelector($ID,$ns_selected){
    //root namespace selector
    if($ns_selected==='' )
        $output="<a href='' onclick='tagadd__loadForm(\"\");return false;'><b>[:]</b></a>->";
    else 
        $output="<a href='' onclick='tagadd__loadForm(\"\");return false;'>[:]</a>->";
    $ns=explode(':',$ID);
    $anz_ns=count($ns);
    $root = '';
    
    foreach($ns as $key=>$part){
        //this is the site name
        if($key+1==$anz_ns) {
            $output.=$part;
        }
        else {
            $root .=$part.':';
            if($root == $ns_selected )
                $output.="<a href='' onclick='tagadd__loadForm(\"".$root."\");return false;'><b>[".$part."]</b></a>->";    
            else 
                $output.="<a href='' onclick='tagadd__loadForm(\"".$root."\");return false;'>[".$part."]</a>->";
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
function createForm($ID,$ns)
{
    
    $Htagfilter=& plugin_load('helper', 'tagfilter');
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
    $html =  createNsSelector($ID,$ns);
    
    if(count($nsTags)<1) return $html."<br><br>no Tags found";
    $form = new Doku_Form('tagadd__form');
    $form->_content[]='<div id="tagadd__accordion" height="800px">';

    createAccordion($form,$nsTags,$siteTags);
    
    $html .= $form->getForm();
    return $html;//Form Ausgeben
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
function categorysizeTags($tags)
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
function createAccordion($form,$nsTags,$siteTags)
{
    $nsTags_cat = categorysizeTags($nsTags);
    $siteTags_cat=categorysizeTags($siteTags);
    
    foreach($nsTags_cat as $category=>$Tags) {
        $form->_content[]='<h3><a href="#">'.$category.' ('.count($siteTags_cat[$category]).'/'.count($Tags).')</a></h3><div>';
        foreach($Tags as $Tag){
            $chk_attrs=array();
            
            if(in_array($Tag,$siteTags)){
                $chk_attrs['checked']='checked';
            }
            $form->addElement(form_makeCheckboxField('tag', $value=$Tag, $label=$Tag, $id='ad_'.$Tag, $class='tagadd', $chk_attrs));

        }
        $form->_content[]='</div>';
    }
    $form->_content[]='</div>';
}

/**
 * save the tags to the raw wiki page
 * @tags array tags
 */
function editWiki($tags) {
    global $Hajax;
    $idcount = 0;
    $result['error'] = false;
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
    if(!empty($add))
        $log.=' Added: '.implode(",",$add);
    if(!empty($rem))
        $log.=' Removed: '.implode(",",$rem);
            
    $summary= "Tag".$idcount." ".$log;
    $Hajax->saveWikiPage($data,$summary,true,array(),false);

}

