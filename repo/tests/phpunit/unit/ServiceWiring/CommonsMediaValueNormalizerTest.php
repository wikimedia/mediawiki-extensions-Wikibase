<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikibase\Repo\Normalization\CommonsMediaValueNormalizer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CommonsMediaValueNormalizerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.CachingCommonsMediaFileNameLookup',
			$this->createMock( CachingCommonsMediaFileNameLookup::class ) );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			CommonsMediaValueNormalizer::class,
			$this->getService( 'WikibaseRepo.CommonsMediaValueNormalizer' )
		);
	}

}
