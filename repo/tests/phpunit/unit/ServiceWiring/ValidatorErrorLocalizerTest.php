<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Localizer\MessageParameterFormatter;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValidatorErrorLocalizerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.MessageParameterFormatter',
			$this->createMock( MessageParameterFormatter::class ) );

		$this->assertInstanceOf(
			ValidatorErrorLocalizer::class,
			$this->getService( 'WikibaseRepo.ValidatorErrorLocalizer' )
		);
	}

}
