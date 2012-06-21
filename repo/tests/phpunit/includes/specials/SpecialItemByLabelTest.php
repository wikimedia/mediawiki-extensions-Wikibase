<?php

namespace Wikibase\Test;
use Wikibase\ChangeNotifier as ChangeNotifier;
use Wikibase\Change as Change;
use Wikibase\Changes as Changes;
use Wikibase\ItemChange as ItemChange;

/**
 * Tests for the SpecialItemByLabel class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group SpecialPage
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SpecialItemByLabelTest extends \MediaWikiTestCase {

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