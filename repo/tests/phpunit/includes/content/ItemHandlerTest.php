<?php

namespace Wikibase\Test;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ItemHandler
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseItem
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemHandlerTest extends EntityHandlerTest {

	/**
	 * @see EntityHandlerTest::getModelId
	 * @return string
	 */
	public function getModelId() {
		return CONTENT_MODEL_WIKIBASE_ITEM;
	}

	/**
	 * @see EntityHandlerTest::getClassName
	 * @return string
	 */
	public function getClassName() {
		return '\Wikibase\ItemHandler';
	}

	/**
	 * @see EntityHandlerTest::contentProvider
	 */
	public function contentProvider() {
		$contents = parent::contentProvider();

		/**
		 * @var ItemContent $content
		 */
		$content = clone $contents[1][0];
		$content->getItem()->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foobar' ) );
		$contents[] = array( $content );

		return $contents;
	}

}
