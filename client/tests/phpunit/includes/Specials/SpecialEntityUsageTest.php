<?php

namespace Wikibase\Client\Tests\Specials;

use FakeResultWrapper;
use RequestContext;
use SpecialPageFactory;
use SpecialPageTestBase;
use Title;
use Wikibase\Client\Specials\SpecialEntityUsage;
use WikiPage;

/**
 * @covers Wikibase\Client\Specials\SpecialEntityUsage
 *
 * @group WikibaseClient
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class SpecialEntityUsageTest extends SpecialPageTestBase {

	protected function setUp() {
		$this->tablesUsed = array_unique(
			array_merge( $this->tablesUsed, [ 'page', 'wbc_entity_usage' ] )
		);
		parent::setUp();
	}

	private function setupEntityUsageFixtures() {
		$db = wfGetDB( DB_MASTER );
		$pages = [
			[
				'page_title' => 'Vienna',
				'page_namespace' => 0,
				'page_id' => 11,
			],
			[
				'page_title' => 'Berlin',
				'page_namespace' => 0,
				'page_id' => 22,
			],
		];
		$entityUsage = [
			[
				'eu_page_id' => 11,
				'eu_entity_id' => 'Q3',
				'eu_aspect' => 'S'
			],
			[
				'eu_page_id' => 11,
				'eu_entity_id' => 'Q3',
				'eu_aspect' => 'O'
			],
			[
				'eu_page_id' => 22,
				'eu_entity_id' => 'Q3',
				'eu_aspect' => 'L.de'
			],
		];
		foreach ( $pages as $pageData ) {
			$page = WikiPage::factory( Title::makeTitle( $pageData['page_namespace'], $pageData['page_title'] ) );
			$page->insertOn( $db, $pageData['page_id'] );
		}
		foreach ( $entityUsage as $item ) {
			$db->insert(
				'wbc_entity_usage',
				$item
			);
		}
	}

	/**
	 * @return SpecialEntityUsage
	 */
	protected function newSpecialPage() {
		return new SpecialEntityUsage();
	}

	public function testExecuteWithValidParam() {
		if ( wfGetDB( DB_SLAVE )->getType() === 'mysql' ) {
			$this->markTestSkipped( 'MySQL does not allow selfjoins on temporary tables' );
		}

		$this->setupEntityUsageFixtures();

		$aspectsVienna = [
			wfMessage( 'wikibase-pageinfo-entity-usage-O' )->parse(),
			wfMessage( 'wikibase-pageinfo-entity-usage-S' )->parse(),
		];
		$aspectsBerlin = [
			wfMessage( 'wikibase-pageinfo-entity-usage-L', 'de' )->parse(),
		];
		$aspectsAll = array_merge( $aspectsVienna, $aspectsBerlin );
		$lang = RequestContext::getMain()->getLanguage();
		$aspectListVienna = $lang->commaList( $aspectsVienna );
		$aspectListBerlin = $lang->commaList( $aspectsBerlin );
		$aspectListAll = $lang->commaList( $aspectsAll );

		$expected = SpecialPageFactory::getLocalNameFor( 'EntityUsage', 'Q3' );

		list( $result, ) = $this->executeSpecialPage( 'Q3' );

		$this->assertNotContains( '<p class="error"', $result );
		$this->assertContains( $expected, $result );

		$this->assertContains( 'Vienna', $result );
		$this->assertContains( 'Berlin', $result );
		$this->assertContains( $aspectListVienna, $result );
		$this->assertContains( $aspectListBerlin, $result );

		$this->assertNotContains( $aspectListAll, $result );
	}

	public function testExecuteWithInvalidParam() {
		list( $result, ) = $this->executeSpecialPage( 'FooBar' );

		$this->assertContains( '<p class="error"', $result );
		$this->assertContains(
			wfMessage( 'wikibase-entityusage-invalid-id', 'FooBar' )->text(),
			$result
		);
	}

}
