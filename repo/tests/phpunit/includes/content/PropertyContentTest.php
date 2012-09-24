<?php

namespace Wikibase\Test;
use Wikibase\PropertyContent, Wikibase\EntityContent;

/**
 * Tests for the Wikibase\PropertyContent class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContentTest extends EntityContentTest {

	/**
	 * @see EntityContentTest::getContentClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return '\Wikibase\PropertyContent';
	}

	/**
	 * @see EntityContentTest::newEmpty
	 *
	 * @since 0.2
	 *
	 * @return EntityContent
	 */
	protected function newEmpty() {
		$content = PropertyContent::newEmpty();

		$dataTypes = \Wikibase\Settings::get( 'dataTypes' );
		$content->getProperty()->setDataTypeById( array_shift( $dataTypes ) );

		return $content;
	}

}
