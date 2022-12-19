<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\ChangeModification;

use IJobSpecification;
use JobQueueGroup;
use MediaWiki\JobQueue\JobQueueGroupFactory;
use MediaWikiIntegrationTestCase;
use MWTimestamp;
use Psr\Log\LoggerInterface;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Changes\RepoRevisionIdentifierFactory;
use Wikibase\Repo\ChangeModification\DispatchChangeDeletionNotificationJob;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @covers \Wikibase\Repo\ChangeModification\DispatchChangeDeletionNotificationJob
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DispatchChangeDeletionNotificationJobTest extends MediaWikiIntegrationTestCase {

	private $expectedLocalClientWikis;

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'archive';
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'text';

		$this->expectedLocalClientWikis = [ 'dewiki', 'enwiki', 'poolwiki' ];

		global $wgWBRepoSettings;
		$newRepoSettings = $wgWBRepoSettings;
		$newRepoSettings['localClientDatabases'] = $this->expectedLocalClientWikis;
		$newRepoSettings['deleteNotificationClientRCMaxAge'] = 1 * 24 * 3600; // (1 days)
		$this->setMwGlobals( 'wgWBRepoSettings', $newRepoSettings );
	}

	public function testShouldDispatchJobsForClientWikis() {
		$timestamp = wfTimestampNow();
		MWTimestamp::setFakeTime( $timestamp );
		list( $pageId, $revisionRecordId, $pageTitle ) = $this->initArchive();
		MWTimestamp::setFakeTime( false );

		$params = [ 'archivedRevisionCount' => 1, 'pageId' => $pageId ];
		$expectedRevIdentifiers = [
			new RepoRevisionIdentifier( "Q303", $timestamp, $revisionRecordId ),
		];

		$logger = $this->createMock( LoggerInterface::class );
		$factory = $this->newJobQueueGroupFactory( $expectedRevIdentifiers );
		$job = $this->getJobAndInitialize( $pageTitle, $params, $logger, $factory );

		$this->assertTrue( $job->run() );
	}

	public function testShouldNotDispatchJobsWhenToOld() {
		MWTimestamp::setFakeTime( '20110401090000' );
		list( $pageId, $revisionRecordId, $pageTitle ) = $this->initArchive();
		MWTimestamp::setFakeTime( false );

		$params = [ 'archivedRevisionCount' => 1, 'pageId' => $pageId ];

		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'info' )
			->with( 'All archive records are too old. Aborting.' );

		$factory = $this->createMock( JobQueueGroupFactory::class );
		$factory->expects( $this->never() )
			->method( 'makeJobQueueGroup' );
		$job = $this->getJobAndInitialize( $pageTitle, $params, $logger, $factory );
		$this->assertTrue( $job->run() );
	}

	/**
	 * @param Title $title
	 * @param array $params
	 * @param LoggerInterface $logger
	 * @param JobQueueGroupFactory $factory
	 * @return DispatchChangeDeletionNotificationJob
	 */
	private function getJobAndInitialize( Title $title, array $params, $logger, $factory ): DispatchChangeDeletionNotificationJob {
		$job = new DispatchChangeDeletionNotificationJob( $title, $params );
		$job->initServices(
			WikibaseRepo::getEntityIdLookup(),
			$logger,
			$factory
		);

		return $job;
	}

	private function newJobQueueGroupFactory(
		array $expectedIds
	): JobQueueGroupFactory {
		$jobQueueGroupFactory = $this->createMock( JobQueueGroupFactory::class );
		$jobQueueGroupFactory->method( 'makeJobQueueGroup' )
			->willReturnCallback( function ( string $wikiId ) use ( $expectedIds ) {
				$jobQueueGroup = $this->createMock( JobQueueGroup::class );
				$jobQueueGroup->expects( $this->once() )
					->method( 'push' )
					->willReturnCallback( function ( array $jobs ) use ( $expectedIds ) {
						$this->assertCount( 1, $jobs );
						$job = $jobs[0];
						$this->assertInstanceOf( IJobSpecification::class, $job );
						$this->assertSame( 'ChangeDeletionNotification', $job->getType() );

						$actualIds = $this->unpackRevisionIdentifiers( $job->getParams()['revisionIdentifiersJson'] );

						$this->assertSameSize( $expectedIds, $actualIds );

						for ( $i = 0; $i < count( $expectedIds ); $i++ ) {
							$this->assertSame( $expectedIds[$i]->getEntityIdSerialization(), $actualIds[$i]->getEntityIdSerialization() );
							$this->assertSame( $expectedIds[$i]->getRevisionId(), $actualIds[$i]->getRevisionId() );
							$this->assertSame( $expectedIds[$i]->getRevisionTimestamp(), $actualIds[$i]->getRevisionTimestamp() );
						}
					} );

				return $jobQueueGroup;
			} );

		return $jobQueueGroupFactory;
	}

	/**
	 * @param string $revisionIdentifiersJson
	 * @return RepoRevisionIdentifier[]
	 */
	private function unpackRevisionIdentifiers( string $revisionIdentifiersJson ): array {
		$repoRevisionFactory = new RepoRevisionIdentifierFactory();
		$revisionIdentifiers = [];

		$revisionIdentifiersArray = json_decode( $revisionIdentifiersJson, true );
		foreach ( $revisionIdentifiersArray as $revisionIdentifierArray ) {
			$revisionIdentifiers[] = $repoRevisionFactory->newFromArray( $revisionIdentifierArray );
		}

		return $revisionIdentifiers;
	}

	private function countArchive( array $conditions = null ): int {
		return $this->db->selectRowCount(
			'archive',
			'ar_rev_id',
			$conditions,
			__METHOD__
		);
	}

	/**
	 * @return WikiPage
	 */
	public function createItemWithPage() {
		$store = WikibaseRepo::getEntityStore();

		// create a fake item
		$item = new Item( new ItemId( 'Q303' ) );
		$revision = $store->saveEntity( $item, 'Q303', $this->getTestUser()->getUser() );

		// get title
		$entityTitleLookup = WikibaseRepo::getEntityTitleLookup();
		$title = $entityTitleLookup->getTitleForId( $revision->getEntity()->getId() );

		// insert a wikipage for the item
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
		$namespace = $entityNamespaceLookup->getEntityNamespace( $item->getId()->getEntityType() );
		$this->insertPage( $title, '{ "entity": "' . $item->getId()->getSerialization() . '" }', $namespace );

		// return page
		return $store->getWikiPageForEntity( $item->getId() );
	}

	protected function initArchive(): array {
		$page = $this->createItemWithPage();
		$revisionRecordId = $page->getRevisionRecord()->getId();
		$pageId = $page->getId();
		$error = '';

		$initialCount = $this->countArchive();

		// delete the page
		$status = $page->doDeleteArticleReal(
			'no reason', $this->getTestUser()->getUser(), false, null, $error,
			null, [], 'delete', false
		);
		$this->assertTrue( $status->isOK() && $status->isGood() );
		$this->assertEquals( $initialCount + 1, $this->countArchive() );

		return [ $pageId, $revisionRecordId, $page->getTitle() ];
	}
}
