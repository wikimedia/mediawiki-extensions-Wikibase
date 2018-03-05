<?php

namespace Wikibase\Client\Tests\Specials;

use RequestContext;
use SpecialPageFactory;
use SpecialPageTestBase;
use Title;
use Wikibase\Client\Specials\SpecialEntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikimedia\Rdbms\FakeResultWrapper;
use WikiPage;

/**
 * @covers Wikibase\Client\Specials\SpecialEntityUsage
 *
 * @group WikibaseClient
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
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
		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();

		$specialPage = $this->getMockBuilder( SpecialEntityUsage::class )
			->setConstructorArgs( [ $idParser ] )
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
			wfMessage( 'wikibase-pageinfo-entity-usage-L-with-modifier', 'fa' )->parse(),
		];
		$aspectsAthena = [
			wfMessage( 'wikibase-pageinfo-entity-usage-S' )->parse(),
		];

		$lang = RequestContext::getMain()->getLanguage();
		$aspectListTehran = $lang->commaList( $aspectsTehran );
		$aspectListAthena = $lang->commaList( $aspectsAthena );

		$this->assertContains( 'Tehran', $result );
		$this->assertContains( 'Athena', $result );
		$this->assertNotContains( '<p class="error"', $result );
		$expected = SpecialPageFactory::getLocalNameFor( 'EntityUsage', 'Q3' );
		$this->assertContains( $expected, $result );
		$this->assertContains( ': ' . $aspectListTehran . '</li>', $result );
		$this->assertContains( ': ' . $aspectListAthena . '</li>', $result );
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
		if ( wfGetDB( DB_REPLICA )->getType() === 'mysql' &&
			$this->usesTemporaryTables()
		) {
			$this->markTestSkipped( 'MySQL does not allow self-joins on temporary tables' );
		}
		$this->addReallyDoQueryData();

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$special = new SpecialEntityUsage( $wikibaseClient->getEntityIdParser() );
		$special->prepareParams( 'Q3' );
		$res = $special->reallyDoQuery( 50 );
		$values = [];
		$expectedUsages = [
			[ 'L.de' ],
			[ 'S', 'O' ],
		];

		$i = 0;
		foreach ( $res as $row ) {
			$values[] = [
				$row->value,
				$row->namespace,
				$row->title,
				$row->eu_page_id
			];

			$this->assertUsageAspects( $expectedUsages[$i++], $row->aspects );
		}

		$expected = [
			[ '22', '0', 'Berlin', '22' ],
			[ '11', '0', 'Vienna', '11' ],
		];

		$this->assertSame( $expected, $values );
	}

	private function assertUsageAspects( $expected, $aspectsString ) {
		// The aspects are not ordered, so don't take this into account when asserting
		$this->assertArrayEquals( $expected, explode( '|', $aspectsString ), false );
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
