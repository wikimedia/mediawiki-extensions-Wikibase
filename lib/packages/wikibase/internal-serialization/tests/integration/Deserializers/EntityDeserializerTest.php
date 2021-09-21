<?php

namespace Tests\Integration\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Serializers\Serializer;
use Tests\Integration\Wikibase\InternalSerialization\TestFactoryBuilder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\EntityDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	/**
	 * @var Serializer
	 */
	private $currentSerializer;

	protected function setUp(): void {
		$this->deserializer = TestFactoryBuilder::newDeserializerFactoryWithDataValueSupport()->newEntityDeserializer();
		$this->currentSerializer = TestFactoryBuilder::newSerializerFactory()->newEntitySerializer();
	}

	public function testGivenLegacySerialization_itemIsDeserialized() {
		$this->assertDeserializesToItem( $this->newLegacySerialization() );
	}

	public function testGivenCurrentSerialization_itemIsDeserialized() {
		$this->assertDeserializesToItem( $this->newCurrentSerialization() );
	}

	private function assertDeserializesToItem( $serialization ) {
		$item = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf( Item::class, $item );
	}

	private function newLegacySerialization() {
		return $this->getSerializationFromFile( 'items/legacy/recent/Q1.json' );
	}

	private function newCurrentSerialization() {
		return $this->getSerializationFromFile( 'items/current/Q1.json' );
	}

	private function getSerializationFromFile( $file ) {
		$itemJson = file_get_contents( __DIR__ . '/../../data/' . $file );
		return json_decode( $itemJson, true );
	}

	public function testGivenGeneratedSerialization_itemIsDeserialized() {
		$this->assertDeserializesToItem( $this->currentSerializer->serialize( $this->newTestItem() ) );
	}

	private function newTestItem() {
		$item = new Item( new ItemId( 'Q42' ) );

		$item->setLabel( 'en', 'foo' );
		$item->setLabel( 'de', 'bar' );

		$item->getSiteLinkList()->addNewSiteLink( 'wiki', 'page' );

		return $item;
	}

}
