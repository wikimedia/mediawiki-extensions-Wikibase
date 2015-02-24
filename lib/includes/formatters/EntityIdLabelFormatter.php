<?php

namespace Wikibase\Lib;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\LabelLookup;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdLabelFormatter implements EntityIdFormatter {

	/**
	 * @var LabelLookup
	 */
	private $labelLookup;

	/**
	 * @since 0.4
	 *
	 * @param LabelLookup $labelLookup
	 */
	public function __construct( LabelLookup $labelLookup ) {
		$this->labelLookup = $labelLookup;
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
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return Term|null Null if no label was found in the language or language fallback chain.
	 */
	protected function lookupEntityLabel( EntityId $entityId ) {
		try {
			return $this->labelLookup->getLabel( $entityId );
		} catch ( OutOfBoundsException $e ) {
			return null;
		}
	}

}
