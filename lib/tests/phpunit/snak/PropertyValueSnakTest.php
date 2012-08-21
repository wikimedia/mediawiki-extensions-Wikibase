<?php

namespace Wikibase\Test;
use Wikibase\PropertyValueSnak as PropertyValueSnak;
use \DataValue\DataValueObject as DataValueObject;

/**
 * Tests for the Wikibase\PropertyValueSnak class.
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
class PropertyValueSnakTest extends PropertySnakObjectTest {

	public function constructorProvider() {
		return array(
			array( true, 1, new DataValueObject() ),
			array( true, 9001, new DataValueObject() ),
			array( false ),
			array( false, 42 ),
		);
	}

	public function getClass() {
		return '\Wikibase\PropertyValueSnak';
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDataValue( PropertyValueSnak $omnomnom ) {
		$dataValue = $omnomnom->getDataValue();
		$this->assertInstanceOf( '\DataValue\DataValue', $dataValue );
		$this->assertEquals( $dataValue, $omnomnom->getDataValue() );
	}

}
