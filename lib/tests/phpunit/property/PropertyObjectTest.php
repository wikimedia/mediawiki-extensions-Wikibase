<?php

namespace Wikibase\Test;
use \Wikibase\PropertyObject as PropertyObject;
use \Wikibase\Property as Property;

/**
 * Tests for the Wikibase\PropertyObject class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyObjectTest extends EntityObjectTest {

	/**
	 * @see EntityObjectTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\Property
	 */
	protected function getNewEmpty() {
		return PropertyObject::newEmpty();
	}

	/**
	 * @see   EntityObjectTest::getNewFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return \Wikibase\Entity
	 */
	protected function getNewFromArray( array $data ) {
		return PropertyObject::newFromArray( $data );
	}
}