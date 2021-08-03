<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\Lib\Normalization\StringValueNormalizer;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StringValueNormalizerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.StringNormalizer',
			new StringNormalizer() );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			StringValueNormalizer::class,
			$this->getService( 'WikibaseRepo.StringValueNormalizer' )
		);
	}

}
