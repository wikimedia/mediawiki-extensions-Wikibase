<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Normalization\ReferenceNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReferenceNormalizerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.SnakNormalizer',
			$this->createMock( SnakNormalizer::class ) );

		$this->assertInstanceOf(
			ReferenceNormalizer::class,
			$this->getService( 'WikibaseRepo.ReferenceNormalizer' )
		);
	}

}
