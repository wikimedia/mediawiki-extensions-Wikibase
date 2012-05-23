<?php

/**
 * Test WikibaseItemView.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * The tests are using "medium" so they are able to run alittle longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
 *
 * The tests are doing some assumptions on the id numbers. If the database isn't empty when
 * when its filled with test items the ids will most likely get out of sync and the tests will
 * fail. It seems impossible to store the item ids back somehow and at the same time not being
 * dependant on some magically correct solution. That is we could use GetItemId but then we
 * would imply that this module in fact is correct.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItemView
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 * 
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class WikibaseItemViewTests extends MediaWikiTestCase {
	
	protected static $num = -1;

	/**
	 * This is to set up the environment.
	 */
	public function setUp() {
		parent::setUp();
	}
	
	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetHTML
	 */
	public function testGetHTML( $arr, $expected)
    {
    	self::$num++;
    	
    	$item = $arr === false ? WikibaseItem::newEmpty() : WikibaseItem::newFromArray( $arr );
    	$item->setLabel('de', 'Stockholm');
        $this->assertTrue(
        	$item != null && $item !== false,
        	"Could not find an item" );
        
		$view = new WikibaseItemView( $item );
        $this->assertTrue(
        	$view != null && $view !== false,
        	"Could not find a view" );
        
        $html = $view->getHTML();
        
        if ( is_string($expected) ) {
	        $this->assertRegExp(
	        	$expected,
	        	$html,
	        	"Could not find the marker '{$expected}'" );
        }
        else {
        	foreach ($expected as $that) {
	        $this->assertRegExp(
	        	$that,
	        	$html,
	        	"Could not find the marker '{$that}'" );
        		
        	}
        }
        
    }
    
    public function providerGetHTML() {
    	return array(
    		array(
    			false,
    			'/"wb-sitelinks-empty"/'
    		),
    		array(
    			array(
    				'links'=> array(
    					array( 'site' => 'en', 'title' => 'Oslo')
    				)
    			),
    			array(
    				'/"wb-sitelinks"/',
    				'/"wb-sitelinks-0 uneven"/',
    				'/<a>\s*Oslo\s*<\/a>/'
    			)
    		),
    		array(
    			array(
    				'links'=> array(
    					array( 'site' => 'de', 'title' => 'Stockholm'),
    					array( 'site' => 'en', 'title' => 'Oslo'),
    				)
    			),
    			array(
    				'/"wb-sitelinks"/',
    				'/"wb-sitelinks-0 uneven"/',
    				'/"wb-sitelinks-1 even"/',
    				'/<a>\s*Oslo\s*<\/a>/',
    				'/<a>\s*Stockholm\s*<\/a>/'
    			)
    		),
    		array(
    			array(
    				'description'=> array( 'en' => array( 'language' => 'en', 'value' => 'Capitol of Norway') ),
    				'links'=> array( array( 'site' => 'en', 'title' => 'Oslo') )
    			),
    			array(
    				'/"wb-sitelinks"/',
    				'/<span class="wb-property-container-value">\s*Capitol of Norway\s*<\/span>/',
    				'/<a>\s*Oslo\s*<\/a>/'
    			)
    		),
    	);
    }
}
