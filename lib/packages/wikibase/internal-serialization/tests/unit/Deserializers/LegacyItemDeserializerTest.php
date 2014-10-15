<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\InternalSerialization\Deserializers\LegacyClaimDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyEntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyItemDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySiteLinkListDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakListDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyItemDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyItemDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$idDeserializer = new LegacyEntityIdDeserializer( new BasicEntityIdParser() );

		$snakDeserializer = new LegacySnakDeserializer( $this->getMock( 'Deserializers\Deserializer' ) );

		$claimDeserializer = new LegacyClaimDeserializer(
			$snakDeserializer,
			new LegacySnakListDeserializer( $snakDeserializer )
		);

		$this->deserializer = new LegacyItemDeserializer(
			$idDeserializer,
			new LegacySiteLinkListDeserializer(),
			$claimDeserializer,
			new LegacyFingerprintDeserializer()
		);
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),

			array( array(
				'links' => array( null )
			) ),

			array( array(
				'claims' => null
			) ),

			array( array(
				'claims' => array( null )
			) ),

			array( array(
				'entity' => 42
			) ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( $serialization );
	}

	private function expectDeserializationException() {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
	}

	public function testGivenEmptyArray_emptyItemIsReturned() {
		$this->assertEquals(
			Item::newEmpty(),
			$this->deserializer->deserialize( array() )
		);
	}

	public function testGivenLinks_itemHasSiteLinks() {
		$item = Item::newEmpty();

		$item->addSiteLink( new SiteLink( 'foo', 'bar' ) );
		$item->addSiteLink( new SiteLink( 'baz', 'bah' ) );

		$this->assertDeserialization(
			array(
				'links' => array(
					'foo' => 'bar',
					'baz' => 'bah',
				)
			),
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
	 * @return Item
	 */
	private function itemFromSerialization( $serialization ) {
		$item = $this->deserializer->deserialize( $serialization );
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $item );
		return $item;
	}

	public function testGivenStatement_itemHasStatement() {
		$item = Item::newEmpty();

		$item->addClaim( $this->newStatement() );

		$this->assertDeserialization(
			array(
				'claims' => array(
					$this->newStatementSerialization()
				)
			),
			$item
		);
	}

	private function newStatement() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement->setGuid( 'foo' );
		return $statement;
	}

	private function newStatementSerialization() {
		return array(
			'm' => array( 'novalue', 42 ),
			'q' => array(),
			'g' => 'foo',
			'rank' => Claim::RANK_NORMAL,
			'refs' => array()
		);
	}

	public function testGivenStatementWithLegacyKey_itemHasStatement() {
		$item = Item::newEmpty();

		$item->addClaim( $this->newStatement() );

		$this->assertDeserialization(
			array(
				'statements' => array(
					$this->newStatementSerialization()
				)
			),
			$item
		);
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenLabels_getLabelsReturnsThem( array $labels ) {
		$item = $this->itemFromSerialization( array( 'label' => $labels ) );

		$this->assertEquals( $labels, $item->getLabels() );
	}

	public function TermListProvider() {
		return array(
			array( array() ),

			array( array(
				'en' => 'foo',
				'de' => 'bar',
			) ),
		);
	}

	public function testGivenInvalidLabels_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'label' => null ) );
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenDescriptions_getDescriptionsReturnsThem( array $descriptions ) {
		$item = $this->itemFromSerialization( array( 'description' => $descriptions ) );

		$this->assertEquals( $descriptions, $item->getDescriptions() );
	}

	public function testGivenInvalidAliases_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'aliases' => null ) );
	}

	/**
	 * @dataProvider aliasesListProvider
	 */
	public function testGivenAliases_getAliasesReturnsThem( array $aliases ) {
		$item = $this->itemFromSerialization( array( 'aliases' => $aliases ) );

		$this->assertEquals( $aliases, $item->getAllAliases() );
	}

	public function aliasesListProvider() {
		return array(
			array( array() ),

			array( array(
				'en' => array( 'foo', 'bar' ),
				'de' => array( 'foo', 'bar', 'baz' ),
				'nl' => array( 'bah' ),
			) ),
		);
	}

}
