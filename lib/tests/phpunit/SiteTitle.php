<?php

namespace Wikibase\Test;

use Wikibase\SiteTitle;
use Wikibase\Settings;

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
	public function testNomalization( $site, $pageName, $normalized, $slow = true ) {
		Settings::singleton()->setSetting( 'normalizeItemByTitlePageNames', $slow );

		$siteTitle = new SiteTitle(
			$site,
			$pageName
		);

		$this->assertEquals(
			$normalized,
			$siteTitle->normalizePageName()
		);
	}

	/**
	 * @group WikibaseLib
	 * @dataProvider providePagesFast
	 */
	public function testFastNomalization( $site, $pageName, $normalized ) {
		$this->testNomalization( $site, $pageName, $normalized, false );
	}

	public function providePagesFast() {
		return array(
			array(
				'site' => 'enwiki',
				'pageName' => 'Wikidata',
				'normalized' => 'Wikidata'
			),
			array(
				'site' => 'pflwiki',
				'pageName' => 'Foo_Bar Page',
				'normalized' => 'Foo Bar Page'
			),
			array(
				'site' => 'hewiki',
				'pageName' => 'עמוד_ראשי',
				'normalized' => 'עמוד ראשי'
			),
		);
	}

	public function providePages() {
		return array(
			array(
				'site' => 'enwiki',
				'pageName' => 'wikidata',
				'normalized' => 'Wikidata'
			),
			/*
				This doesn't actually work cause we don't really call external sites
				in unit tests
				array(
					'site' => 'pflwiki',
					'pageName' => 'Foo_Bar Page',
					'normalized' => null // Doesn't exist
				),
			*/
			array(
				'site' => 'hewiki',
				'pageName' => 'עמוד_ראשי',
				'normalized' => 'עמוד ראשי'
			),
			array(
				'site' => 'unknownSiteId',
				'pageName' => 'blerg',
				'normalized' => null
			),
			/*
				This doesn't actually work cause we don't really call external sites
				in unit tests
				array(
					'site' => 'enwiki',
					'pageName' => 'MainPage',
					'normalized' => 'Main Page' // Redirect
				)
			*/
		);
	}
}
