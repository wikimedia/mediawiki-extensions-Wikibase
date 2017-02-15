<?php

namespace Wikibase\DataModel\Tests\Entity;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use InvalidArgumentException;

/**
 * @covers Wikibase\DataModel\Entity\PropertyId
 * @covers Wikibase\DataModel\Entity\EntityId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyIdTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider idSerializationProvider
	 */
	public function testCanConstructId( $idSerialization, $normalizedIdSerialization ) {
		$id = new PropertyId( $idSerialization );

		$this->assertEquals(
			$normalizedIdSerialization,
			$id->getSerialization()
		);
	}

	public function idSerializationProvider() {
		return [
			[ 'p1', 'P1' ],
			[ 'p100', 'P100' ],
			[ 'p1337', 'P1337' ],
			[ 'p31337', 'P31337' ],
			[ 'P31337', 'P31337' ],
			[ 'P42', 'P42' ],
			[ ':P42', 'P42' ],
			[ 'foo:P42', 'foo:P42' ],
			[ 'foo:bar:p42', 'foo:bar:P42' ],
			[ 'P2147483647', 'P2147483647' ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotConstructWithInvalidSerialization( $invalidSerialization ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new PropertyId( $invalidSerialization );
	}

	public function invalidIdSerializationProvider() {
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
		];
	}

	public function testGetNumericId() {
		$id = new PropertyId( 'P1' );
		$this->assertSame( 1, $id->getNumericId() );
	}

	public function testGetNumericId_foreignId() {
		$id = new PropertyId( 'foo:P1' );
		$this->assertSame( 1, $id->getNumericId() );
	}

	public function testGetEntityType() {
		$id = new PropertyId( 'P1' );
		$this->assertSame( 'property', $id->getEntityType() );
	}

	public function testSerialize() {
		$id = new PropertyId( 'P1' );
		$this->assertSame( 'P1', $id->serialize() );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testUnserialize( $json, $expected ) {
		$id = new PropertyId( 'P1' );
		$id->unserialize( $json );
		$this->assertSame( $expected, $id->getSerialization() );
	}

	public function serializationProvider() {
		return [
			[ 'P2', 'P2' ],
			[ '["property","P2"]', 'P2' ],

			// All these cases are kind of an injection vector and allow constructing invalid ids.
			[ '["string","P2"]', 'P2' ],
			[ '["","string"]', 'string' ],
			[ '["",""]', '' ],
			[ '["",2]', 2 ],
			[ '["",null]', null ],
			[ '', '' ],
		];
	}

	/**
	 * @dataProvider numericIdProvider
	 */
	public function testNewFromNumber( $number ) {
		$id = PropertyId::newFromNumber( $number );
		$this->assertEquals( 'P' . $number, $id->getSerialization() );
	}

	public function numericIdProvider() {
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
		$this->setExpectedException( InvalidArgumentException::class );
		PropertyId::newFromNumber( $number );
	}

	public function invalidNumericIdProvider() {
		return [
			[ 'P1' ],
			[ '42.1' ],
			[ 42.1 ],
			[ 2147483648 ],
			[ '2147483648' ],
		];
	}

	public function testNewFromRepositoryAndNumber() {
		$id = PropertyId::newFromRepositoryAndNumber( 'foo', 1 );
		$this->assertSame( 'foo:P1', $id->getSerialization() );
	}

	public function testNewFromRepositoryAndNumberWithInvalidNumericId() {
		$this->setExpectedException( InvalidArgumentException::class );
		PropertyId::newFromRepositoryAndNumber( '', 'P1' );
	}

}
