<?php

namespace Wikibase\Lib\Formatters;

use ValueFormatters\ValueFormatter;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;

/**
 * @license GPL-2.0-or-later
 */
class UnmappedEntityIdValueFormatter implements ValueFormatter {

	public function format( $value ) {
		/** @var UnmappedEntityIdValue $value */
		return $value->getValue();
	}

}
