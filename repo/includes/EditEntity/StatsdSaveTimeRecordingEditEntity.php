<?php

namespace Wikibase\Repo\EditEntity;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * EditEntity that collects stats for edits.
 * @license GPL-2.0-or-later
 */
class StatsdSaveTimeRecordingEditEntity implements EditEntity {

	/** @var EditEntity */
	private $inner;
	/** @var StatsdDataFactoryInterface */
	private $stats;
	/** @var string */
	private $timingPrefix;

	/**
	 * @param EditEntity $editEntity
	 * @param StatsdDataFactoryInterface $stats
	 * @param string $timingPrefix Resulting metric will be: $timingPrefix.<savetype>.<entitytype>
	 */
	public function __construct(
		EditEntity $editEntity,
		StatsdDataFactoryInterface $stats,
		string $timingPrefix
	) {
		$this->inner = $editEntity;
		$this->stats = $stats;
		$this->timingPrefix = $timingPrefix;
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
		$attemptSaveStart = microtime( true );
		$result = $this->inner->attemptSave( $newEntity, $summary, $flags, $token, $watch, $tags );
		$attemptSaveEnd = microtime( true );

		$this->stats->timing(
			"{$this->timingPrefix}.attemptSave.{$newEntity->getType()}",
			( $attemptSaveEnd - $attemptSaveStart ) * 1000
		);

		return $result;
	}

}
