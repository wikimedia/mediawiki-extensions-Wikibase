<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use HtmlCacheUpdater;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\PurgeEntityDataJob;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\PurgeEntityDataJob
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PurgeEntityDataJobTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'archive';
	}

	public function addDBData() {
		$this->db->truncate( 'archive', __METHOD__ ); // T265033
		$defaultArchiveRow = [
			'ar_comment_id' => 1,
			'ar_actor' => 1,
			'ar_timestamp' => $this->db->timestamp(),
		];
		$this->db->insert( 'archive', [
			// relevant rows: the corresponding URLs should be purged
			$defaultArchiveRow + [
				'ar_id' => 1,
				'ar_namespace' => 0,
				'ar_title' => 'Q123',
				'ar_page_id' => 123,
				'ar_rev_id' => 1234,
			],
			$defaultArchiveRow + [
				'ar_id' => 2,
				'ar_namespace' => 0,
				'ar_title' => 'Q123',
				'ar_page_id' => 123,
				'ar_rev_id' => 1235,
			],
			// irrelevant row: wrong namespace
			$defaultArchiveRow + [
				'ar_id' => 3,
				'ar_namespace' => 1,
				'ar_title' => 'Q123',
				'ar_page_id' => 123,
				'ar_rev_id' => 1236,
			],
			// irrelevant row: wrong title
			$defaultArchiveRow + [
				'ar_id' => 4,
				'ar_namespace' => 0,
				'ar_title' => 'Q124',
				'ar_page_id' => 123,
				'ar_rev_id' => 1237,
			],
			// irrelevant row: wrong page ID
			// (possible if the same namespace+title was deleted several times)
			$defaultArchiveRow + [
				'ar_id' => 5,
				'ar_namespace' => 1,
				'ar_title' => 'Q123',
				'ar_page_id' => 124,
				'ar_rev_id' => 1238,
			],
		], __METHOD__ );
	}

	public function testRun_purgesPotentiallyCachedUrls() {
		$itemId = new ItemId( 'Q123' );

		$entityDataUriManager = $this->createMock( EntityDataUriManager::class );
		$entityDataUriManager->expects( $this->exactly( 3 ) )
			->method( 'getPotentiallyCachedUrls' )
			->withConsecutive(
				[ $itemId ],
				[ $itemId, 1234 ],
				[ $itemId, 1235 ]
			)
			->willReturnOnConsecutiveCalls(
				[ '/Special:EntityData/Q123' ],
				[ '/Special:EntityData/Q123?revision=1234' ],
				[ '/Special:EntityData/Q123?revision=1235' ]
			);

		$htmlCacheUpdater = $this->createMock( HtmlCacheUpdater::class );
		$htmlCacheUpdater->expects( $this->once() )
			->method( 'purgeUrls' )
			->with( [
				'/Special:EntityData/Q123',
				'/Special:EntityData/Q123?revision=1234',
				'/Special:EntityData/Q123?revision=1235',
			] );

		$job = new PurgeEntityDataJob(
			new ItemIdParser(),
			$entityDataUriManager,
			$this->getRepoDb(),
			$htmlCacheUpdater,
			1,
			[
				'namespace' => 0,
				'title' => 'Q123',
				'pageId' => 123,
				'entityId' => 'Q123',
			]
		);

		$job->run();
	}

	public function testRun_doesNotCallPurgeWithEmptyUrls() {
		$entityDataUriManager = $this->createMock( EntityDataUriManager::class );
		$entityDataUriManager->method( 'getPotentiallyCachedUrls' )
			->willReturn( [] );

		$htmlCacheUpdater = $this->createMock( HtmlCacheUpdater::class );
		$htmlCacheUpdater->expects( $this->never() )
			->method( 'purgeUrls' );

		$job = new PurgeEntityDataJob(
			new ItemIdParser(),
			$entityDataUriManager,
			$this->getRepoDb(),
			$htmlCacheUpdater,
			100,
			[
				'namespace' => 0,
				'title' => 'Q456',
				'pageId' => 456,
				'entityId' => 'Q456',
			]
		);

		$job->run();
	}

	private function getRepoDb(): RepoDomainDb {
		return WikibaseRepo::getRepoDomainDbFactory()->newRepoDb();
	}

}
