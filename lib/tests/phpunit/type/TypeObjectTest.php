<?php

namespace Wikibase\Test;
use \Wikibase\TypeObject as TypeObject;
use \Wikibase\Type as Type;

/**
 * Tests for the Wikibase\TypeObject class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseType
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypeObjectTest extends EntityObjectTest {

	/**
	 * @see EntityObjectTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\Type
	 */
	protected function getNewEmpty() {
		return TypeObject::newEmpty();
	}

	/**
	 * @see EntityObjectTest::getNewFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return \Wikibase\Entity
	 */
	protected function getNewFromArray( array $data ) {
		return TypeObject::newFromArray( $data );
	}
}