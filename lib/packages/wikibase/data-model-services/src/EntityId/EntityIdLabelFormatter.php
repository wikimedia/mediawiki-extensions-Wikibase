<?php

namespace Wikibase\DataModel\Services\EntityId;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
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
	 * @return string
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
	 * @return Term|null Null if no label was found in the language or language fallback chain.
	 */
	protected function lookupEntityLabel( EntityId $entityId ) {
		try {
			return $this->labelDescriptionLookup->getLabel( $entityId );
		} catch ( OutOfBoundsException $e ) {
			return null;
		}
	}

}
