<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseRepoWbui2025InitResourceDependenciesHook" to
 * register handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoWbui2025InitResourceDependenciesHook {

	/**
	 * This hook receives the array of dependencies for the Wbui2025 initialisation ResourceLoader
	 * module. Hook implementors can add additional dependencies to be loaded when the feature is
	 * loaded.
	 *
	 * @param array[] &$dependencies
	 */
	public function onWikibaseRepoWbui2025InitResourceDependenciesHook( array &$dependencies ): void;

}
