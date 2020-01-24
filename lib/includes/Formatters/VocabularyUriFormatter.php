<?php

namespace Wikibase\Lib\Formatters;

use InvalidArgumentException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;

/**
 * A dedicated formatter for concept URIs referring to entities on a vocabulary repository.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class VocabularyUriFormatter implements ValueFormatter {

	/**
	 * @var EntityIdParser
	 */
	private $vocabUriParser;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelLookup;

	/**
	 * @var array A set of unitless IDs, represented as string keys.
	 */
	private $unitlessUnitIds;

	/**
	 * @param EntityIdParser $vocabUriParser
	 * @param LabelDescriptionLookup $labelLookup
	 * @param string[] $unitlessUnitIds A list of IDs that represent the "unitless" unit (one),
	 *        e.g. "http://www.wikidata.org/entity/Q199". The strings "" and "1" are always
	 *        treated as "non-units".
	 */
	public function __construct(
		EntityIdParser $vocabUriParser,
		LabelDescriptionLookup $labelLookup,
		array $unitlessUnitIds = []
	) {
		$this->vocabUriParser = $vocabUriParser;
		$this->labelLookup = $labelLookup;

		$this->unitlessUnitIds = array_flip( $unitlessUnitIds );
		$this->unitlessUnitIds[''] = true;
		$this->unitlessUnitIds['1'] = true;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param string $conceptUri
	 *
	 * @throws InvalidArgumentException
	 * @return string|null Null if the concept URI refers to a unitless unit. Otherwise a label or
	 * an entity ID or the original concept URI.
	 */
	public function format( $conceptUri ) {
		if ( !is_string( $conceptUri ) ) {
			throw new InvalidArgumentException( '$conceptUri must be a string' );
		}

		if ( array_key_exists( $conceptUri, $this->unitlessUnitIds ) ) {
			return null;
		}

		try {
			$entityId = $this->vocabUriParser->parse( $conceptUri );

			try {
				// TODO: Ideally we would show unit *symbols*, taking from a config file,
				// a system message, or a statement on the unit's item.
				$term = $this->labelLookup->getLabel( $entityId );
			} catch ( LabelDescriptionLookupException $ex ) {
				$term = null;
			}

			if ( $term !== null ) {
				return $term->getText();
			} else {
				// Fall back to the entity ID.
				return $entityId->getSerialization();
			}
		} catch ( EntityIdParsingException $ex ) {
			// Fall back to the raw concept URI.
			return $conceptUri;
		}
	}

}
