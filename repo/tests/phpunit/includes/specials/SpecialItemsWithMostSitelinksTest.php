<?php

namespace Wikibase\Test;

use Language;
use Wikibase\Repo\Specials\SpecialItemsWithMostSitelinks;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemsWithMostSitelinks
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Maarten Dammers
 */
class SpecialItemsWithMostSitelinksTest extends SpecialPageTestBase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'qqx' )
		) );
	}

	protected function newSpecialPage() {
		return new SpecialItemsWithMostSitelinks();
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertInternalType( 'string', $output );
		$this->assertContains( 'itemswithmostsitelinks-summary', $output );
		$this->assertContains( '<div class="mw-spcontent">', $output );
		$this->assertContains( 'wikibase-nsitelinks', $output );
	}

}
