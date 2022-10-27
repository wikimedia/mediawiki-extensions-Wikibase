<?php

namespace Wikibase\DataModel\Tests\Entity;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\DataModel\Entity\ItemId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemIdTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider idSerializationProvider
	 */
	public function testCanConstructId( $idSerialization, $normalizedIdSerialization ) {
		$id = new ItemId( $idSerialization );

		$this->assertSame(
			$normalizedIdSerialization,
			$id->getSerialization()
		);
	}

	public function idSerializationProvider() {
		return [
			[ 'q1', 'Q1' ],
			[ 'q100', 'Q100' ],
			[ 'q1337', 'Q1337' ],
			[ 'q31337', 'Q31337' ],
			[ 'Q31337', 'Q31337' ],
			[ 'Q42', 'Q42' ],
			[ ':Q42', 'Q42' ],
			[ 'foo:Q42', 'foo:Q42' ],
			[ 'foo:bar:q42', 'foo:bar:Q42' ],
			[ 'Q2147483647', 'Q2147483647' ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotConstructWithInvalidSerialization( $invalidSerialization ) {
		$this->expectException( InvalidArgumentException::class );
		new ItemId( $invalidSerialization );
	}

	public function invalidIdSerializationProvider() {
		return [
			[ "Q1\n" ],
			[ 'q' ],
			[ 'p1' ],
			[ 'qq1' ],
			[ '1q' ],
			[ 'q01' ],
			[ 'q 1' ],
			[ ' q1' ],
			[ 'q1 ' ],
			[ '1' ],
			[ ' ' ],
			[ '' ],
			[ '0' ],
			[ 0 ],
			[ 1 ],
			[ 'Q2147483648' ],
			[ 'Q99999999999' ],
		];
	}

	public function testGetNumericId() {
		$id = new ItemId( 'Q1' );
		$this->assertSame( 1, $id->getNumericId() );
	}

	public function testGetNumericId_foreignId() {
		$id = new ItemId( 'foo:Q1' );
		$this->assertSame( 1, $id->getNumericId() );
	}

	public function testGetEntityType() {
		$id = new ItemId( 'Q1' );
		$this->assertSame( 'item', $id->getEntityType() );
	}

	public function testSerialize() {
		$id = new ItemId( 'Q1' );
		$this->assertSame( 'Q1', $id->serialize() );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testUnserialize( $json, $expected ) {
		$id = new ItemId( 'Q1' );
		$id->unserialize( $json );
		$this->assertSame( $expected, $id->getSerialization() );
	}

	public function serializationProvider() {
		return [
			[ 'Q2', 'Q2' ],
			[ '["item","Q2"]', 'Q2' ],

			// All these cases are kind of an injection vector and allow constructing invalid ids.
			[ '["string","Q2"]', 'Q2' ],
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
		$id = ItemId::newFromNumber( $number );
		$this->assertSame( 'Q' . $number, $id->getSerialization() );
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
		$this->expectException( InvalidArgumentException::class );
		ItemId::newFromNumber( $number );
	}

	public function invalidNumericIdProvider() {
		return [
			[ 'Q1' ],
			[ '42.1' ],
			[ 42.1 ],
			[ 2147483648 ],
			[ '2147483648' ],
		];
	}

	public function testNewFromRepositoryAndNumber() {
		$id = ItemId::newFromRepositoryAndNumber( 'foo', 1 );
		$this->assertSame( 'foo:Q1', $id->getSerialization() );
	}

	public function testNewFromRepositoryAndNumberWithInvalidNumericId() {
		$this->expectException( InvalidArgumentException::class );
		ItemId::newFromRepositoryAndNumber( '', 'Q1' );
	}

}
