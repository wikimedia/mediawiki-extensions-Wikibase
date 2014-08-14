<?php

namespace Wikibase\Test\Snak;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Snak\PropertyValueSnak
 * @uses DataValues\StringValue
 * @uses Wikibase\DataModel\Entity\EntityId
 * @uses Wikibase\DataModel\Entity\PropertyId
 * @uses Wikibase\DataModel\Snak\SnakObject
 * @uses DataValues\DataValueObject
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyValueSnakTest extends SnakObjectTest {

	public function constructorProvider() {
		return array(
			array( true, new PropertyId( 'P1' ), new StringValue( 'a' ) ),
			array( true, new PropertyId( 'P9001' ), new StringValue( 'bc' ) ),
		);
	}

	public function getClass() {
		return 'Wikibase\DataModel\Snak\PropertyValueSnak';
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDataValue( PropertyValueSnak $snak ) {
		$dataValue = $snak->getDataValue();
		$this->assertInstanceOf( 'DataValues\DataValue', $dataValue );
	}

}
