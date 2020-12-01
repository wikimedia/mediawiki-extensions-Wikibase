<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;

/**
 * Wrapper for EntityIdFormatter that handles federated property API exceptions
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesEntityIdFormatter implements EntityIdFormatter {

	/** @var EntityIdFormatter */
	private $innerFormatter;

	public function __construct( EntityIdFormatter $innerFormatter ) {
		$this->innerFormatter = $innerFormatter;
	}

	public function formatEntityId( EntityId $entityId ) {
		try {
			return $this->innerFormatter->formatEntityId( $entityId );
		} catch ( ApiRequestExecutionException $e ) {
			return $entityId->getSerialization();
		}
	}

}
