<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use SplFileInfo;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\DeserializerFactory
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RealItemsTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$dataValueClasses = array_merge(
			$GLOBALS['evilDataValueMap'],
			array(
				'globecoordinate' => 'DataValues\GlobeCoordinateValue',
				'monolingualtext' => 'DataValues\MonolingualTextValue',
				'multilingualtext' => 'DataValues\MultilingualTextValue',
				'quantity' => 'DataValues\QuantityValue',
				'time' => 'DataValues\TimeValue',
				'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
			)
		);

		$factory = new DeserializerFactory(
			new DataValueDeserializer( $dataValueClasses ),
			new BasicEntityIdParser()
		);

		$this->deserializer = $factory->newItemDeserializer();
	}

	/**
	 * @dataProvider itemSerializationProvider
	 */
	public function testDeserializationWorksAndReturnsItem( $fileName, $serialization ) {
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
	public function testDeserializationReturnsCorrectItem( $fileName, $serialization ) {
		$item = $this->deserializer->deserialize( $serialization );

		$expectedItem = Item::newFromArray( $serialization );

		$this->assertTrue(
			$expectedItem->equals( $item ),
			$fileName . ' should deserialize into the correct Item'
		);
	}

	public function itemSerializationProvider() {
		return $this->getItemSerializationsFromDir( __DIR__ . '/../data/items/' );
	}

	private function getItemSerializationsFromDir( $dir ) {
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

}