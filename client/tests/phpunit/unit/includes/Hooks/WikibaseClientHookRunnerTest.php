<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Hooks;

use MediaWiki\Tests\HookContainer\HookRunnerTestBase;
use Wikibase\Client\Hooks\WikibaseClientHookRunner;

/**
 * @covers \Wikibase\Client\Hooks\WikibaseClientHookRunner
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseClientHookRunnerTest extends HookRunnerTestBase {

	public function provideHookRunners(): iterable {
		yield WikibaseClientHookRunner::class => [ WikibaseClientHookRunner::class ];
	}

}
