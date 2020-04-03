<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

/**
 * Verifies that arbitrary data access doesn't work, if it's disabled.
 *
 * @covers \Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseLibraryNoArbitraryAccessTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLibraryNoArbitraryAccessTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLibraryNoArbitraryAccessTests' => __DIR__ . '/LuaWikibaseLibraryNoArbitraryAccessTests.lua',
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
