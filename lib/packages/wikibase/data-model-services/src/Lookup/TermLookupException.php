<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 1.2
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermLookupException extends \RuntimeException {

	private $entityId;

	public function __construct(
		EntityId $entityId,
		array $languageCodes,
		$message = null,
		\Exception $previous = null
	) {
		$this->entityId = $entityId;

		$codesString = implode( ', ', $languageCodes );

		parent::__construct(
			$message ?: 'Term lookup failed for: ' . $entityId . ' with language codes: ' . $codesString,
			0,
			$previous
		);
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

}
