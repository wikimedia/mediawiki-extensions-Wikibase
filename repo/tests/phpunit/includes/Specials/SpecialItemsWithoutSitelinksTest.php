<?php

namespace Wikibase\Repo\Tests\Specials;

use Language;
use SpecialPageTestBase;
use Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks
 * @covers Wikibase\Repo\Specials\SpecialWikibaseQueryPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SpecialItemsWithoutSitelinksTest extends SpecialPageTestBase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( [
			'wgContLang' => Language::factory( 'qqx' )
		] );
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
