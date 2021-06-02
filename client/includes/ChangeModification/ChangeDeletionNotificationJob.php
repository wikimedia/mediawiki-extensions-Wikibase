<?php

declare( strict_types = 1 );

namespace Wikibase\Client\ChangeModification;

use MediaWiki\MediaWikiServices;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Rdbms\ClientDomainDb;

/**
 * Job for notifying a client wiki of a batch of revision deletions on the repository.
 *
 * @license GPL-2.0-or-later
 */
class ChangeDeletionNotificationJob extends ChangeModificationNotificationJob {

	/** @var int */
	private $batchSize;

	/**
	 * Constructs a ChangeDeletionNotificationJob for the repo revisions given.
	 *
	 * @param ClientDomainDb $clientDb
	 * @param int $batchSize
	 * @param array $params Contains the name of the repo, revisionIdentifiersJson to redact
	 */
	public function __construct( ClientDomainDb $clientDb, int $batchSize, array $params = [] ) {
		parent::__construct( 'ChangeDeletionNotification', $clientDb, $params );

		$this->batchSize = $batchSize;
	}

	/**
	 * @param Title $unused
	 * @param array $params
	 * @return ChangeDeletionNotificationJob
	 */
	public static function newFromGlobalState( Title $unused, array $params ) {
		$mwServices = MediaWikiServices::getInstance();

		return new self(
			WikibaseClient::getClientDomainDbFactory()->newLocalDb(),
			$mwServices->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			$params
		);
	}

	/**
	 * @param int[] $relevantChanges
	 */
	protected function modifyChanges( array $relevantChanges ): void {

		$dbw = $this->clientDb->connections()->getWriteConnection();

		foreach ( array_chunk( $relevantChanges, $this->batchSize ) as $rcIdBatch ) {
			$dbw->delete(
				'recentchanges',
				[ 'rc_id' => $rcIdBatch ],
				__METHOD__
			);

			$this->clientDb->replication()->waitForAllAffectedClusters();
		}
	}

}
