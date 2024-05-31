<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\DataModel\Deserializers\SnakValueDeserializer;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakValueDeserializerTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$dataTypeDefinitions = $this->createStub( DataTypeDefinitions::class );
		$dataTypeDefinitions->method( 'getDeserializerBuilders' )->willReturn( [] );
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions', $dataTypeDefinitions );
		$this->mockService( 'WikibaseRepo.DataValueDeserializer', new DataValueDeserializer( [] ) );

		$this->assertInstanceOf(
			SnakValueDeserializer::class,
			$this->getService( 'WikibaseRepo.SnakValueDeserializer' )
		);
	}
}
