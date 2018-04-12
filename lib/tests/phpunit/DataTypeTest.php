<?php

namespace Wikibase\Lib\Tests;

use PHPUnit4And6Compat;
use Wikibase\Lib\DataType;
use InvalidArgumentException;

/**
 * @covers Wikibase\Lib\DataType
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class DataTypeTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException( $propertyType, $valueType ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new DataType( $propertyType, $valueType );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			[ 'propertyType', '' ],
			[ 'propertyType', null ],
			[ 'propertyType', false ],
			[ 'propertyType', 1 ],
			[ '', 'valueType' ],
			[ null, 'valueType' ],
			[ false, 'valueType' ],
			[ 0, 'valueType' ],
		];
	}

	public function testGetId() {
		$type = new DataType( 'propertyType', 'valueType' );
		$this->assertSame( 'propertyType', $type->getId() );
	}

	public function testGetDataValueType() {
		$type = new DataType( 'propertyType', 'valueType' );
		$this->assertSame( 'valueType', $type->getDataValueType() );
	}

	public function testGetMessageKey() {
		$type = new DataType( 'propertyType', 'valueType' );
		$this->assertSame( 'datatypes-type-propertyType', $type->getMessageKey() );
	}

	public function testToArray() {
		$type = new DataType( 'propertyType', 'valueType' );
		$this->assertSame( [ 'dataValueType' => 'valueType' ], $type->toArray() );
	}

}
