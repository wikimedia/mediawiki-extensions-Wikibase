<?php

namespace Wikibase\DataModel\Services\Lookup;

use Exception;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 2.0
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class TermLookupException extends RuntimeException {

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @param EntityId $entityId
	 * @param string[] $languageCodes
	 * @param string|null $message
	 * @param Exception|null $previous
	 */
	public function __construct(
		EntityId $entityId,
		array $languageCodes,
		$message = null,
		Exception $previous = null
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
