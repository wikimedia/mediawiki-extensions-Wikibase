<?php

namespace Wikibase\Lib\Store;

use Wikimedia\NormalizedException\INormalizedException;
use Wikimedia\NormalizedException\NormalizedExceptionTrait;

/**
 * @license GPL-2.0-or-later
 */
class DivergingEntityIdException extends BadRevisionException implements INormalizedException {
	use NormalizedExceptionTrait;

	private EntityRevision $entityRevision;
	private string $expectedEntityId;
	private int $revisionId;

	public function __construct( int $revisionId, EntityRevision $entityRevision, string $expectedEntityId ) {
		$this->revisionId = $revisionId;
		$this->entityRevision = $entityRevision;
		$this->expectedEntityId = $expectedEntityId;

		$this->normalizedMessage = 'Revision {revisionId} belongs to {actualEntityId} instead of expected {entityId}';
		$this->messageContext = [
			'revisionId' => $this->revisionId,
			'actualEntityId' => $this->entityRevision->getEntity()->getId()->getSerialization(),
			'entityId' => $this->expectedEntityId,
		];
		parent::__construct( self::getMessageFromNormalizedMessage( $this->normalizedMessage, $this->messageContext ) );
	}

	public function getEntityRevision(): EntityRevision {
		return $this->entityRevision;
	}

}
