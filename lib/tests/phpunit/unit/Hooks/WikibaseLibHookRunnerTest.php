<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Unit\Hooks;

use MediaWiki\Tests\HookContainer\HookRunnerTestBase;
use Wikibase\Lib\Hooks\WikibaseLibHookRunner;

/**
 * @covers \Wikibase\Lib\Hooks\WikibaseLibHookRunner
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLibHookRunnerTest extends HookRunnerTestBase {

	public static function provideHookRunners(): iterable {
		yield WikibaseLibHookRunner::class => [ WikibaseLibHookRunner::class ];
	}

}
