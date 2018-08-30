<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;

/**
 * @license GPL-2.0-or-later
 */
interface EntityIdHtmlLinkFormatter extends EntityIdFormatter {

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML.
	 */
	public function formatEntityId( EntityId $entityId );

}
