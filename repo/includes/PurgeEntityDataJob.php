<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use BatchRowIterator;
use HtmlCacheUpdater;
use Job;
use MediaWiki\MediaWikiServices;
use Title;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\ILoadBalancer;

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

	/** @var ILBFactory */
	private $lbFactory;

	/** @var HtmlCacheUpdater */
	private $htmlCacheUpdater;

	/** @var int */
	private $batchSize;

	public function __construct(
		EntityIdParser $entityIdParser,
		EntityDataUriManager $entityDataUriManager,
		ILBFactory $lbFactory,
		HtmlCacheUpdater $htmlCacheUpdater,
		int $batchSize,
		array $params
	) {
		parent::__construct( 'PurgeEntityData', $params );
		$this->entityIdParser = $entityIdParser;
		$this->entityDataUriManager = $entityDataUriManager;
		$this->lbFactory = $lbFactory;
		$this->htmlCacheUpdater = $htmlCacheUpdater;
		$this->batchSize = $batchSize;
	}

	public static function newFromGlobalState( Title $unused, array $params ): self {
		$repo = WikibaseRepo::getDefaultInstance();
		$services = MediaWikiServices::getInstance();
		return new self(
			$repo->getEntityIdParser(),
			$repo->getEntityDataUriManager(),
			$services->getDBLoadBalancerFactory(),
			$services->getHtmlCacheUpdater(),
			$services->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			$params
		);
	}

	public function run(): void {
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
	}

	private function getArchivedRevisionIds(): iterable {
		// read archive rows from a replica, but only after it has caught up with the master
		// (in theory we only need to wait for the master pos as of when the job was pushed,
		// but DBMasterPos is an opaque object, so we can’t put it in the job params)
		$this->lbFactory->waitForReplication( [
			'domain' => $this->lbFactory->getLocalDomainID(),
		] );
		$dbr = $this->lbFactory->getMainLB()->getConnection( ILoadBalancer::DB_REPLICA );

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
