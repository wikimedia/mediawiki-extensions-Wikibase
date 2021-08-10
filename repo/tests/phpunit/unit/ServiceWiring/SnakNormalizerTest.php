<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakNormalizerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( [] ) );

		$this->assertInstanceOf(
			SnakNormalizer::class,
			$this->getService( 'WikibaseRepo.SnakNormalizer' )
		);
	}

}
