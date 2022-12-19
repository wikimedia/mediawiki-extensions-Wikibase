<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeModification;

use BatchRowIterator;
use JobSpecification;
use MediaWiki\MediaWikiServices;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;

/**
 * Job for fetching and dispatching RepoRevisionIdentifiers marked for deletion for client databases
 *
 * @license GPL-2.0-or-later
 */
class DispatchChangeDeletionNotificationJob extends DispatchChangeModificationNotificationJob {

	/** @var int */
	private $batchSize;

	/** @var int */
	private $archivedRevisionCount;

	/** @var int */
	private $pageId;

	/**
	 * @var RepoDomainDb
	 */
	private $db;

	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'DispatchChangeDeletionNotification', $title, $params );

		Assert::parameter( isset( $params['pageId'] ), '$params', '$params[\'pageId\'] not set.' );
		Assert::parameter( isset( $params['archivedRevisionCount'] ), '$params', '$params[\'archivedRevisionCount\'] not set.' );
		Assert::parameterType( 'integer', $params['pageId'], '$params[\'pageId\']' );
		Assert::parameterType( 'integer', $params['archivedRevisionCount'], '$params[\'archivedRevisionCount\']' );

		$this->pageId = $params['pageId'];
		$this->archivedRevisionCount = $params['archivedRevisionCount'];
	}

	protected function initFromGlobalState( MediaWikiServices $mwServices ): void {
		parent::initFromGlobalState( $mwServices );

		$this->batchSize = $mwServices->getMainConfig()->get( 'UpdateRowsPerQuery' );
		$this->db = WikibaseRepo::getRepoDomainDbFactory( $mwServices )->newRepoDb();
	}

	private function getArchiveRows( int &$processed, int &$staleRecords, string $entityIdSerialization ): array {
		$dbr = $this->db->connections()->getReadConnection();

		$iterator = new BatchRowIterator(
			$dbr,
			'archive',
			[ 'ar_id' ],
			$this->batchSize
		);

		$thresholdTime = time() - $this->clientRCMaxAge;
		$staleRecords = (int)$dbr->selectField(
			'archive', 'COUNT(*)',
			[
				'ar_namespace' => $this->getTitle()->getNamespace(),
				'ar_title' => $this->getTitle()->getDBkey(),
				'ar_page_id' => $this->pageId,
				'ar_timestamp < ' . $dbr->addQuotes( $dbr->timestamp( $thresholdTime ) ),
			], __METHOD__
		);

		if ( $staleRecords === $this->archivedRevisionCount ) {
			return [];
		}

		$iterator->setFetchColumns( [ 'ar_rev_id', 'ar_timestamp' ] );
		$iterator->addConditions( [
			'ar_page_id' => $this->pageId,
			'ar_title' => $this->title->getDBkey(),
			'ar_namespace' => $this->title->getNamespace(),
			'ar_timestamp >= ' . $dbr->addQuotes( $dbr->timestamp( $thresholdTime ) ),
		] );

		$identifiers = [];
		foreach ( $iterator as $batch ) {
			foreach ( $batch as $row ) {
				$identifiers[] = new RepoRevisionIdentifier(
					$entityIdSerialization,
					$row->ar_timestamp,
					intval( $row->ar_rev_id )
				);
				$processed++;
			}
		}
		return $identifiers;
	}

	protected function getChangeModificationNotificationJobs( EntityId $entityId ): array {
		$processed = 0;
		$staleRecords = 0;
		$repoRevisionIdentifiers = $this->getArchiveRows( $processed, $staleRecords, $entityId->getSerialization() );

		if ( $staleRecords === $this->archivedRevisionCount ) {
			$this->logger->info( "All archive records are too old. Aborting." );
			return [];
		}

		if ( $processed !== $this->archivedRevisionCount - $staleRecords ) {
			$this->logger->warning( __METHOD__ . ': processed {processed} rows but archived {archived} revisions for {entityId}', [
				'processed' => $processed,
				'archived' => $this->archivedRevisionCount - $staleRecords,
				'entityId' => $entityId->getSerialization(),
			] );
		}

		if ( $repoRevisionIdentifiers === [] ) {
			return [];
		}

		return [ new JobSpecification( 'ChangeDeletionNotification', [
			'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( $repoRevisionIdentifiers ),
		] ) ];
	}
}
