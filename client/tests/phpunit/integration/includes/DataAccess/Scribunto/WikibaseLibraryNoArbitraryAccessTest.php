<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

/**
 * Verifies that arbitrary data access doesn't work, if it's disabled.
 *
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLibraryNoArbitraryAccessTest extends WikibaseLibraryTestCase {

	/** @inheritDoc */
	protected static $moduleName = 'WikibaseLibraryNoArbitraryAccessTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseLibraryNoArbitraryAccessTests' => __DIR__ . '/WikibaseLibraryNoArbitraryAccessTests.lua',
		];
	}

	/**
	 * Whether to allow arbitrary data access or not
	 *
	 * @return bool
	 */
	protected static function allowArbitraryDataAccess() {
		return false;
	}

}
