<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\AnonymousEditWarningBuilder;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AnonymousEditWarningBuilderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSpecialPageFactory' );

		$this->assertInstanceOf(
			AnonymousEditWarningBuilder::class,
			$this->getService( 'WikibaseRepo.AnonymousEditWarningBuilder' )
		);
	}

}
