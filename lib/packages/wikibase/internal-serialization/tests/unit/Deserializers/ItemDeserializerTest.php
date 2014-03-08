<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\InternalSerialization\Deserializers\ItemDeserializer;
use Wikibase\InternalSerialization\Deserializers\SiteLinkListDeserializer;

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
		$linkDeserializer = new SiteLinkListDeserializer();

		$this->deserializer = new ItemDeserializer( $linkDeserializer );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
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

		$newItem = $this->itemFromSerialization(
			array(
				'links' => array(
					'foo' => 'bar',
				)
			)
		);

		$this->assertTrue( $item->equals( $newItem ) );
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

}