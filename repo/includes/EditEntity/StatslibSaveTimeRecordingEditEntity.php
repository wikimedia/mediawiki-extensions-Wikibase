<?php

namespace Wikibase\Repo\EditEntity;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Stats\StatsFactory;

/**
 * EditEntity that collects stats for edits.
 * @license GPL-2.0-or-later
 */
class StatslibSaveTimeRecordingEditEntity implements EditEntity {

	/**
	 * @var EditEntity
	 */
	private $inner;

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/**
	 * @param EditEntity $editEntity
	 * @param StatsFactory $statsFactory
	 */
	public function __construct(
		EditEntity $editEntity,
		StatsFactory $statsFactory
	) {
		$this->inner = $editEntity;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
	}

	/**
	 * @inheritDoc
	 */
	public function getEntityId() {
		return $this->inner->getEntityId();
	}

	/**
	 * @inheritDoc
	 */
	public function getLatestRevision() {
		return $this->inner->getLatestRevision();
	}

	/**
	 * @inheritDoc
	 */
	public function getBaseRevision() {
		return $this->inner->getBaseRevision();
	}

	/**
	 * @inheritDoc
	 */
	public function getStatus() {
		return $this->inner->getStatus();
	}

	/**
	 * @inheritDoc
	 */
	public function isSuccess() {
		return $this->inner->isSuccess();
	}

	/**
	 * @inheritDoc
	 */
	public function hasError( $errorType = self::ANY_ERROR ) {
		return $this->inner->hasError( $errorType );
	}

	/**
	 * @inheritDoc
	 */
	public function hasEditConflict() {
		return $this->inner->hasEditConflict();
	}

	/**
	 * @inheritDoc
	 */
	public function isTokenOK( $token ) {
		return $this->inner->isTokenOK( $token );
	}

	/**
	 * @inheritDoc
	 */
	public function attemptSave(
		EntityDocument $newEntity,
		string $summary,
		$flags,
		$token,
		$watch = null,
		array $tags = []
	) {
		$timing = $this->statsFactory
			->getTiming( 'EditEntity_attemptSave_duration_seconds' )
			->setLabel( 'type', $newEntity->getType() )
			->copyToStatsdAt( "wikibase.repo.EditEntity.timing.EditEntity.attemptSave.{$newEntity->getType()}" );

		$timing->start();
		$result = $this->inner->attemptSave( $newEntity, $summary, $flags, $token, $watch, $tags );
		$timing->stop();

		return $result;
	}

}
