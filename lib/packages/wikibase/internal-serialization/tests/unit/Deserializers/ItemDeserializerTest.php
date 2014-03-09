<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\InternalSerialization\Deserializers\ClaimDeserializer;
use Wikibase\InternalSerialization\Deserializers\EntityIdDeserializer;
use Wikibase\InternalSerialization\Deserializers\ItemDeserializer;
use Wikibase\InternalSerialization\Deserializers\SiteLinkListDeserializer;
use Wikibase\InternalSerialization\Deserializers\SnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\SnakListDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\ItemDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$idDeserializer = new EntityIdDeserializer( new BasicEntityIdParser() );

		$linkDeserializer = new SiteLinkListDeserializer();

		$snakDeserializer = new SnakDeserializer( $this->getMock( 'Deserializers\Deserializer' ) );

		$claimDeserializer = new ClaimDeserializer(
			$snakDeserializer,
			new SnakListDeserializer( $snakDeserializer )
		);

		$this->deserializer = new ItemDeserializer( $idDeserializer, $linkDeserializer, $claimDeserializer );
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
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
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

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'foo' );
		$item->addClaim( $statement );

		$this->assertDeserialization(
			array(
				'claims' => array(
					array(
						'm' => array( 'novalue', 42 ),
						'q' => array(),
						'g' => 'foo',
						'rank' => Claim::RANK_NORMAL,
						'refs' => array()
					)
				)
			),
			$item
		);
	}

}