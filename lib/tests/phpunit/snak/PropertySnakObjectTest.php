<?php

namespace Wikibase\Test;
use Wikibase\PropertySnak as PropertySnak;

/**
 * Tests for the Wikibase\PropertySnakObject class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class PropertySnakObjectTest extends SnakObjectTest {

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetPropertyId( PropertySnak $omnomnom ) {
		$id = $omnomnom->getPropertyId();
		$this->assertInternalType( 'integer', $id );
		$this->assertEquals( $id, $omnomnom->getPropertyId() );
	}

}
