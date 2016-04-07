<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 1.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
interface EntityIdFormatter {

	/**
	 * @param EntityId $value
	 *
	 * @return string Either plain text, wikitext or HTML.
	 */
	public function formatEntityId( EntityId $value );

}
