<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\QuantityUnitFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;

/**
 * QuantityUnitFormatter for representing units by their respective Item label.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class ItemLabelUnitFormatter implements QuantityUnitFormatter {

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelLookup;

	/**
	 * @var string
	 */
	private $unitUriPrefix;

	/**
	 * @var array A set of unitless IDs, represented as string keys.
	 */
	private $unitlessUnitIds;

	/**
	 * @param LabelDescriptionLookup $labelLookup
	 * @param string $unitUriPrefix The prefix to be stripped. Stripping is cases sensitive.
	 * @param string[] $unitlessUnitIds A list of IDs that represent the "unitless" unit (one),
	 *        e.g. "http://www.wikidata.org/entity/Q199". The strings "" and "1" are always
	 *        treated as "non-units".
	 */
	public function __construct(
		LabelDescriptionLookup $labelLookup,
		$unitUriPrefix = '',
		array $unitlessUnitIds = array()
	) {
		$this->labelLookup = $labelLookup;
		$this->unitUriPrefix = $unitUriPrefix;
		$this->unitlessUnitIds = array_flip( $unitlessUnitIds );
		$this->unitlessUnitIds[''] = true;
		$this->unitlessUnitIds['1'] = true;
	}

	/**
	 * @see QuantityUnitFormatter::applyUnit
	 *
	 * This implementation will interpret $unit as an external ItemId, typically a URI.
	 * If successfully parsed, the label of the Item is looked up, and appended to $numberText with
	 * a single space as a separator.
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

		$prefixLength = strlen( $this->unitUriPrefix );
		if ( strncmp( $this->unitUriPrefix, $unit, $prefixLength ) !== 0 ) {
			return "$numberText $unit";
		}

		$idString = substr( $unit, $prefixLength );
		$label = $this->getUnitLabel( $idString );
		// TODO: Localizable pattern for placement (before/after, separator).
		return "$numberText $label";
	}

	/**
	 * @param string $idString
	 *
	 * @return string
	 */
	private function getUnitLabel( $idString ) {
		try {
			$itemId = new ItemId( $idString );

			try {
				// TODO: Ideally we would show unit *symbols*, taking from a config file, a system
				// message, or a statement on the unit's Item. Then the class name doesn't apply
				// any more, though.
				return $this->labelLookup->getLabel( $itemId )->getText();
			} catch ( OutOfBoundsException $ex ) {
				return $itemId->getSerialization();
			}
		} catch ( InvalidArgumentException $ex ) {
			return $idString;
		}
	}

}
