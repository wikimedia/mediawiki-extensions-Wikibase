<?php

namespace Wikibase\Test;

use Language;
use Wikibase\Repo\Specials\SpecialListDatatypes;

/**
 * @covers Wikibase\Repo\Specials\SpecialListDatatypes
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SpecialListDataTypesTest extends SpecialPageTestBase {

	public function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'qqx' )
		) );
	}

	protected function newSpecialPage() {
		return new SpecialListDatatypes();
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertInternalType( 'string', $output );
		$this->assertContains( 'wikibase-listdatatypes-summary', $output );
		$this->assertContains( 'wikibase-listdatatypes-intro', $output );
	}

}
