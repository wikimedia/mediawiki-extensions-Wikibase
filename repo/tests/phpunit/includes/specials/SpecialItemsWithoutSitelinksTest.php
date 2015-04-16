<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\ItemId;
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

	private function getEntityPerPage() {
		$entityPerPage = $this->getMock( 'Wikibase\Repo\Store\EntityPerPage' );

		$entityPerPage->expects( $this->once() )
			->method( 'getItemsWithoutSitelinks' )
			->will( $this->returnCallback(
				function( $siteId ) {
					if ( $siteId === null ) {
						return array(
							new ItemId( 'Q123' ),
							new ItemId( 'Q456' )
						);
					} else if ( $siteId === 'enwiki' ) {
						return array(
							new ItemId( 'Q123' ),
							new ItemId( 'Q456' ),
							new ItemId( 'Q789' )
						);
					} else if ( $siteId === 'dewiki' ) {
						return array();
					} else {
						throw new InvalidArgumentException();
					}
				}
			) );

		return $entityPerPage;
	}

	protected function newSpecialPage() {
		$specialPage = new SpecialItemsWithoutSitelinks();

		$specialPage->initServices( $this->getEntityPerPage() );

		return $specialPage;
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		list( $output, ) = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertInternalType( 'string', $output );
		$this->assertContains( 'wikibase-itemswithoutsitelinks-summary', $output );
		$this->assertContains( '<div class="mw-spcontent">', $output );

		// There was a bug in SpecialWikibaseQueryPage::showQuery() adding an unnecesarry
		// Html::closeElement( 'div' ) when the results is empty.
		$this->assertNotContains( '</div></div>', $output );

		$this->assertContains( 'Q123', $output );
		$this->assertContains( 'Q456', $output );
		$this->assertNotContains( 'Q789', $output );

		list( $output, ) = $this->executeSpecialPage( 'enwiki', null, 'qqx' );

		$this->assertContains( 'enwiki', $output );
		$this->assertContains( 'Q123', $output );
		$this->assertContains( 'Q456', $output );
		$this->assertContains( 'Q789', $output );

		list( $output, ) = $this->executeSpecialPage( 'dewiki', null, 'qqx' );

		$this->assertContains( 'dewiki', $output );
		$this->assertContains( 'specialpage-empty', $output );
	}

}
