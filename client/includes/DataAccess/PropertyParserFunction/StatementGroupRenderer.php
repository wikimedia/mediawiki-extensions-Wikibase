<?php

namespace Wikibase\Client\DataAccess\PropertyParserFunction;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Renderer for the {{#property}} parser function for
 * rendering a Statement group.
 *
 * @license GPL-2.0+
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
