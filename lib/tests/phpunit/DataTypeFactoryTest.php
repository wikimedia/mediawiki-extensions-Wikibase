<?php

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;

/**
 * @covers \Wikibase\Lib\DataTypeFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DataTypeFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider valueTypesProvider
	 */
	public function testConstructor( array $valueTypes ) {
		$instance = new DataTypeFactory( $valueTypes );
		$this->assertInstanceOf( DataTypeFactory::class, $instance );
	}

	public function valueTypesProvider() {
		return [
			[ [] ],
			[ [ 'string' => 'string' ] ],
			[ [ 'customType' => 'customValueType' ] ],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testConstructorThrowsException( array $argument ) {
		$this->expectException( InvalidArgumentException::class );
		new DataTypeFactory( $argument );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ [ 'string' => '' ] ],
			[ [ 'string' => 1 ] ],
			[ [ 'string' => new DataType( 'string', 'string' ) ] ],
			[ [ '' => 'string' ] ],
			[ [ 0 => 'string' ] ],
			[ [ 0 => new DataType( 'string', 'string' ) ] ],
		];
	}

	public function testGetTypeIds() {
		$instance = new DataTypeFactory( [ 'customType' => 'string' ] );

		$expected = [ 'customType' ];
		$this->assertSame( $expected, $instance->getTypeIds() );
	}

	public function testGetType() {
		$instance = new DataTypeFactory( [ 'customType' => 'string' ] );

		$expected = new DataType( 'customType', 'string' );
		$this->assertEquals( $expected, $instance->getType( 'customType' ) );
	}

	public function testGetUnknownType() {
		$instance = new DataTypeFactory( [] );

		$this->expectException( OutOfBoundsException::class );
		$instance->getType( 'unknownTypeId' );
	}

	public function testGetTypes() {
		$instance = new DataTypeFactory( [ 'customType' => 'string' ] );

		$expected = [ 'customType' => new DataType( 'customType', 'string' ) ];
		$this->assertEquals( $expected, $instance->getTypes() );
	}

	public static function provideDataTypeBuilder() {
		return [
			[
				'data-type',
				[ 'data-type' => 'valuetype' ],
				'valuetype',
			],
		];
	}

	/**
	 * @dataProvider provideDataTypeBuilder
	 */
	public function testDataTypeBuilder( $id, $types, $expected ) {
		$factory = new DataTypeFactory( $types );

		$type = $factory->getType( $id );

		$this->assertEquals( $id, $type->getId() );
		$this->assertEquals( $expected, $type->getDataValueType() );
	}

}
