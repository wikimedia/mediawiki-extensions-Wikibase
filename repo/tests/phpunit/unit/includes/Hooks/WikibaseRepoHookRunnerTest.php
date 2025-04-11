<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\Hooks;

use MediaWiki\Hook\EditFilterMergedContentHook;
use MediaWiki\HookContainer\HookContainer;
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

	/**
	 * Wikibase passes a seventh argument into the EditFilterMergedContent hook (T288885).
	 * In order to make {@link HookRunnerTestBase} happy,
	 * this means that {@link WikibaseRepoHookRunner} needs to implement our own hook interface
	 * {@link \Wikibase\Repo\Hooks\WikibaseEditFilterMergedContentHook WikibaseEditFilterMergedContentHook},
	 * which cannot extend core’s {@link EditFilterMergedContentHook} interface,
	 * nor can {@link WikibaseRepoHookRunner} implement {@link EditFilterMergedContentHook}.
	 * This test checks that the two interfaces are still compatible,
	 * by declaring and creating a subclass which does implement the core interface
	 * in addition to the Wikibase version.
	 */
	public function testCompatibleWithCoreEditFilterMergedContentHook(): void {
		$hook = new class( $this->createStub( HookContainer::class ) )
			extends WikibaseRepoHookRunner
			implements EditFilterMergedContentHook
{
		};
		$this->addToAssertionCount( 1 );
	}

}
