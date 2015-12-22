<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
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
