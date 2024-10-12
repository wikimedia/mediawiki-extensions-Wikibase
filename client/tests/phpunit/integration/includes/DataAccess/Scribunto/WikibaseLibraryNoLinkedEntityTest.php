<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use MediaWiki\Title\Title;

/**
 * Tests for pages that are not connected to any Item.
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
class WikibaseLibraryNoLinkedEntityTest extends WikibaseLibraryTestCase {

	/** @inheritDoc */
	protected static $moduleName = 'WikibaseLibraryNoLinkedEntityTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseLibraryNoLinkedEntityTests' => __DIR__ . '/WikibaseLibraryNoLinkedEntityTests.lua',
		];
	}

	/**
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::makeTitle( NS_MAIN, 'WikibaseClientDataAccessTest-NotLinkedWithAnyEntity' );
	}

}
