<?php

namespace Wikibase\Client\Tests\Maintenance;

use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Title;
use Wikibase\Client\Maintenance\PopulateUnexpectedUnconnectedPagePageProp;
use Wikibase\Client\NamespaceChecker;
use Wikimedia\Rdbms\IDatabase;

// files in maintenance/ are not autoloaded, so load explicitly
require_once __DIR__ . '/../../../../maintenance/PopulateUnexpectedUnconnectedPagePageProp.php';

/**
 * @covers \Wikibase\Client\Maintenance\PopulateUnexpectedUnconnectedPagePageProp
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class PopulateUnexpectedUnconnectedPagePagePropTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return PopulateUnexpectedUnconnectedPagePageProp::class;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->overrideMwServices(
			null,
			[
				'WikibaseClient.NamespaceChecker' => function() {
					return new NamespaceChecker( [], [ $this->getDefaultWikitextNS() ] );
				},
			]
		);

		$this->tablesUsed[] = 'page_props';
	}

	public function addDBDataOnce() {
		// Remove old stray pages.
		$this->db->delete( 'page', IDatabase::ALL_ROWS, __METHOD__ );

		$titles = [];
		for ( $i = 1; $i < 5; $i++ ) {
			$titles[$i] = Title::makeTitle( $this->getDefaultWikitextNS(), "PopulateUnexpectedUnconnectedPagePagePropTest-$i" );
		}
		$titles[] = Title::makeTitle( NS_TALK, 'Page outside of a Wikibase NS' );
		$titles[101] = Title::makeTitle( $this->getDefaultWikitextNS(), 'PopulateUnexpectedUnconnectedPagePagePropTest-High-Page-id' );
		foreach ( $titles as $pageId => $title ) {
			$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
			$page->insertOn( $this->db, $pageId );
		}
	}

	public function testExecute(): void {
		$namespaceString = strval( $this->getDefaultWikitextNS() );
		$namespaceFloat = $this->getDefaultWikitextNS() + 0.0;

		$prior = [
			[ '3', 'expectedUnconnectedPage', '', 0.0 ],
			[ '2', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			[ '4', 'unrelated-page-prop', '', 0.0 ],
		];
		$expected = [
			[ '1', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			[ '2', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			[ '3', 'expectedUnconnectedPage', '', 0.0 ],
			[ '4', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
			[ '4', 'unrelated-page-prop', '', 0.0 ],
			[ '101', 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
		];
		$this->insertPageProps( $prior );

		$this->maintenance->loadWithArgv( [] );
		$this->maintenance->execute();

		$this->assertSelect(
			'page_props',
			[ 'pp_page', 'pp_propname', 'pp_value', 'pp_sortkey' ],
			IDatabase::ALL_ROWS,
			$expected
		);
	}

	public function testExecute_paging(): void {
		$namespaceInt = $this->getDefaultWikitextNS();
		$namespaceString = strval( $namespaceInt );
		$namespaceFloat = $namespaceInt + 0.0;

		$this->insertPageProps( [
			[ 1, 'expectedUnconnectedPage', '', 0.0 ],
			// 2 is unexpected unconnected
			[ 3, 'wikibase_item', '', 0.0 ],
			// 4 is unexpected unconnected
			// 101 is unexpected unconnected
		] );

		$argv = [ '--batch-size', 1, '--first-page-id', 3, '--last-page-id', 4 ];

		$this->maintenance->loadWithArgv( $argv );
		$this->maintenance->execute();
		$this->assertSelect(
			'page_props',
			[ 'pp_page', 'pp_propname', 'pp_value', 'pp_sortkey' ],
			IDatabase::ALL_ROWS,
			[
				[ 1, 'expectedUnconnectedPage', '', 0.0 ],
				// 2 is excluded
				[ 3, 'wikibase_item', '', 0.0 ],
				[ 4, 'unexpectedUnconnectedPage', $namespaceString, $namespaceFloat ],
				// 101 is excluded
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
