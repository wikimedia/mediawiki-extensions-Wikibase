<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\ValueFormatterBase;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdFormatter extends ValueFormatterBase {

	/**
	 * @deprecated
	 */
	const OPT_PREFIX_MAP = 'prefixmap';

	/**
	 * Format an EntityId data value
	 *
	 * @since 0.4
	 *
	 * @param EntityId $value The ID to format
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function format( $value ) {
		if ( !( $value instanceof EntityId ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityId.' );
		}

		return $value->getSerialization();
	}

}
