<?php

namespace Wikibase\Repo\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks;

/**
 * @covers \Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks
 * @covers \Wikibase\Repo\Specials\SpecialWikibaseQueryPage
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class SpecialItemsWithoutSitelinksTest extends SpecialPageTestBase {

	protected function setUp(): void {
		parent::setUp();

		$this->setContentLang( 'qqx' );
	}

	protected function newSpecialPage() {
		return new SpecialItemsWithoutSitelinks();
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		list( $output, ) = $this->executeSpecialPage( '' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'wikibase-itemswithoutsitelinks-summary', $output );
		$this->assertStringContainsString( '<div class="mw-spcontent">', $output );

		// There was a bug in SpecialWikibaseQueryPage::showQuery() adding an unnecessary
		// Html::closeElement( 'div' ) when the results is empty.
		$this->assertStringNotContainsString( '</div></div>', $output );
	}

}
