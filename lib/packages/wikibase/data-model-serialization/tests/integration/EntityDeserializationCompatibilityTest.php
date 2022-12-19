<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Deserializers\Deserializer;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * @covers DataValues\Deserializers\DataValueDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeserializationCompatibilityTest extends TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer( [
				'string' => StringValue::class,
				'unknown' => UnknownValue::class,
				'globecoordinate' => GlobeCoordinateValue::class,
				'quantity' => QuantityValue::class,
				'time' => TimeValue::class,
				'wikibase-entityid' => EntityIdValue::class,
			] ),
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
			EntityDocument::class,
			$entity,
			'Deserialization of ' . $fileName . ' should lead to an EntityDocument instance'
		);
	}

	public function entityProvider() {
		return $this->getEntitySerializationsFromDir( __DIR__ . '/../data/' );
	}

	private function getEntitySerializationsFromDir( $dir ) {
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

}
