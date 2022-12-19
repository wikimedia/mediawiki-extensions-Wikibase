<?php

namespace Wikibase\Client\Tests\Integration\Specials;

use LanguageQqx;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\MediaWikiServices;
use SpecialPageTestBase;
use Title;
use TrivialLanguageConverter;
use Wikibase\Client\Specials\SpecialEntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \Wikibase\Client\Specials\SpecialEntityUsage
 *
 * @group WikibaseClient
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Wikibase
 * @group Database
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
			],
		];

		return new FakeResultWrapper( $rows );
	}

	/**
	 * @return SpecialEntityUsage
	 */
	protected function newSpecialPage() {
		$idParser = WikibaseClient::getEntityIdParser();
		$dbFactory = WikibaseClient::getClientDomainDbFactory();

		$specialPage = $this->getMockBuilder( SpecialEntityUsage::class )
			->setConstructorArgs( [ $this->languageConverterFactory(), $dbFactory, $idParser ] )
			->onlyMethods( [ 'reallyDoQuery' ] )
			->getMock();

		$specialPage->method( 'reallyDoQuery' )
			->willReturn( $this->reallyDoQueryMock() );

		return $specialPage;
	}

	private function languageConverterFactory(): LanguageConverterFactory {
		$languageConverterFactory = $this
			->getMockBuilder( LanguageConverterFactory::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getLanguageConverter' ] )
			->getMock();
		$languageConverterFactory->method( 'getLanguageConverter' )
			->willReturn( new TrivialLanguageConverter( new LanguageQqx() ) );

		return $languageConverterFactory;
	}

	public function testExecuteWithValidParam() {
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
		list( $result, ) = $this->executeSpecialPage( 'Q3', null, $lang );
		$aspectsTehran = [
			'(wikibase-pageinfo-entity-usage-O: )',
			'(wikibase-pageinfo-entity-usage-L-with-modifier: fa)',
		];
		$aspectsAthena = [
			'(wikibase-pageinfo-entity-usage-S: )',
		];

		$aspectListTehran = $lang->commaList( $aspectsTehran );
		$aspectListAthena = $lang->commaList( $aspectsAthena );

		$this->assertStringContainsString( 'Tehran', $result );
		$this->assertStringContainsString( 'Athena', $result );
		$this->assertStringNotContainsString( '<p class="error"', $result );
		$expected = MediaWikiServices::getInstance()->getSpecialPageFactory()
			->getLocalNameFor( 'EntityUsage', 'Q3' );
		$this->assertStringContainsString( $expected, $result );
		$this->assertStringContainsString( '(colon-separator)' . $aspectListTehran . '</li>', $result );
		$this->assertStringContainsString( '(colon-separator)' . $aspectListAthena . '</li>', $result );
	}

	public function testExecuteWithInvalidParam() {
		list( $result, ) = $this->executeSpecialPage( 'FooBar', null, 'qqx' );

		$this->assertStringContainsString( '<p class="error"', $result );
		$this->assertStringContainsString( '(wikibase-entityusage-invalid-id: FooBar)', $result );
	}

	public function testReallyDoQuery() {
		if ( $this->db->getType() === 'mysql' &&
			$this->usesTemporaryTables()
		) {
			$this->markTestSkipped( 'MySQL does not allow self-joins on temporary tables' );
		}
		$this->addReallyDoQueryData();

		$special = new SpecialEntityUsage(
			$this->languageConverterFactory(),
			WikibaseClient::getClientDomainDbFactory(),
			WikibaseClient::getEntityIdParser()
		);
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
				$row->eu_page_id,
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
		$dump = [
			'page' => [
				[
					'page_namespace' => NS_MAIN,
					'page_title' => 'Vienna',
					'page_id' => 11,
				],
				[
					'page_namespace' => NS_MAIN,
					'page_title' => 'Berlin',
					'page_id' => 22,
				],
			],
			'wbc_entity_usage' => [
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'S',
				],
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'O',
				],
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q4',
					'eu_aspect' => 'L.en',
				],
				[
					'eu_page_id' => 22,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'L.de',
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$this->db->delete( $table, '*' );

			foreach ( $rows as $row ) {
				if ( $table === 'page' ) {
					$title = Title::makeTitle( $row['page_namespace'], $row['page_title'] );
					$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
					$page->insertOn( $this->db, $row['page_id'] );
				} else {
					$this->db->insert( $table, $row );
				}
			}
		}
	}

}
