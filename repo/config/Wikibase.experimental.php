<?php

/**
 * This file holds registration of experimental features part of the Wikibase Repo extension.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 */

if ( !defined( 'WB_VERSION' ) || !defined( 'WB_EXPERIMENTAL_FEATURES' ) ) {
	die( 'Not an entry point.' );
}

call_user_func( function() {
	global $wgSpecialPages, $wgSpecialPageGroups, $wgHooks;

	// Register Special:MergeItems here until the merge feature works well enough Bug: 55960
	$wgSpecialPages['MergeItems'] = 'Wikibase\Repo\Specials\SpecialMergeItems';
	$wgSpecialPageGroups['MergeItems'] = 'wikibaserepo';

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.3
	 *
	 * @param array &$files
	 *
	 * @return boolean
	 */
	$wgHooks['UnitTestsList'][] = function( array &$files ) {
		// @codeCoverageIgnoreStart
		$testFiles = array(
		);

		foreach ( $testFiles as $file ) {
			$files[] = __DIR__ . '/../tests/phpunit/includes/' . $file . 'Test.php';
		}

		return true;
		// @codeCoverageIgnoreEnd
	};

} );
