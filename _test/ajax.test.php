<?php

namespace dokuwiki\plugin\tagadd\test;

use DokuWikiTest;
use TestRequest;


/**
 * @group plugin_tagadd
 * @group plugins
 */
class plugin_tagadd_ajax_test extends DokuWikiTest {

    public function setup() {
        $this->pluginsEnabled[] = 'tagadd';
        $this->pluginsEnabled[] = 'ajaxedit';
        $this->pluginsEnabled[] = 'tag';
        $this->pluginsEnabled[] = 'tagfilter';
        parent::setup();
    }

    
    public function test_basic_syntax() {
        saveWikiText('test:plugin_tagadd',"Test\n{{tag>Tag1 Tag2}}",'test');
        
        $data = rawWiki('test:plugin_tagadd');
        $this->assertContains('{{tag>Tag1 Tag2}}', $data);

        $request = new TestRequest();
        $request->post([
            'call'   => 'plugin_tagadd', 
            'action' => 'saveTags',
            'pageid' => 'test:plugin_tagadd',
            'form' => [
                ['name' => 'tag', 'value' => 'Tag1'],
                ['name' => 'tag', 'value' => 'Tag2'],
                ['name' => 'tag', 'value' => 'Tag3'],
            ],
            
            'lastmod' => @filemtime(wikiFN('test:plugin_tagadd')),
            
        ], '/lib/exe/ajax.php');
               
        $data = rawWiki('test:plugin_tagadd');
        $this->assertContains('{{tag> Tag1 Tag2 Tag3 }}', $data);
        
        $request = new TestRequest();
        $request->post([
            'call'   => 'plugin_tagadd',
            'action' => 'saveTags',
            'pageid' => 'test:plugin_tagadd',
            'form' => [
                ['name' => 'tag', 'value' => 'Tag1'],
            ],
            
            'lastmod' => @filemtime(wikiFN('test:plugin_tagadd')),
            
        ], '/lib/exe/ajax.php');
        
        
        $data = rawWiki('test:plugin_tagadd');
        $this->assertContains('{{tag> Tag1 }}', $data);
    }
}
