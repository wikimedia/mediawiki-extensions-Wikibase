<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeserializationCompatibilityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(
				array(
					'string' => 'DataValues\StringValue',
					'unknown' => 'DataValues\UnknownValue',
					'globecoordinate' => 'DataValues\GlobeCoordinateValue',
					'quantity' => 'DataValues\QuantityValue',
					'time' => 'DataValues\TimeValue',
					'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
				)
			),
			new BasicEntityIdParser()
		);

		$this->deserializer = $deserializerFactory->newEntityDeserializer();
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testGivenEntitySerialization_entityIsReturned( $fileName, $serialization ) {
		$entity = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf(
			'Wikibase\DataModel\Entity\EntityDocument',
			$entity,
			'Deserialization of ' . $fileName . ' should lead to an EntityDocument instance'
		);
	}

	public function entityProvider() {
		return $this->getEntitySerializationsFromDir( __DIR__ . '/../data/' );
	}

	private function getEntitySerializationsFromDir( $dir ) {
		$argumentLists = array();

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) ) as $fileInfo ) {
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
