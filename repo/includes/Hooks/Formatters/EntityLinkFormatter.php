<?php

namespace Wikibase\Repo\Hooks\Formatters;

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
	 * @param EntityId $entityId
	 * @param string[]|null $labelData
	 * @param string[]|null $descriptionData
	 *
	 * @return string The plain, unescaped title="…" attribute for the link.
	 */
	public function getTitleAttribute(
		EntityId $entityId,
		array $labelData = null,
		array $descriptionData = null
	);

	/**
	 * Optionally update the fragment of the link.
	 *
	 * This is necessary for subentities, where the link
	 * points to a section of the parent entity’s page;
	 * if the anchor of that section changes
	 * (e.g. from including the parent entity ID to not including it),
	 * we want to update the fragment in old links to that entity accordingly.
	 * See T208423 for an example of this.
	 *
	 * @param EntityId $entityId
	 * @param string $fragment The current fragment of the link, not including an initial '#'.
	 * @return string The new fragment (or the same as $fragment), not including an initial '#'.
	 */
	public function getFragment( EntityId $entityId, $fragment );

}
