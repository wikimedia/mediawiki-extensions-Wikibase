<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\DataModel\Deserializers\SnakValueParser;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakValueParserTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService(
			'WikibaseRepo.DataValueDeserializer',
			$this->createMock( DataValueDeserializer::class )
		);
		$dataTypeDefinitions = $this->createStub( DataTypeDefinitions::class );
		$dataTypeDefinitions->method( 'getParserFactoryCallbacks' )->willReturn( [] );
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions', $dataTypeDefinitions );

		$this->assertInstanceOf(
			SnakValueParser::class,
			$this->getService( 'WikibaseRepo.SnakValueParser' )
		);
	}
}
