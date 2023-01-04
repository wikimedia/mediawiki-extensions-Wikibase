<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use Title;

/**
 * Tests for pages that are not connected to any Item.
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
class Scribunto_LuaWikibaseLibraryNoLinkedEntityTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLibraryNoLinkedEntityTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLibraryNoLinkedEntityTests' => __DIR__ . '/LuaWikibaseLibraryNoLinkedEntityTests.lua',
		];
	}

	/**
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::makeTitle( NS_MAIN, 'WikibaseClientDataAccessTest-NotLinkedWithAnyEntity' );
	}

}
