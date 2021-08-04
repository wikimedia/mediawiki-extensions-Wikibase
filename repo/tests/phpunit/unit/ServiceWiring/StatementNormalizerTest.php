<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Normalization\ReferenceNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Lib\Normalization\StatementNormalizer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementNormalizerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.SnakNormalizer',
			$this->createMock( SnakNormalizer::class ) );
		$this->mockService( 'WikibaseRepo.ReferenceNormalizer',
			$this->createMock( ReferenceNormalizer::class ) );

		$this->assertInstanceOf(
			StatementNormalizer::class,
			$this->getService( 'WikibaseRepo.StatementNormalizer' )
		);
	}

}
