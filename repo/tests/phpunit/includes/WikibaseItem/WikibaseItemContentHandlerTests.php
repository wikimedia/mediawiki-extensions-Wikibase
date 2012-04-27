<?php

/**
 * Tests for the WikibaseItem class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * 
 */
class WikibaseItemContentHandlerTests extends MediaWikiTestCase {
	
	/**
	 * Enter description here ...
	 * @var WikibaseContentHandler
	 */
	protected $ch;
	
	/**
	 * Enter description here ...
	 * @var WikibaseItem
	 */
	protected $item;
	
	/**
	 * This is to set up the environment
	 */
	public function setUp() {
  		parent::setUp();
		$this->ch = new WikibaseContentHandler();
	}
	
  	/**
	 * This is to tear down the environment
	 */
	public function tearDown() {
		parent::tearDown();
	}
	
	/**
	 * This is to make sure the unserializeContent method work approx as it should with the provided data
	 * @dataProvider provideBasicData
	 */
	public function testUnserializeContent( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->item = $this->ch->unserializeContent( $input,'application/json' );
		$this->assertInstanceOf(
			'WikibaseItem',
			$this->item,
			'Calling unserializeContent on a WikibaseContentHandler should return a WikibaseItem'
		);
	}

	/**
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testGetLabels( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->item = $this->ch->unserializeContent( $input,'application/json' );
		$this->assertEquals(
			$labels,
			$this->item->getLabels( $languages ),
			'Testing getLabels on a new WikibaseItem after creating it with preset values and doing a unserializeContent'
		);
	}

	/**
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testGetDescriptions( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->item = $this->ch->unserializeContent( $input,'application/json' );
		$this->assertEquals(
			$descriptions,
			$this->item->getDescriptions( $languages ),
			'Testing getDescriptions on a new WikibaseItem after creating it with preset values and doing a unserializeContent'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::copy
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testCopy( $input ) {
		$this->item = $this->ch->unserializeContent( $input,'application/json' );
		$copy = $this->item->copy();
		$this->assertInstanceOf(
			'WikibaseItem',
			$copy,
			'Calling copy on the return value of WikibaseItem::unserializeContent() should still return a new WikibaseItem object'
		);
		$this->assertEquals(
			$copy,
			$this->item,
			'Calling copy() on an item built by unserializeContent should return a similar object'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::cleanStructure
	 * This uses a rather strange name as it does not _clean_ the structure but _constructs_ missing elements
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testCleanStructure( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->item = $this->ch->unserializeContent( $input,'application/json' );
		$this->item->cleanStructure();
		$this->assertInstanceOf(
			'WikibaseItem',
			$this->item,
			'Calling cleanStructure should still leave the item as a WikibaseItem object'
		);
		$this->assertInternalType(
			'array',
			$this->item->getLabels(),
			'Checking if the expected structure is the type for returned labels for a cleaned WikibaseItem'
		);
		$this->assertInternalType(
			'array',
			$this->item->getdescriptions(),
			'Checking if the expected structure is the type returned for descriptions for a cleaned WikibaseItem'
		);
	}
	
	
	/**
	 * Tests @see WikibaseItem::testAddSiteLink
	 * @dataProvider provideLinkData
	 * @depends testUnserializeContent
	 * @group Broken
	 */
	public function testAddSiteLink( $input, array $link, array $languages = null ) {
		$this->item = $this->ch->unserializeContent( $input,'application/json' );
		//$this->addSiteLink( $siteId, $pageName, $updateType = 'set' );
	}
	
	public function provideBasicData() {
		return array(
			array(
				'{
					"label": { },
					"description": { }
				}',
				array(),
				array(),
				null,
			),
			array(
				'{
					"label": { },
					"description": { }
				}',
				array(),
				array(),
				array(),
			),
			array(
				'{
					"label": { },
					"description": { }
				}',
				array(),
				array(),
				array( 'en', 'de' ),
			),
			array(
				'{
					"label": { },
					"description": { }
				}',
				array(),
				array(),
				array( 'en' ),
			),
			
			array(
				'{
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				array(),
				null,
			),
			array(
				'{
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array(),
				array(),
				array(),
			),
			array(
				'{
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				array(),
				array( 'en', 'de' ),
			),
			array(
				'{
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array( 'en' => 'en-value' ),
				array(),
				array( 'en' ),
			),
			
			array(
				'{
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array(),
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				null,
			),
			array(
				'{
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array(),
				array(),
				array(),
			),
			array(
				'{
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array(),
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				array( 'en', 'de' ),
			),
			array(
				'{
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array(),
				array( 'en' => 'en-value' ),
				array( 'en' ),
			),
			
			array(
				'{
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } },
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				null,
			),
			array(
				'{
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } },
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array(),
				array(),
				array(),
			),
			array(
				'{
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } },
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				array( 'de' => 'de-value', 'en' => 'en-value' ),
				array( 'en', 'de' ),
			),
			array(
				'{
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } },
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}',
				array( 'en' => 'en-value' ),
				array( 'en' => 'en-value' ),
				array( 'en' ),
			),
		);
	}
}