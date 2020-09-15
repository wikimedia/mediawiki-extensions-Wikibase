<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * Generates HTML to display the terms of an entity.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface EntityTermsView {

	public const TERMS_EDIT_SPECIAL_PAGE = 'SetLabelDescriptionAliases';

	/**
	 * @param string $mainLanguageCode Desired language of the label, description and aliases in the
	 *  title and header section. Not necessarily identical to the interface language.
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param AliasGroupList|null $aliasGroups
	 * @param EntityId|null $entityId the id of the entity
	 *
	 * @return string HTML
	 */
	public function getHtml(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null,
		EntityId $entityId = null
	);

	/**
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	public function getTitleHtml( EntityId $entityId = null );

}
