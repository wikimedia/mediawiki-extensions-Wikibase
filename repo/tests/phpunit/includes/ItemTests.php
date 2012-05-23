<?php
/**
 *  Tests for the Wikibase\Item class.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemTests extends MediaWikiTestCase {

	protected $the_item;

	public function setUp() {
		$handler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
		$this->the_item = $handler->makeEmptyContent();
	}

	public function testGetModelName()  {
		$this->assertEquals( CONTENT_MODEL_WIKIBASE_ITEM, $this->the_item->getModel() );
	}

}