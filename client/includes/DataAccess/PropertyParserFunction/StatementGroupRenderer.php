<?php

namespace Wikibase\Client\DataAccess\PropertyParserFunction;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Renderer for the {{#property}} parser function for
 * rendering a Statement group.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface StatementGroupRenderer {

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId
	 *
	 * @return string
	 */
	public function render( EntityId $entityId, $propertyLabelOrId );

}
