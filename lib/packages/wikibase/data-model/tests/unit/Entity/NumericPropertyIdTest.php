<?php

namespace Wikibase\DataModel\Tests\Entity;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @covers \Wikibase\DataModel\Entity\NumericPropertyId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 */
class NumericPropertyIdTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider idSerializationProvider
	 */
	public function testCanConstructId( $idSerialization, $normalizedIdSerialization ) {
		$id = new NumericPropertyId( $idSerialization );

		$this->assertSame(
			$normalizedIdSerialization,
			$id->getSerialization()
		);
	}

	public static function idSerializationProvider() {
		return [
			[ 'p1', 'P1' ],
			[ 'p100', 'P100' ],
			[ 'p1337', 'P1337' ],
			[ 'p31337', 'P31337' ],
			[ 'P31337', 'P31337' ],
			[ 'P42', 'P42' ],
			[ ':P42', 'P42' ],
			[ 'P2147483647', 'P2147483647' ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotConstructWithInvalidSerialization( $invalidSerialization ) {
		$this->expectException( InvalidArgumentException::class );
		new NumericPropertyId( $invalidSerialization );
	}

	public static function invalidIdSerializationProvider() {
		return [
			[ "P1\n" ],
			[ 'p' ],
			[ 'q1' ],
			[ 'pp1' ],
			[ '1p' ],
			[ 'p01' ],
			[ 'p 1' ],
			[ ' p1' ],
			[ 'p1 ' ],
			[ '1' ],
			[ ' ' ],
			[ '' ],
			[ '0' ],
			[ 0 ],
			[ 1 ],
			[ 'P2147483648' ],
			[ 'P99999999999' ],
			// no longer supported (T291823, T338223)
			[ 'foo:P42', 'foo:P42' ],
			[ 'foo:bar:p42', 'foo:bar:P42' ],
		];
	}

	public function testGetNumericId() {
		$id = new NumericPropertyId( 'P1' );
		$this->assertSame( 1, $id->getNumericId() );
	}

	public function testGetEntityType() {
		$id = new NumericPropertyId( 'P1' );
		$this->assertSame( 'property', $id->getEntityType() );
	}

	public function testSerialize() {
		$id = new NumericPropertyId( 'P1' );
		$this->assertSame( 'P1', $id->serialize() );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testUnserialize( $json, $expected ) {
		$id = new NumericPropertyId( 'P1' );
		$id->unserialize( $json );
		$this->assertSame( $expected, $id->getSerialization() );
	}

	public static function serializationProvider() {
		return [
			[ 'P2', 'P2' ],
			[ '["property","P2"]', 'P2' ],

			// All these cases are kind of an injection vector and allow constructing invalid ids.
			[ '["string","P2"]', 'P2' ],
			[ '["","string"]', 'string' ],
			[ '["",""]', '' ],
			[ '["",2]', 2 ],
			[ '["",null]', '' ],
			[ '', '' ],
		];
	}

	/**
	 * @dataProvider numericIdProvider
	 */
	public function testNewFromNumber( $number ) {
		$id = NumericPropertyId::newFromNumber( $number );
		$this->assertSame( 'P' . $number, $id->getSerialization() );
	}

	public static function numericIdProvider() {
		return [
			[ 42 ],
			[ '42' ],
			[ 42.0 ],
			// Check for 32-bit integer overflow on 32-bit PHP systems.
			[ 2147483647 ],
			[ '2147483647' ],
		];
	}

	/**
	 * @dataProvider invalidNumericIdProvider
	 */
	public function testNewFromNumberWithInvalidNumericId( $number ) {
		$this->expectException( InvalidArgumentException::class );
		NumericPropertyId::newFromNumber( $number );
	}

	public static function invalidNumericIdProvider() {
		return [
			[ 'P1' ],
			[ '42.1' ],
			[ 42.1 ],
			[ 2147483648 ],
			[ '2147483648' ],
		];
	}

}
