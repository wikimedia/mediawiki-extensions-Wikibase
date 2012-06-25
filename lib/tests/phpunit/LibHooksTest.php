<?php

namespace Wikibase\Test;
use Wikibase\LibHooks as LibHooks;

/**
 * Tests for the Wikibase\LibHooks class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LibHooksTest extends \MediaWikiTestCase {

	public function testOnSchemaUpdate() {
		$updater = \DatabaseUpdater::newForDb( wfGetDB( DB_MASTER ) );

		$this->assertTrue( LibHooks::onSchemaUpdate( $updater ) );
	}

	public function testRegisterUnitTests() {
		$files = array();

		$this->assertTrue( LibHooks::registerUnitTests( $files ) );

		$this->assertTrue( count( $files ) > 0 );
	}

}