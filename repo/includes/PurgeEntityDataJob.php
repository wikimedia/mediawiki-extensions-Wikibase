<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use BatchRowIterator;
use HtmlCacheUpdater;
use Job;
use MediaWiki\MediaWikiServices;
use Title;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\LinkedData\EntityDataUriManager;

/**
 * Job to purge Special:EntityData URLs from the HTTP cache after a page was deleted.
 *
 * @license GPL-2.0-or-later
 */
class PurgeEntityDataJob extends Job {

	/** @var EntityIdParser */
	private $entityIdParser;

	/** @var EntityDataUriManager */
	private $entityDataUriManager;

	/** @var RepoDomainDb */
	private $repoDb;

	/** @var HtmlCacheUpdater */
	private $htmlCacheUpdater;

	/** @var int */
	private $batchSize;

	public function __construct(
		EntityIdParser $entityIdParser,
		EntityDataUriManager $entityDataUriManager,
		RepoDomainDb $repoDb,
		HtmlCacheUpdater $htmlCacheUpdater,
		int $batchSize,
		array $params
	) {
		parent::__construct( 'PurgeEntityData', $params );
		$this->entityIdParser = $entityIdParser;
		$this->entityDataUriManager = $entityDataUriManager;
		$this->repoDb = $repoDb;
		$this->htmlCacheUpdater = $htmlCacheUpdater;
		$this->batchSize = $batchSize;
	}

	public static function newFromGlobalState( Title $unused, array $params ): self {
		$services = MediaWikiServices::getInstance();
		return new self(
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getEntityDataUriManager( $services ),
			WikibaseRepo::getRepoDomainDbFactory( $services )->newRepoDb(),
			$services->getHtmlCacheUpdater(),
			$services->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			$params
		);
	}

	public function run(): bool {
		$entityId = $this->entityIdParser->parse( $this->params['entityId'] );

		// URLs for latest data...
		$urls = $this->entityDataUriManager->getPotentiallyCachedUrls( $entityId );
		foreach ( $this->getArchivedRevisionIds() as $revisionId ) {
			// ...and URLs for each specific revision
			$urls = array_merge( $urls, $this->entityDataUriManager->getPotentiallyCachedUrls(
				$entityId,
				$revisionId
			) );
		}

		if ( $urls !== [] ) {
			$this->htmlCacheUpdater->purgeUrls( $urls );
		}

		return true;
	}

	private function getArchivedRevisionIds(): iterable {
		// read archive rows from a replica, but only after it has caught up with the primary
		// (in theory we only need to wait for the primary pos as of when the job was pushed,
		// but DBPrimaryPos is an opaque object, so we can’t put it in the job params)
		$this->repoDb->replication()->wait();
		$dbr = $this->repoDb->connections()->getReadConnection();

		$iterator = new BatchRowIterator( $dbr, 'archive', [ 'ar_id' ], $this->batchSize );
		$iterator->setFetchColumns( [ 'ar_rev_id' ] );
		$iterator->addConditions( [
			'ar_page_id' => $this->params['pageId'],
			'ar_title' => $this->params['title'],
			'ar_namespace' => $this->params['namespace'],
		] );
		$iterator->setCaller( __METHOD__ );

		foreach ( $iterator as $batch ) {
			foreach ( $batch as $row ) {
				yield (int)$row->ar_rev_id;
			}
		}
	}

}
