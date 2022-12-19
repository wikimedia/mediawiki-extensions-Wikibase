<?php

namespace Wikibase\Lib\Store;

/**
 * @license GPL-2.0-or-later
 */
class DivergingEntityIdException extends BadRevisionException {

	private EntityRevision $entityRevision;
	private string $expectedEntityId;
	private int $revisionId;

	public function __construct( int $revisionId, EntityRevision $entityRevision, string $expectedEntityId ) {
		$this->revisionId = $revisionId;
		$this->entityRevision = $entityRevision;
		$this->expectedEntityId = $expectedEntityId;
		$actualEntityId = $entityRevision->getEntity()->getId()->getSerialization();

		parent::__construct( "Revision $revisionId belongs to $actualEntityId instead of expected $expectedEntityId" );
	}

	/**
	 * @return array
	 * @phan-return non-empty-array
	 */
	public function getNormalizedDataForLogging(): array {
		return [
			'Revision {revisionId} belongs to {actualEntityId} instead of expected {entityId}',
			[
				'revisionId' => $this->revisionId,
				'actualEntityId' => $this->entityRevision->getEntity()->getId()->getSerialization(),
				'entityId' => $this->expectedEntityId,
			],
		];
	}

	public function getEntityRevision(): EntityRevision {
		return $this->entityRevision;
	}

}
