<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
interface EntityIdFormatter {

	/**
	 * Format an EntityId
	 *
	 * @param EntityId $value
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function format( $value );

}
