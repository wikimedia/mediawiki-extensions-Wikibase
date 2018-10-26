<?php

namespace Wikibase\Client\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\Client\Store\Sql\PageRandomLookup;
use Wikimedia\Rdbms\IDatabase;

const PAGE_ID = 22;

/**
 * @covers \Wikibase\Client\Store\Sql\PageRandomLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PageRandomLookupTest extends \MediaWikiTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'page';
		parent::setUp();
	}

	private function insertPageRandom( IDatabase $db, $pageRandom ) {
		$db->insert(
			'page',
			[
				'page_id' => PAGE_ID,
				'page_random' => $pageRandom,
				'page_namespace' => 0,
				'page_title' => '',
				'page_restrictions' => '',
				'page_is_redirect' => 0,
				'page_is_new' => 0,
				'page_touched' => 0,
				'page_latest' => 0,
				'page_len' => 0
			]
		);
	}

	/**
	 * @dataProvider providerGetPageRandom
	 */
	public function testGetPageRandom( $expected, $pageRandom, $msg ) {
		$db = wfGetDB( DB_REPLICA );

		$this->insertPageRandom( $db, $pageRandom );

		$lookup = new PageRandomLookup( MediaWikiServices::getInstance()->getDBLoadBalancer() );
		$this->assertEquals( $expected, $lookup->getPageRandom( PAGE_ID ), $msg );
	}

	public function providerGetPageRandom() {
		return [
			[ null, false, 'Invalid: false' ],
			[ null, 1.1, 'Invalid: positive' ],
			[ 0, 0, 'Valid: zero' ],
			[ 0.5, 0.5, 'Valid: float' ],
			[ 1, 1, 'Valid: one' ]
		];
	}

}
