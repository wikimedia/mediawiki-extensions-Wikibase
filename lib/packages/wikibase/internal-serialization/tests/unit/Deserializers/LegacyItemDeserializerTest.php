<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyItemDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySiteLinkListDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakListDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyStatementDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyItemDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyItemDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$idDeserializer = new LegacyEntityIdDeserializer( new BasicEntityIdParser() );

		$snakDeserializer = new LegacySnakDeserializer( $this->createMock( Deserializer::class ) );

		$statementDeserializer = new LegacyStatementDeserializer(
			$snakDeserializer,
			new LegacySnakListDeserializer( $snakDeserializer )
		);

		$this->deserializer = new LegacyItemDeserializer(
			$idDeserializer,
			new LegacySiteLinkListDeserializer(),
			$statementDeserializer,
			new LegacyFingerprintDeserializer()
		);
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],

			[ [
				'links' => [ null ],
			] ],

			[ [
				'claims' => null,
			] ],

			[ [
				'claims' => [ null ],
			] ],

			[ [
				'entity' => 42,
			] ],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( $serialization );
	}

	private function expectDeserializationException() {
		$this->expectException( DeserializationException::class );
	}

	public function testGivenEmptyArray_emptyItemIsReturned() {
		$this->assertEquals(
			new Item(),
			$this->deserializer->deserialize( [] )
		);
	}

	public function testGivenLinks_itemHasSiteLinks() {
		$item = new Item();

		$item->getSiteLinkList()->addNewSiteLink( 'foo', 'bar' );
		$item->getSiteLinkList()->addNewSiteLink( 'baz', 'bah' );

		$this->assertDeserialization(
			[
				'links' => [
					'foo' => 'bar',
					'baz' => 'bah',
				],
			],
			$item
		);
	}

	private function assertDeserialization( $serialization, Item $expectedItem ) {
		$newItem = $this->itemFromSerialization( $serialization );

		$this->assertTrue(
			$expectedItem->equals( $newItem ),
			'Deserialized Item should match expected Item'
		);
	}

	/**
	 * @param string $serialization
	 *
	 * @return Item
	 */
	private function itemFromSerialization( $serialization ) {
		$item = $this->deserializer->deserialize( $serialization );
		$this->assertInstanceOf( Item::class, $item );
		return $item;
	}

	public function testGivenStatement_itemHasStatement() {
		$item = new Item();
		$item->getStatements()->addStatement( $this->newStatement() );

		$this->assertDeserialization(
			[
				'claims' => [
					$this->newStatementSerialization(),
				],
			],
			$item
		);
	}

	private function newStatement() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'foo' );
		return $statement;
	}

	private function newStatementSerialization() {
		return [
			'm' => [ 'novalue', 42 ],
			'q' => [],
			'g' => 'foo',
			'rank' => Statement::RANK_NORMAL,
			'refs' => [],
		];
	}

	public function testGivenStatementWithLegacyKey_itemHasStatement() {
		$item = new Item();
		$item->getStatements()->addStatement( $this->newStatement() );

		$this->assertDeserialization(
			[
				'statements' => [
					$this->newStatementSerialization(),
				],
			],
			$item
		);
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenLabels_getLabelsReturnsThem( array $labels ) {
		$item = $this->itemFromSerialization( [ 'label' => $labels ] );

		$this->assertEquals( $labels, $item->getFingerprint()->getLabels()->toTextArray() );
	}

	public function TermListProvider() {
		return [
			[ [] ],

			[ [
				'en' => 'foo',
				'de' => 'bar',
			] ],
		];
	}

	public function testGivenInvalidLabels_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'label' => null ] );
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenDescriptions_getDescriptionsReturnsThem( array $descriptions ) {
		$item = $this->itemFromSerialization( [ 'description' => $descriptions ] );

		$this->assertEquals( $descriptions, $item->getFingerprint()->getDescriptions()->toTextArray() );
	}

	public function testGivenInvalidAliases_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'aliases' => null ] );
	}

	/**
	 * @dataProvider aliasesListProvider
	 */
	public function testGivenAliases_getAliasesReturnsThem( array $aliases ) {
		$item = $this->itemFromSerialization( [ 'aliases' => $aliases ] );

		$this->assertEquals( $aliases, $item->getFingerprint()->getAliasGroups()->toTextArray() );
	}

	public function aliasesListProvider() {
		return [
			[ [] ],

			[ [
				'en' => [ 'foo', 'bar' ],
				'de' => [ 'foo', 'bar', 'baz' ],
				'nl' => [ 'bah' ],
			] ],
		];
	}

}
