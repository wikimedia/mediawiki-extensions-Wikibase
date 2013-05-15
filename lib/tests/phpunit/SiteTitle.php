<?php

namespace Wikibase\Test;

/**
 * Tests for the Wikibase\SiteTitle class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class SiteTitleTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseLib
	 * @dataProvider providePages
	 */
	public function testNomalization( $pages, $slow = true ) {
		\Wikibase\Settings::setSetting( 'normalizeItemByTitlePageNames', $slow );

		foreach( $pages as $page ) {
			$siteTitle = new \Wikibase\SiteTitle(
				$page['site'],
				$page['pageName']
			);

			$this->assertEquals(
				$page['normalized'],
				$siteTitle->normalizePageName()
			);
		}
	}

	/**
	 * @group WikibaseLib
	 * @dataProvider providePagesFast
	 */
	public function testFastNomalization( $pages ) {
		$this->testNomalization( $pages, false );
	}

	public function providePagesFast() {
		return array(
			array(
				'site' => 'enwiki',
				'pageName' => 'Wikidata',
				'normalized' => 'Wikidata'
			), array(
				'site' => 'pflwiki',
				'pageName' => 'Foo_Bar Page',
				'normalized' => 'Foo Bar Page'
			), array(
				'site' => \SiteSQLStore::newInstance()->getSite( 'hewiki' ),
				'pageName' => 'עמוד_ראשי',
				'normalized' => 'עמוד ראשי'
			),
		);
	}

	public function providePages() {
		return array(
			array(
				'site' => 'enwiki',
				'pageName' => 'Wikidata',
				'normalized' => 'Wikidata'
			), array(
				'site' => 'pflwiki',
				'pageName' => 'Foo_Bar Page',
				'normalized' => null // Doesn't exist
			), array(
				'site' => \SiteSQLStore::newInstance()->getSite( 'hewiki' ),
				'pageName' => 'עמוד_ראשי',
				'normalized' => 'עמוד ראשי'
			), array(
				'site' => 'unknownSiteId',
				'pageName' => 'blerg',
				'normalized' => null
			), array(
				'site' => 'enwiki',
				'pageName' => 'MainPage',
				'normalized' => 'Main Page' // Redirect
			),
		);
	}
}
