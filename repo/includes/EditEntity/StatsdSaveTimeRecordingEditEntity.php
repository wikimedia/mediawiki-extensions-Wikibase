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

	public function getEntityId() {
		return $this->inner->getEntityId();
	}

	public function getLatestRevision() {
		return $this->inner->getLatestRevision();
	}

	public function getBaseRevision() {
		return $this->inner->getBaseRevision();
	}

	public function getStatus() {
		return $this->inner->getStatus();
	}

	public function isSuccess() {
		return $this->inner->isSuccess();
	}

	public function hasError( $errorType = self::ANY_ERROR ) {
		return $this->inner->hasError( $errorType );
	}

	public function hasEditConflict() {
		return $this->inner->hasEditConflict();
	}

	public function isTokenOK( $token ) {
		return $this->inner->isTokenOK( $token );
	}

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
