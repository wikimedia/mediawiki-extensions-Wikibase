<?php

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;

/**
 * Wrapper class giving ability to replace EntityIdFormatter in production in a controlled manner.
 * Depending on the provided $maxEntityId, formatting will be delegated to either `$targetFormatter`
 * if given entity ID is <= than $maxEntityId, or to `$fallbackFormatter` otherwise.
 *
 * @license GPL-2.0-or-later
 */
class ControlledFallbackEntityIdFormatter implements EntityIdFormatter {

	/**
	 * @var int
	 */
	private $maxEntityId;

	/**
	 * @var EntityIdFormatter
	 */
	private $targetFormatter;

	/**
	 * @var EntityIdFormatter
	 */
	private $fallbackFormatter;

	/**
	 * @param int $maxEntityId
	 * @param EntityIdFormatter $targetFormatter
	 * @param EntityIdFormatter $fallbackFormatter
	 */
	public function __construct(
		$maxEntityId,
		EntityIdFormatter $targetFormatter,
		EntityIdFormatter $fallbackFormatter
	) {
		$this->maxEntityId = $maxEntityId;
		$this->targetFormatter = $targetFormatter;
		$this->fallbackFormatter = $fallbackFormatter;
	}

	public function formatEntityId( EntityId $value ) {
		if ( $value instanceof Int32EntityId && $value->getNumericId() <= $this->maxEntityId ) {
			try {
				return $this->targetFormatter->formatEntityId( $value );
			} catch ( \Throwable $e ) {
				return $this->fallbackFormatter->formatEntityId( $value );
			}
		}

		return $this->fallbackFormatter->formatEntityId( $value );
	}

}
