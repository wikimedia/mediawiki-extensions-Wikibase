<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\Hooks;

use MediaWiki\Tests\HookContainer\HookRunnerTestBase;
use Wikibase\Repo\Hooks\WikibaseRepoHookRunner;

/**
 * @covers \Wikibase\Repo\Hooks\WikibaseRepoHookRunner
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoHookRunnerTest extends HookRunnerTestBase {

	public static function provideHookRunners(): iterable {
		yield WikibaseRepoHookRunner::class => [ WikibaseRepoHookRunner::class ];
	}

}
