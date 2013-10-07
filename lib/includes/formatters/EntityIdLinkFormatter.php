<?php

namespace Wikibase\Lib;
use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * Formats entity IDs by generating a wiki link to the corresponding page title.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityIdLinkFormatter extends EntityIdTitleFormatter {

	/**
	 * Format an EntityId data value
	 *
	 * @param EntityId|EntityIdValue $value The value to format
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		$title = parent::format( $value );

		return "[[$title]]";
	}

}

