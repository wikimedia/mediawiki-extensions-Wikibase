<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Store\Sql;

use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\Store\Sql\UnexpectedUnconnectedPagePrimer;
use Wikibase\Client\WikibaseClient;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Client\Store\Sql\UnexpectedUnconnectedPagePrimer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class UnexpectedUnconnectedPagePrimerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		$this->tablesUsed[] = 'page_props';

		parent::setUp();
	}

	public function addDBDataOnce() {
		// Remove old stray pages.
		$this->db->delete( 'page', IDatabase::ALL_ROWS, __METHOD__ );

		$titles = [];
		for ( $i = 1; $i < 5; $i++ ) {
			$titles[$i] = Title::makeTitle( $this->getDefaultWikitextNS(), "UnexpectedUnconnectedPagePrimerTest-$i" );
		}
		$titles[] = Title::makeTitle( NS_TALK, 'Page outside of a Wikibase NS' );
		$titles[101] = Title::makeTitle( $this->getDefaultWikitextNS(), 'UnexpectedUnconnectedPagePrimerTest-High-Page-id' );

		foreach ( $titles as $pageId => $title ) {
			$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
			$page->insertOn( $this->db, $pageId );
		}
	}

	public function insertPagePropProvider(): array {
		$namespaceString = strval( -$this->getDefaultWikitextNS() );
		$namespaceFloat = -$this->getDefaultWikitextNS() + 0.0;
		$convertToLegacy = function( $row ) {
			if ( $row[1] === 'unexpectedUnconnectedPage' ) {
				// The value doesn't matter and to make sure we actually need a change,
				// don't use -$this->getDefaultWikitextNS() (as that could be 0).
				$row[3] = 5.0;
			}
			return $row;
		};

		$nothingToDo = [
			[ '1', 'expectedUnconnectedPage', '', 0.0 ],
			[ '2', 'expectedUnconnectedPage', '', 0.0 ],
			[ '2', 'unrelated-page-prop', '', 0.0 ],
			[ '3', 'wikibase_item', '', 0.0 ],
			[ '4', 'expectedUnconnectedPage', '', 1.0 ],
			[ '101', 'expectedUnconnectedPage', '', 0.0 ],
		];

		$oneUnconnectedPrior = [
			[ '1', 'expectedUnconnectedPage', '', 0.0 ],
			[ '3', 'unrelated-page-prop', '1', 1.0 ],
			[ '3', 'wikibase_item', 'Q12', 0.0 ],
			[ '4', 'expectedUnconnectedPage', '', 0.0 ],
			[ '101', 'expectedUnconnectedPage', '', 0.0 ],
		];
		$oneUnconnectedExpected = [
			[ '1', 'expectedUnconnectedPage', '', 0.0 ],
			[ '2', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			[ '3', 'unrelated-page-prop', '1', 1.0 ],
			[ '3', 'wikibase_item', 'Q12', 0.0 ],
			[ '4', 'expectedUnconnectedPage', '', 0.0 ],
			[ '101', 'expectedUnconnectedPage', '', 0.0 ],
		];

		$manyUnconnectedPrior = [
			[ '3', 'expectedUnconnectedPage', '', 0.0 ],
			[ '4', 'unrelated-page-prop', '', 0.0 ],
		];
		$manyUnconnectedExpected = [
			[ '1', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			[ '2', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			[ '3', 'expectedUnconnectedPage', '', 0.0 ],
			[ '4', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			[ '4', 'unrelated-page-prop', '', 0.0 ],
			[ '101', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
		];

		return [
			'Nothing to do (expectedUnconnectedPage or wikibase_item)' => [
				'expectedPageProps' => $nothingToDo,
				'priorPageProps' => $nothingToDo,
			],
			'One unexpectedly unconnected' => [
				'expectedPageProps' => $oneUnconnectedExpected,
				'priorPageProps' => $oneUnconnectedPrior,
			],
			'One unexpectedly unconnected, tiny batch size' => [
				'expectedPageProps' => $oneUnconnectedExpected,
				'priorPageProps' => $oneUnconnectedPrior,
				'batchSize' => 3,
			],
			'One unexpectedly unconnected, tiny batch size, tiny batch size multiplicator' => [
				'expectedPageProps' => $oneUnconnectedExpected,
				'priorPageProps' => $oneUnconnectedPrior,
				'batchSize' => 2,
				'batchSizeSelectMultiplicator' => 2,
			],
			'Many unexpectedly unconnected, tiny batch size' => [
				'expectedPageProps' => $manyUnconnectedExpected,
				'priorPageProps' => $manyUnconnectedPrior,
				'batchSize' => 3,
			],
			'Many unexpectedly unconnected with legacy (positive) sortkey, tiny batch size' => [
				'expectedPageProps' => $manyUnconnectedExpected,
				'priorPageProps' => array_map( $convertToLegacy, $manyUnconnectedExpected ),
				'batchSize' => 3,
			],
			'Many unexpectedly unconnected, tiny batch size, tiny batch size multiplicator' => [
				'expectedPageProps' => $manyUnconnectedExpected,
				'priorPageProps' => $manyUnconnectedPrior,
				'batchSize' => 2,
				'batchSizeSelectMultiplicator' => 2,
			],
			'Many unexpectedly unconnected with legacy (positive) sortkey, tiny batch size, tiny batch size multiplicator' => [
				'expectedPageProps' => $manyUnconnectedExpected,
				'priorPageProps' => array_map( $convertToLegacy, $manyUnconnectedExpected ),
				'batchSize' => 2,
				'batchSizeSelectMultiplicator' => 2,
			],
			'All unexpectedly unconnected, tiny batch size, tiny batch size multiplicator' => [
				'expectedPageProps' => [
					[ '1', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
					[ '2', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
					[ '3', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
					[ '4', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
					[ '101', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
				],
				'priorPageProps' => [],
				'batchSize' => 1,
				'batchSizeSelectMultiplicator' => 2,
			],
		];
	}

	/**
	 * @dataProvider insertPagePropProvider
	 */
	public function testInsertPageProp(
		array $expectedPageProps,
		array $priorPageProps,
		int $batchSize = 1000,
		int $batchSizeSelectMultiplicator = null
	): void {
		$this->insertPageProps( $priorPageProps );

		$primer = new UnexpectedUnconnectedPagePrimer(
			WikibaseClient::getClientDomainDbFactory()->newLocalDb(),
			new NamespaceChecker( [], [ $this->getDefaultWikitextNS() ] ),
			$batchSize
		);
		if ( $batchSizeSelectMultiplicator ) {
			$primer->setBatchSizeSelectMultiplicator( $batchSizeSelectMultiplicator );
		}
		$primer->setPageProps();

		$this->assertSelect(
			'page_props',
			[ 'pp_page', 'pp_propname', 'pp_value', 'pp_sortkey' ],
			IDatabase::ALL_ROWS,
			$expectedPageProps
		);
	}

	public function testInsertPageProp_continue(): void {
		$namespaceInt = $this->getDefaultWikitextNS();
		$namespaceString = strval( $namespaceInt );
		$namespaceFloat = -$namespaceInt + 0.0;
		$this->insertPageProps( [
			[ 1, 'expectedUnconnectedPage', '', 0.0 ],
			// 2 is unexpected unconnected
			[ 3, 'wikibase_item', '', 0.0 ],
			// 4 is unexpected unconnected
		] );
		$primer = new UnexpectedUnconnectedPagePrimer(
			WikibaseClient::getClientDomainDbFactory()->newLocalDb(),
			new NamespaceChecker( [], [ $namespaceInt ] ),
			2
		);
		$primer->setBatchSizeSelectMultiplicator( 1 );

		// first run
		$primer->setMaxPageId( 2 );
		$primer->setPageProps();
		$this->assertSelect(
			'page_props',
			[ 'pp_page', 'pp_propname', 'pp_value', 'pp_sortkey' ],
			IDatabase::ALL_ROWS,
			[
				[ 1, 'expectedUnconnectedPage', '', 0.0 ],
				[ 2, 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
				[ 3, 'wikibase_item', '', 0.0 ],
				// 4 not yet processed
			]
		);

		$primer->setMinPageId( 3 );
		$primer->setMaxPageId( 4 );
		$primer->setPageProps();
		$this->assertSelect(
			'page_props',
			[ 'pp_page', 'pp_propname', 'pp_value', 'pp_sortkey' ],
			IDatabase::ALL_ROWS,
			[
				[ 1, 'expectedUnconnectedPage', '', 0.0 ],
				[ 2, 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
				[ 3, 'wikibase_item', '', 0.0 ],
				[ 4, 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			]
		);
	}

	/**
	 * Insert given page props
	 *
	 * @param array[] $pageProps Array of 'pp_page', 'pp_propname', 'pp_value', 'pp_sortkey'
	 */
	private function insertPageProps( array $pageProps ): void {
		$this->db->delete( 'page_props', IDatabase::ALL_ROWS, __METHOD__ );

		$toInsert = [];
		foreach ( $pageProps as $pageProp ) {
			$toInsert[] = [
				'pp_page' => $pageProp[0],
				'pp_propname' => $pageProp[1],
				'pp_value' => $pageProp[2],
				'pp_sortkey' => $pageProp[3],
			];
		}

		$this->db->insert( 'page_props', $toInsert, __METHOD__ );
	}

}
