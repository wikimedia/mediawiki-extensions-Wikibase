<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess\Tests\Unit\Hooks;

use MediaWiki\Tests\HookContainer\HookRunnerTestBase;
use Wikibase\DataAccess\Hooks\WikibaseDataAccessHookRunner;

/**
 * @covers \Wikibase\DataAccess\Hooks\WikibaseDataAccessHookRunner
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseDataAccessHookRunnerTest extends HookRunnerTestBase {

	public static function provideHookRunners(): iterable {
		yield WikibaseDataAccessHookRunner::class => [ WikibaseDataAccessHookRunner::class ];
	}

}
