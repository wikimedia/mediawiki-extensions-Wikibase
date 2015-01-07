<?php

namespace Wikibase\Lib;

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
	 * @return string
	 */
	public function formatEntityId( EntityId $value );

}
