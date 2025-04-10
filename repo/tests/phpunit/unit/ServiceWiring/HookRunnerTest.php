<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Hooks\WikibaseRepoHookRunner;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

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

		$this->assertInstanceOf( WikibaseRepoHookRunner::class,
			$this->getService( 'WikibaseRepo.HookRunner' ) );
	}

}
