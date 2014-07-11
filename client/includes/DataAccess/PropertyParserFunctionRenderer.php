<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface PropertyParserFunctionRenderer {

	/**
	 * @param ItemId $itemId
	 * @param string $propertyLabel
	 *
	 * @return string
	 */
	public function render( ItemId $itemId, $propertyLabel );

}
