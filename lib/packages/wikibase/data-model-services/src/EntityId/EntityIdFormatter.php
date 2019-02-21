<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
interface EntityIdFormatter {

	/**
	 * @param EntityId $value
	 *
	 * @return string Either plain text, wikitext or HTML.
	 * @throws \InvalidArgumentException when value falls out of supported patterns by the formatter
	 */
	public function formatEntityId( EntityId $value );

}
