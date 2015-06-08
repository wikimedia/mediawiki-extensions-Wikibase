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
	 * @var array A set of unitless IDs, represented as string keys.
	 */
	private $unitlessUnitIds;

	/**
	 * @param EntityIdParser $externalEntityIdParser
	 * @param LabelDescriptionLookup $labelLookup
	 * @param string[] $unitlessUnitIds A list of IDs that represent the "unitless" unit (one),
	 *        e.g. "http://www.wikidata.org/entity/Q199". The strings "" and "1" are always
	 *        treated as "non-units".
	 */
	public function __construct(
		EntityIdParser $externalEntityIdParser,
		LabelDescriptionLookup $labelLookup,
		array $unitlessUnitIds = array()
	) {
		$this->unitlessUnitIds = array_flip( $unitlessUnitIds );
		$this->unitlessUnitIds[''] = true;
		$this->unitlessUnitIds['1'] = true;

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
	 * @return string Text
	 */
	public function applyUnit( $unit, $numberText ) {
		if ( array_key_exists( $unit, $this->unitlessUnitIds ) ) {
			return $numberText;
		}

		try {
			$entityId = $this->externalEntityIdParser->parse( $unit );

			try {
				// TODO: Ideally we would show unit *symbols*, taking from a config file,
				// a system message, or a statement on the unit's item. Then the
				// name "EntityLabelUnitFormatter" doesn't apply any more, though.
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
