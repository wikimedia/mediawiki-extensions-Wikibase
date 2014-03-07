<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * Formats entity IDs by generating a wiki link to the corresponding page title.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityIdLinkFormatter extends EntityIdTitleFormatter {

	/**
	 * @param EntityId $entityId
	 * @param array $entityInfo
	 *
	 * @return string
	 *
	 * @see EntityIdFormatter::formatEntityId
	 */
	public function formatEntityId( EntityId $entityId, $exists = true ) {
		$title = parent::formatEntityId( $entityId, $exists );

		return "[[$title]]";
	}

}
