<?php

namespace Wikibase\Repo\EditEntity;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * EditEntity that times save times for saves recording to statsd.
 * @license GPL-2.0-or-later
 */
class TimedEditEntity implements EditEntity {

	private $editEntity;
	private $stats;
	private $timingPrefix;

	/**
	 * @param EditEntity $editEntity
	 * @param StatsdDataFactoryInterface $stats
	 * @param string $timingPrefix
	 */
	public function __construct(
		EditEntity $editEntity,
		StatsdDataFactoryInterface $stats,
		$timingPrefix
	) {
		$this->editEntity = $editEntity;
		$this->stats = $stats;
		$this->timingPrefix = $timingPrefix;
	}

	public function getEntityId() {
		return $this->editEntity->getEntityId();
	}

	public function getLatestRevision() {
		return $this->editEntity->getLatestRevision();
	}

	public function getBaseRevision() {
		return $this->editEntity->getBaseRevision();
	}

	public function getStatus() {
		return $this->editEntity->getStatus();
	}

	public function isSuccess() {
		return $this->editEntity->isSuccess();
	}

	public function hasError( $errorType = self::ANY_ERROR ) {
		return $this->editEntity->hasError( $errorType );
	}

	public function hasEditConflict() {
		return $this->editEntity->hasEditConflict();
	}

	public function isTokenOK( $token ) {
		return $this->editEntity->isTokenOK( $token );
	}

	public function attemptSave(
		EntityDocument $newEntity,
		$summary,
		$flags,
		$token,
		$watch = null
	) {
		$attemptSaveStart = microtime( true );
		$result = $this->editEntity->attemptSave( $newEntity, $summary, $flags, $token, $watch );
		$attemptSaveEnd = microtime( true );

		$this->stats->timing(
			"{$this->timingPrefix}.{$newEntity->getType()}.EditEntity.attemptSave",
			( $attemptSaveEnd - $attemptSaveStart ) * 1000
		);

		return $result;
	}

}
