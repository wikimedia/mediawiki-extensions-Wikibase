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
	private $externalEntityIdParser;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelLookup;

	/**
	 * @param EntityIdParser $externalEntityIdParser
	 * @param LabelDescriptionLookup $labelLookup
	 */
	public function __construct( EntityIdParser $externalEntityIdParser, LabelDescriptionLookup $labelLookup ) {
		$this->externalEntityIdParser = $externalEntityIdParser;
		$this->labelLookup = $labelLookup;
	}

	/**
	 * @see QuantityUnitFormatter::applyUnit()
	 *
	 * This implementation will interpret $unit as an external entity ID (typically a URI), which
	 * can be parsed using the EntityIdParser supplied to the constructor. If $unit is successfully
	 * parsed, the label of the entity is looked up, and appended to $numberText with a single
	 * space as a separator.
	 *
	 * @param string $unit
	 * @param string $numberText
	 *
	 * @return string
	 */
	public function applyUnit( $unit, $numberText ) {
		//TODO: replace hard coded IDs meaning "no unit" by something configurable.
		if ( $unit === '' || $unit === '1' || $unit === 'http://www.wikidata.org/entity/Q199' ) {
			return $numberText;
		}

		try {
			$entityId = $this->externalEntityIdParser->parse( $unit );

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
