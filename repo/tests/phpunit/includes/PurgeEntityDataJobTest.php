<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use MediaWiki\Cache\HTMLCacheUpdater;
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

	public function addDBData() {
		$defaultArchiveRow = [
			'ar_comment_id' => 1,
			'ar_actor' => 1,
			'ar_timestamp' => $this->getDb()->timestamp(),
		];
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'archive' )
			// relevant rows: the corresponding URLs should be purged
			->row( $defaultArchiveRow + [
				'ar_id' => 1,
				'ar_namespace' => 0,
				'ar_title' => 'Q123',
				'ar_page_id' => 123,
				'ar_rev_id' => 1234,
			] )
			->row( $defaultArchiveRow + [
				'ar_id' => 2,
				'ar_namespace' => 0,
				'ar_title' => 'Q123',
				'ar_page_id' => 123,
				'ar_rev_id' => 1235,
			] )
			// irrelevant row: wrong namespace
			->row( $defaultArchiveRow + [
				'ar_id' => 3,
				'ar_namespace' => 1,
				'ar_title' => 'Q123',
				'ar_page_id' => 123,
				'ar_rev_id' => 1236,
			] )
			// irrelevant row: wrong title
			->row( $defaultArchiveRow + [
				'ar_id' => 4,
				'ar_namespace' => 0,
				'ar_title' => 'Q124',
				'ar_page_id' => 123,
				'ar_rev_id' => 1237,
			] )
			// irrelevant row: wrong page ID
			// (possible if the same namespace+title was deleted several times)
			->row( $defaultArchiveRow + [
				'ar_id' => 5,
				'ar_namespace' => 1,
				'ar_title' => 'Q123',
				'ar_page_id' => 124,
				'ar_rev_id' => 1238,
			] )
			->caller( __METHOD__ )
			->execute();
	}

	public function testRun_purgesPotentiallyCachedUrls() {
		$itemId = new ItemId( 'Q123' );

		$entityDataUriManager = $this->createMock( EntityDataUriManager::class );
		$returnURLsByRevision = [
			0 => '/Special:EntityData/Q123',
			1234 => '/Special:EntityData/Q123?revision=1234',
			1235 => '/Special:EntityData/Q123?revision=1235',
		];
		$entityDataUriManager->expects( $this->exactly( 3 ) )
			->method( 'getPotentiallyCachedUrls' )
			->willReturnCallback( function ( $id, $revision ) use ( $itemId, &$returnURLsByRevision ) {
				$this->assertEquals( $itemId, $id );
				$this->assertArrayHasKey( $revision, $returnURLsByRevision );
				$ret = $returnURLsByRevision[$revision];
				unset( $returnURLsByRevision[$revision] );
				return [ $ret ];
			} );

		$htmlCacheUpdater = $this->createMock( HTMLCacheUpdater::class );
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

		$htmlCacheUpdater = $this->createMock( HTMLCacheUpdater::class );
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
