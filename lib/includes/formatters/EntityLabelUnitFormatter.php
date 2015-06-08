<?php

namespace Wikibase\Lib;

use OutOfBoundsException;
use ValueFormatters\QuantityUnitFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\LabelDescriptionLookup;

/**
 * QuantityUnitFormatter for representing units by their respective entity label.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityLabelUnitFormatter implements QuantityUnitFormatter {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelLookup;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param LabelDescriptionLookup $labelLookup
	 */
	public function __construct( EntityIdParser $entityIdParser, LabelDescriptionLookup $labelLookup ) {
		$this->entityIdParser = $entityIdParser;
		$this->labelLookup = $labelLookup;
	}

	/**
	 * @see QuantityUnitFormatter::applyUnit()
	 *
	 * This implementation will interpret $unit as an entity ID or URI string that can be parsed
	 * using the EntityIdParser supplied to the constructor. If $unit is successfully parsed,
	 * the label of the entity is looked up, and appended to $numberText with a single space as
	 * a separator.
	 *
	 * @param string $unit
	 * @param string $numberText
	 *
	 * @return string
	 */
	public function applyUnit( $unit, $numberText ) {
		if ( preg_match( '!^(|1|https?://www\.wikidata\.org/entity/Q199)$!', $unit ) ) {
			return $numberText;
		}

		try {
			$entityId = $this->entityIdParser->parse( $unit );

			try {
				// TODO: special lookup for symbol
				$label = $this->labelLookup->getLabel( $entityId )->getText();
			} catch ( OutOfBoundsException $ex ) {
				$label = $entityId->getSerialization();
			}

			// TODO: localizable pattern for placement (before/after, separator)
			return "$numberText $label";
		} catch ( EntityIdParsingException $ex ) {
			// Use raw ID (URI)
			return "$numberText $unit";
		}
	}
}
