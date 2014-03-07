<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\EntityId;

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
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 * @param bool $exists
	 *
	 * @return string
	 */
	public function formatEntityId( EntityId $entityId, $exists = true ) {
		$title = parent::formatEntityId( $entityId, $exists );

		return "[[$title]]";
	}

}
