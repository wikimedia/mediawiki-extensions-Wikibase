<?php

namespace Wikibase\Test;

use Language;
use Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks
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
 * @author Adam Shorland
 */
class SpecialItemsWithoutSitelinksTest extends SpecialPageTestBase {

	public function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'qqx' )
		) );
	}

	protected function newSpecialPage() {
		return new SpecialItemsWithoutSitelinks();
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertInternalType( 'string', $output );
		$this->assertContains( 'wikibase-itemswithoutsitelinks-summary', $output );
		$this->assertContains( '<div class="mw-spcontent">', $output );

		// There was a bug in SpecialWikibaseQueryPage::showQuery() adding an unnecesarry
		// Html::closeElement( 'div' ) when the results is empty.
		$this->assertNotContains( '</div></div>', $output );
	}

}
