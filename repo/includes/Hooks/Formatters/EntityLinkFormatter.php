<?php

namespace Wikibase\Repo\Hooks\Formatters;

use Title;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface EntityLinkFormatter {

	/**
	 * Produce link HTML from Entity ID and label data.
	 * @param EntityId $entityId
	 * @param string[]|null $labelData Array containing the 'value' and 'language' fields
	 *
	 * @return string HTML code for the link
	 */
	public function getHtml( EntityId $entityId, array $labelData = null );

	/**
	 * Get "title" attribute for Wikidata entity link.
	 * @param Title $title
	 * @param string[]|null $labelData
	 * @param string[]|null $descriptionData
	 *
	 * @return string The plain, unescaped title="…" attribute for the link.
	 */
	public function getTitleAttribute( Title $title, array $labelData = null, array $descriptionData = null );

}
