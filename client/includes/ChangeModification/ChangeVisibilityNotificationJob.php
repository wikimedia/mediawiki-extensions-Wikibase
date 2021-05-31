<?php

declare( strict_types = 1 );

namespace Wikibase\Client\ChangeModification;

use MediaWiki\MediaWikiServices;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikimedia\Assert\Assert;

/**
 * Job for notifying a client wiki of a batch of revision visibility changes on the repository.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ChangeVisibilityNotificationJob extends ChangeModificationNotificationJob {

	/** @var int */
	private $batchSize;

	/**
	 * Constructs a ChangeVisibilityNotificationJob for the repo revisions given.
	 *
	 * @param ClientDomainDb $clientDb
	 * @param int $batchSize
	 * @param array $params Contains the name of the repo, revisionIdentifiersJson to redact
	 *   and the visibilityBitFlag to set.
	 */
	public function __construct( ClientDomainDb $clientDb, int $batchSize, array $params = [] ) {
		parent::__construct( 'ChangeVisibilityNotification', $clientDb, $params );

		Assert::parameter(
			isset( $params['visibilityBitFlag'] ),
			'$params',
			'$params[\'visibilityBitFlag\'] not set.'
		);

		$this->batchSize = $batchSize;
	}

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
		$visibilityBitFlag = $this->params['visibilityBitFlag'];

		$dbw = $this->clientDb->connections()->getWriteConnection();

		foreach ( array_chunk( $relevantChanges, $this->batchSize ) as $rcIdBatch ) {
			$dbw->update(
				'recentchanges',
				[ 'rc_deleted' => $visibilityBitFlag ],
				[ 'rc_id' => $rcIdBatch ],
				__METHOD__
			);

			$this->clientDb->replication()->waitForAllAffectedClusters();
		}
	}

}
