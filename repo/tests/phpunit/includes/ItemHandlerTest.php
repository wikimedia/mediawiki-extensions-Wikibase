<?php

namespace Wikibase\Test;
use \Wikibase\ItemHandler as ItemHandler;
use \Wikibase\Item as Item;
use \Wikibase\ItemContent as ItemContent;

/**
 * Tests for the WikibaseItem class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItem
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemHandlerTest extends \MediaWikiTestCase {
	
	/**
	 * Enter description here ...
	 * @var ItemHandler
	 */
	protected $ch;
	
	/**
	 * @var ItemContent
	 */
	protected $itemContent;
	
	/**
	 * This is to set up the environment
	 */
	public function setUp() {
  		parent::setUp();
		$this->ch = new ItemHandler();
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
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$this->assertInstanceOf(
			'\Wikibase\ItemContent',
			$this->itemContent,
			'Calling unserializeContent on a \Wikibase\ItemHandler should return a \Wikibase\Item'
		);
	}

	/**
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testGetLabels( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$this->assertEquals(
			$labels,
			$this->itemContent->getItem()->getLabels( $languages ),
			'Testing getLabels on a new \Wikibase\Item after creating it with preset values and doing a unserializeContent'
		);
	}

	/**
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testGetDescriptions( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$this->assertEquals(
			$descriptions,
			$this->itemContent->getItem()->getDescriptions( $languages ),
			'Testing getDescriptions on a new \Wikibase\Item after creating it with preset values and doing a unserializeContent'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::copy
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testCopy( $input ) {
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$copy = $this->itemContent->copy();
		$this->assertInstanceOf(
			'\Wikibase\ItemContent',
			$copy,
			'Calling copy on the return value of \Wikibase\Item::unserializeContent() should still return a new \Wikibase\Item object'
		);
		$this->assertEquals(
			$copy,
			$this->itemContent,
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
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$this->itemContent->getItem()->cleanStructure();
		$this->assertInstanceOf(
			'\Wikibase\ItemContent',
			$this->itemContent,
			'Calling cleanStructure should still leave the item as a \Wikibase\Item object'
		);
		$this->assertInternalType(
			'array',
			$this->itemContent->getItem()->getLabels(),
			'Checking if the expected structure is the type for returned labels for a cleaned \Wikibase\Item'
		);
		$this->assertInternalType(
			'array',
			$this->itemContent->getItem()->getdescriptions(),
			'Checking if the expected structure is the type returned for descriptions for a cleaned WikibaseItem'
		);
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