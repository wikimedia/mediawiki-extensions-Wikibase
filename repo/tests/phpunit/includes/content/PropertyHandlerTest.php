<?php

namespace Wikibase\Test;

use Wikibase\PropertyContent;

/**
 * @covers Wikibase\PropertyHandler
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseProperty
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyHandlerTest extends EntityHandlerTest {

	/**
	 * @see EntityHandlerTest::getModelId
	 * @return string
	 */
	public function getModelId() {
		return CONTENT_MODEL_WIKIBASE_PROPERTY;
	}

	/**
	 * @see EntityHandlerTest::getClassName
	 * @return string
	 */
	public function getClassName() {
		return '\Wikibase\PropertyHandler';
	}

	/**
	 * @see EntityHandlerTest::contentProvider
	 */
	public function contentProvider() {
		$contents = parent::contentProvider();

		/**
		 * @var PropertyContent $content
		 */
		$content = clone $contents[1][0];
		// TODO: add some prop-specific stuff: $content->getProperty()->;
		$contents[] = array( $content );

		return $contents;
	}

}
