<?php

namespace Wikibase\Test;

/**
 * Base class for testing special pages.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SpecialPageTestBase extends \MediaWikiTestCase {

	public function testConstructor() {
		$this->assertInstanceOf( 'SpecialPage', new \SpecialItemByLabel() );
	}

	public function testExecute() {
		$instance = new \SpecialItemByLabel();
		$instance->execute( '' );

		$this->assertTrue( true, 'Calling execute without any subpage value' );

		$instance = new \SpecialItemByLabel();
		$instance->execute( 'en/oHai' );

		$this->assertTrue( true, 'Calling execute with a subpage value' );
	}

}