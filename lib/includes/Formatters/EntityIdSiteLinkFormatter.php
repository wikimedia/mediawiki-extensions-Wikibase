<?php

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * A formatter for exclusive use on client wikis. It expects an entity ID and returns a wikitext
 * snippet, containing a link to a local page as specified by the relevant sitelink, labeled with
 * the label in the local (or a fallback) language. Both the sitelink and the label are optional.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class EntityIdSiteLinkFormatter implements EntityIdFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelLookup;

	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		LabelDescriptionLookup $labelLookup
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->labelLookup = $labelLookup;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string Wikitext
	 */
	public function formatEntityId( EntityId $entityId ) {
		$term = null;

		try {
			$term = $this->labelLookup->getLabel( $entityId );
		} catch ( LabelDescriptionLookupException $ex ) {
		}

		// TODO: Add language fallback indicator
		$label = $term ? wfEscapeWikiText( $term->getText() ) : '';

		if ( $entityId instanceof ItemId ) {
			$title = $this->entityTitleLookup->getTitleForId( $entityId );

			if ( $title !== null ) {
				$pageName = $title->getFullText();
				$optionalLabel = $label === '' ? '' : '|' . $label;

				return '[[' . $pageName . $optionalLabel . ']]';
			}
		}

		return $label === '' ? $entityId->getSerialization() : $label;
	}

}
