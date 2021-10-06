<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Hooks\WikibaseClientHookRunner;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class HookRunnerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getHookContainer' );

		$this->assertInstanceOf(
			WikibaseClientHookRunner::class,
			$this->getService( 'WikibaseClient.HookRunner' )
		);
	}

}
