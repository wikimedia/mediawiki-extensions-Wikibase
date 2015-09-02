<?php

namespace Wikibase\Lib;

use ValueFormatters\QuantityUnitFormatter;

/**
 * QuantityUnitFormatter for representing units by their respective entity label.
 *
 * @since 0.5
 * @deprecated since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityLabelUnitFormatter implements QuantityUnitFormatter {

	/**
	 * @var VocabularyUriFormatter
	 */
	private $vocabularyUriFormatter;

	/**
	 * @param VocabularyUriFormatter $vocabularyUriFormatter
	 */
	public function __construct( VocabularyUriFormatter $vocabularyUriFormatter ) {
		$this->vocabularyUriFormatter = $vocabularyUriFormatter;
	}

	/**
	 * @see QuantityUnitFormatter::applyUnit
	 *
	 * This implementation will interpret $unit as an external entity ID (typically a URI), which
	 * can be parsed using the EntityIdParser supplied to the constructor. If $unit is successfully
	 * parsed, the label of the entity is looked up, and appended to $numberText with a single
	 * space as a separator.
	 *
	 * @param string $conceptUri
	 * @param string $numberText
	 *
	 * @return string Text
	 */
	public function applyUnit( $conceptUri, $numberText ) {
		$label = $this->vocabularyUriFormatter->format( $conceptUri );

		if ( $label === null ) {
			return $numberText;
		}

		// TODO: localizable pattern for placement (before/after, separator)
		return $numberText . ' ' . $label;
	}

}
