<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use MediaWiki\Tests\Structure\BundleSizeTestBase;

/**
 * Track the size of ResourceLoader modules that repo or client load on page load.
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 * @coversNothing
 */
class WikibaseBundleSizeTest extends BundleSizeTestBase {

	protected function setUp(): void {
		parent::setUp();

		$this->markTestSkippedIfExtensionNotLoaded( 'WikibaseRepository' );
		$this->markTestSkippedIfExtensionNotLoaded( 'WikibaseClient' );
	}

	/** @inheritDoc */
	public static function getBundleSizeConfigData(): string {
		return dirname( __DIR__, 3 ) . '/bundlesize.config.json';
	}

}
