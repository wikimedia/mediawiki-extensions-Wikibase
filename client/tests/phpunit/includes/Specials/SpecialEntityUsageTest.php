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

	public function reallyDoQueryMock() {
		$rows = [
			(object)[
				'value' => 11,
				'namespace' => 0,
				'title' => 'Tehran',
				'aspects' => 'O|L.fa',
				'eu_page_id' => 11,
				'eu_entity_id' => 'Q3',
			],
			(object)[
				'value' => 22,
				'namespace' => 0,
				'title' => 'Athena',
				'aspects' => 'S',
				'eu_page_id' => 22,
				'eu_entity_id' => 'Q3',
			]
		];

		return new FakeResultWrapper( $rows );
	}

	/**
	 * @return SpecialEntityUsage
	 */
	protected function newSpecialPage() {
		$specialPage = $this->getMockBuilder( SpecialEntityUsage::class )
			->setMethods( [ 'reallyDoQuery' ] )
			->getMock();

		$specialPage->expects( $this->any() )
			->method( 'reallyDoQuery' )
			->will( $this->returnValue( $this->reallyDoQueryMock() ) );

		return $specialPage;
	}

	public function testExecuteWithValidParam() {
		list( $result, ) = $this->executeSpecialPage( 'Q3' );
		$aspectsTehran = [
			wfMessage( 'wikibase-pageinfo-entity-usage-O' )->parse(),
			wfMessage( 'wikibase-pageinfo-entity-usage-L', 'fa' )->parse(),
		];
		$aspectsAthena = [
			wfMessage( 'wikibase-pageinfo-entity-usage-S' )->parse(),
		];
		$aspectsAll = array_merge( $aspectsTehran, $aspectsAthena );

		$lang = RequestContext::getMain()->getLanguage();
		$aspectListTehran = $lang->commaList( $aspectsTehran );
		$aspectListAthena = $lang->commaList( $aspectsAthena );
		$aspectListAll = $lang->commaList( $aspectsAll );

		$this->assertContains( 'Tehran', $result );
		$this->assertContains( 'Athena', $result );
		$this->assertNotContains( '<p class="error"', $result );
		$expected = SpecialPageFactory::getLocalNameFor( 'EntityUsage', 'Q3' );
		$this->assertContains( $expected, $result );
		$this->assertContains( $aspectListTehran, $result );
		$this->assertContains( $aspectListAthena, $result );
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

	public function testReallyDoQuery() {
		if ( wfGetDB( DB_SLAVE )->getType() === 'mysql' ) {
			$this->markTestSkipped( 'MySQL does not allow selfjoins on temporary tables' );
		}
		$this->addReallyDoQueryData();

		$special = new SpecialEntityUsage();
		$special->prepareParams( 'Q3' );
		$res = $special->reallyDoQuery( 50 );
		$values = [];

		foreach ( $res as $row ) {
			$values[] = [
				$row->value,
				$row->namespace,
				$row->title,
				$row->aspects,
				$row->eu_page_id
			];
		}

		$expected = [
			[ '22', '0', 'Berlin', 'L.de', '22' ],
			[ '11', '0', 'Vienna', 'S|O', '11' ],
		];

		$this->assertSame( $expected, $values );
	}

	private function addReallyDoQueryData() {
		$db = wfGetDB( DB_MASTER );
		$dump = [
			'page' => [
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
			],
			'wbc_entity_usage' => [
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
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q4',
					'eu_aspect' => 'L.en'
				],
				[
					'eu_page_id' => 22,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'L.de'
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$db->delete( $table, '*' );

			foreach ( $rows as $row ) {
				if ( $table === 'page' ) {
					$title = Title::newFromText( $row['page_title'], $row['page_namespace'] );
					$page = WikiPage::factory( $title );
					$page->insertOn( $db, $row['page_id'] );
				} else {
					$db->insert( $table, $row );
				}
			}
		}
	}

}
