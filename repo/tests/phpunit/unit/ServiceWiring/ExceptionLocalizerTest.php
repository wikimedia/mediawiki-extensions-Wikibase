<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Localizer\MessageParameterFormatter;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ExceptionLocalizerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.MessageParameterFormatter',
			$this->createMock( MessageParameterFormatter::class ) );

		$this->assertInstanceOf(
			ExceptionLocalizer::class,
			$this->getService( 'WikibaseRepo.ExceptionLocalizer' )
		);
	}

}
