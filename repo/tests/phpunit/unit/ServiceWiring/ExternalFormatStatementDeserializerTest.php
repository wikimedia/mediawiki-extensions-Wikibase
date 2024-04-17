<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ExternalFormatStatementDeserializerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.BaseDataModelDeserializerFactory',
			$this->createStub( DeserializerFactory::class )
		);

		$this->assertInstanceOf(
			Deserializer::class,
			$this->getService( 'WikibaseRepo.ExternalFormatStatementDeserializer' )
		);
	}
}
