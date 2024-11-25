<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Deserializers\Deserializer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * @covers Wikibase\InternalSerialization\DeserializerFactory
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RealEntitiesTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$this->deserializer = TestFactoryBuilder::newDeserializerFactoryWithDataValueSupport()->newEntityDeserializer();
	}

	/**
	 * @dataProvider itemLegacySerializationProvider
	 */
	public function testGivenLegacyItem_DeserializationReturnsItem( $fileName, $serialization ) {
		$item = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf(
			Item::class,
			$item,
			$fileName . ' should deserialize into an Item'
		);
	}

	public static function itemLegacySerializationProvider() {
		return self::getEntitySerializationsFromDir( __DIR__ . '/../data/items/legacy/' );
	}

	private static function getEntitySerializationsFromDir( $dir ) {
		$argumentLists = [];

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) ) as $fileInfo ) {
			if ( $fileInfo->getExtension() === 'json' ) {
				$argumentLists[] = [
					$fileInfo->getFilename(),
					json_decode( file_get_contents( $fileInfo->getPathname() ), true ),
				];
			}
		}

		return $argumentLists;
	}

	public static function propertyLegacySerializationProvider() {
		return self::getEntitySerializationsFromDir( __DIR__ . '/../data/properties/legacy/' );
	}

	/**
	 * @dataProvider propertyLegacySerializationProvider
	 */
	public function testGivenLegacyProperty_DeserializationReturnsProperty( $fileName, $serialization ) {
		$property = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf(
			Property::class,
			$property,
			$fileName . ' should deserialize into a Property'
		);
	}

	/**
	 * @dataProvider currentEntitySerializationProvider
	 */
	public function testGivenCurrentEntities_DeserializationReturnsCorrectEntity( $fileName, $serialization ) {
		$entity = $this->deserializer->deserialize( $serialization );

		$expectedEntity = TestFactoryBuilder::newCurrentDeserializerFactory()
			->newEntityDeserializer()->deserialize( $serialization );

		$this->assertTrue(
			$entity->equals( $expectedEntity ),
			$fileName . ' should be deserialized into the same entity by both deserializers'
		);
	}

	public static function currentEntitySerializationProvider() {
		return self::getEntitySerializationsFromDir( __DIR__ . '/../data/items/current/' );
	}

}
