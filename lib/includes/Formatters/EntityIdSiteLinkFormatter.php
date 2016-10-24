<?php

namespace Wikibase\Lib\Formatters;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * A formatter for exclusive use on client wikis. It expects an entity ID and returns a wikitext
 * snippet, containing a link to a local page as specified by the relevant sitelink, labeled with
 * the label in the local (or a fallback) language. Both the sitelink and the label are optional.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class EntityIdSiteLinkFormatter implements EntityIdFormatter {

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $localSiteId;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelLookup;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $localSiteId
	 * @param LabelDescriptionLookup $labelLookup
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		$localSiteId,
		LabelDescriptionLookup $labelLookup
	) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->localSiteId = $localSiteId;
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
			$pageName = $this->getPageName( $entityId );

			if ( $pageName !== null ) {
				$optionalLabel = $label === '' ? '' : '|' . $label;

				return '[[' . $pageName . $optionalLabel . ']]';
			}
		}

		return $label === '' ? $entityId->getSerialization() : $label;
	}

	/**
	 * @param ItemId $id
	 *
	 * @return string|null
	 */
	private function getPageName( ItemId $id ) {
		// TODO: Bad, bad interface
		$siteLinkData = $this->siteLinkLookup->getLinks(
			[ $id->getNumericId() ],
			[ $this->localSiteId ]
		);

		if ( count( $siteLinkData ) !== 1 ) {
			return null;
		}

		return $siteLinkData[0][1];
	}

}
