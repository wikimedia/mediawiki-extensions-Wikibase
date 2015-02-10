<?php

namespace Wikibase\DataAccess;

use InvalidArgumentException;
use Language;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataAccess\PropertyIdResolver;
use Wikibase\DataAccess\SnaksFinder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;

/**
 * Renders the main Snaks associated with a given Property on an Entity.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 *
 * @author Marius Hoch < hoo@online.de >
 */
interface EntityStatementsRenderer {

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws PropertyLabelNotResolvedException
	 * @return string
	 */
	public function render( EntityId $entityId, $propertyLabelOrId, $acceptableRanks = null );

}
