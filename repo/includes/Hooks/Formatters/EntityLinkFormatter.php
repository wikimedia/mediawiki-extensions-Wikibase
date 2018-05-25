<?php

namespace Wikibase\Repo\Hooks\Formatters;

use Title;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface EntityLinkFormatter {

	public function getHtml( EntityId $entityId, array $labelData );

	public function getTitleAttribute( Title $title, array $labelData, array $descriptionData );

}
