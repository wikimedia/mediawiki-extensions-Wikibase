<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\Term;

/**
 * @since 1.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatter implements EntityIdFormatter {

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 */
	public function __construct( LabelDescriptionLookup $labelDescriptionLookup ) {
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string Plain text
	 */
	public function formatEntityId( EntityId $entityId ) {
		$term = $this->lookupEntityLabel( $entityId );
		if ( $term !== null ) {
			return $term->getText();
		}

		// @fixme check if the entity is deleted and format differently?
		return $entityId->getSerialization();
	}

	/**
	 * Lookup a label for an entity
	 *
	 * @param EntityId $entityId
	 *
	 * @return Term|null Null if no label was found or the entity does not exist
	 */
	protected function lookupEntityLabel( EntityId $entityId ) {
		try {
			return $this->labelDescriptionLookup->getLabel( $entityId );
		} catch ( LabelDescriptionLookupException $e ) {
			return null;
		}
	}

}
