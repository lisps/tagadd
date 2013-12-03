/**
 * DokuWiki Plugin TagAdd (JavaScript Component) 
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author lisps
 */

/**
 * Set when a request is active
 **/
var TAGADD__loadActive = false;

/**
 * Submit the selected Tags
 **/
function tagadd__ajax_submitFormTags()
{
      ajaxedit_send('tagadd',false,tagadd__submitFormTagsDone,{
          form:jQuery('#tagadd__form').serializeArray(),
          action:'saveTags',
      });
}

/**
 * Callback from tagadd__ajax_submitFormTags
 * check ajax response and close dialog
 *
 * @param json data jQuery ajax requested data
 **/
function tagadd__submitFormTagsDone(data){
    ret = ajaxedit_parse(data);
    if(ajaxedit_checkResponse(ret)) {
        jQuery("#tagadd__dialog").dialog("close");
        if(jQuery("div.tags span").length){
            jQuery("div.tags span").html(ret.links);
        }
        else {
            jQuery('div.page').append('<div class="tags"><span>'+ret.links+'</span></div>');
        }
    }
}

/**
 * Create dialog if not exists and request the tags for the given namespace
 *
 * @param string ns namespace 
 **/
function tagadd__loadForm(ns){
    if (TAGADD__loadActive) return ;
    TAGADD__loadActive = true;

    if(!jQuery('#tagadd__dialog').length){
        jQuery('body').append('<div id="tagadd__dialog" position="absolute" border=1 height="800px"><div id="tagadd__dialog_div"></div></div>');
        jQuery( "#tagadd__dialog" ).dialog({title:LANG.plugins.tagadd['choose tags'],
            height:600,
            width: Math.min(700,jQuery(window).width()-50),
            autoOpen:true,
            buttons:[
                {text:LANG.plugins.tagadd['closeDialog'],click: function() {jQuery(this).dialog('close');}},
                {text:LANG.plugins.tagadd['save'],click: function() {tagadd__ajax_submitFormTags();}},
                ],
            });
    }
    jQuery('#tagadd__dialog').addClass('loading');
    ajaxedit_send('tagadd',false,tagadd__submitLoadFormDone,{
        action:'loadForm',
        ns:ns,
        from:jQuery('#tagadd__form').serializeArray(),
    });

}

/**
 * Callback from tagadd__loadForm
 * opens dialog
 * @param json data jquery data
 **/
function tagadd__submitLoadFormDone(data){
    ret = ajaxedit_parse(data);
    if(ajaxedit_checkResponse(ret)) {
        jQuery("#tagadd__dialog_div").empty();
        jQuery("#tagadd__dialog_div").html(ret.form);
    
        jQuery("#tagadd__dialog").dialog("open");
        jQuery("#tagadd__accordion").accordion({heightStyle: 'content',collapsible:true});
        //jQuery("#tagadd__accordion").accordion('activate',false);
    }
    TAGADD__loadActive = false;
    jQuery('#tagadd__dialog').removeClass('loading');
}

//add Shortcut
jQuery(document).ready(function() {
    if(JSINFO && JSINFO['act'] === 'show') {
        jQuery(document).keypress(function(e) {
            if (e.ctrlKey && e.which == 113) {
              tagadd__loadForm(JSINFO['currentNamespace']);
            }
        });
    }
});
