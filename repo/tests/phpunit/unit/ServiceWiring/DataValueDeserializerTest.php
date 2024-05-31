<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataValueDeserializerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockDataTypeDefs();
		$this->assertInstanceOf(
			DataValueDeserializer::class,
			$this->getService( 'WikibaseRepo.DataValueDeserializer' )
		);
	}

	/** @dataProvider provideInvalidId */
	public function testEntityIdErrorHandling( $invalidId ) {
		$this->mockDataTypeDefs();
		/** @var DataValueDeserializer $deserializer */
		'@phan-var DataValueDeserializer $deserializer';
		$deserializer = $this->getService( 'WikibaseRepo.DataValueDeserializer' );

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( [
			'type' => 'wikibase-entityid',
			'value' => [ 'id' => $invalidId ],
		] );
	}

	public static function provideInvalidId(): iterable {
		yield 'empty string' => [ '' ];
		yield 'T334719' => [ [ 'array' => true ] ];
	}

	public function mockDataTypeDefs(): void {
		$this->mockService(
			'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( require __DIR__ . '/../../../../WikibaseRepo.datatypes.php' )
		);
	}

}
