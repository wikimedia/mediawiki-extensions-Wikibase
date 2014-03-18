<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use SplFileInfo;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\DeserializerFactory
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RealEntitiesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$this->deserializer = TestDeserializerFactory::newInstanceWithDataValueSupport()->newEntityDeserializer();
	}

	/**
	 * @dataProvider itemSerializationProvider
	 */
	public function testGivenItem_DeserializationWorksAndReturnsItem( $fileName, $serialization ) {
		$item = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf(
			'Wikibase\DataModel\Entity\Item',
			$item,
			$fileName . ' should deserialize to an Item'
		);
	}

	/**
	 * @dataProvider itemSerializationProvider
	 */
	public function testGivenItem_DeserializationReturnsCorrectItem( $fileName, $serialization ) {
		$item = $this->deserializer->deserialize( $serialization );

		$expectedItem = Item::newFromArray( $serialization );

		$this->workAroundSomeOldEntityBug( $expectedItem );

		$this->assertTrue(
			$expectedItem->equals( $item ),
			$fileName . ' should deserialize into the correct Item'
		);
	}

	public function itemSerializationProvider() {
		return $this->getEntitySerializationsFromDir( __DIR__ . '/../data/items/' );
	}

	private function getEntitySerializationsFromDir( $dir ) {
		$argumentLists = array();

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir ) ) as $fileInfo ) {
			if ( $fileInfo->getExtension() === 'json' ) {
				$argumentLists[] = array(
					$fileInfo->getFilename(),
					json_decode( file_get_contents( $fileInfo->getPathname() ), true )
				);
			}
		}

		return $argumentLists;
	}

	public function propertySerializationProvider() {
		return $this->getEntitySerializationsFromDir( __DIR__ . '/../data/properties/' );
	}

	/**
	 * @dataProvider propertySerializationProvider
	 */
	public function testGivenProperty_DeserializationReturnsCorrectProperty( $fileName, $serialization ) {
		$item = $this->deserializer->deserialize( $serialization );

		$expectedProperty = Property::newFromArray( $serialization );

		$this->workAroundSomeOldEntityBug( $expectedProperty );

		$this->assertTrue(
			$expectedProperty->equals( $item ),
			$fileName . ' should deserialize into the correct Item'
		);
	}

	private function workAroundSomeOldEntityBug( Entity $entity ) {
		// This fixes alias list consistency by triggering the normalization code.
		// The old deserialization code (Item/Property::newFromArray() does not do this automatically.
		// There are some old revisions for which this normalization is needed due to
		// a long ago fixed bug.
		$entity->setAllAliases( $entity->getAllAliases() );
	}

}