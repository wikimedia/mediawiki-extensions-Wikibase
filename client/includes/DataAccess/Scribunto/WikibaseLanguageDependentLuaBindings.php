<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\Edrsf\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Edrsf\StorageException;

/**
 * Actual implementations of various functions to access Wikibase functionality
 * through Scribunto. Functions in here can dependend on the target language.
 *
 * @license GPL-2.0+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLanguageDependentLuaBindings {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var LanguageFallbackLabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup
	 * @param UsageAccumulator $usageAccumulator for tracking title usage via getEntityId.
	 *
	 * @note: label usage is not tracked in $usageAccumulator. This should be done inside
	 *        the $labelDescriptionLookup or an underlying TermsLookup.
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup,
		UsageAccumulator $usageAccumulator
	) {
		$this->entityIdParser = $entityIdParser;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->usageAccumulator = $usageAccumulator;
	}

	/**
	 * @param string $prefixedEntityId
	 *
	 * @return string[]|null[] Array containing label, label language code.
	 *     Null for both, if entity couldn't be found/ no label present.
	 */
	public function getLabel( $prefixedEntityId ) {
		try {
			$entityId = $this->entityIdParser->parse( $prefixedEntityId );
		} catch ( EntityIdParsingException $e ) {
			return [ null, null ];
		}

		try {
			$term = $this->labelDescriptionLookup->getLabel( $entityId );
		} catch ( StorageException $ex ) {
			// TODO: verify this catch is still needed
			return [ null, null ];
		} catch ( LabelDescriptionLookupException $ex ) {
			return [ null, null ];
		}

		if ( $term === null ) {
			return [ null, null ];
		}

		// NOTE: This tracks a label usage in the wiki's content language.
		return [ $term->getText(), $term->getActualLanguageCode() ];
	}

	/**
	 * @param string $prefixedEntityId
	 *
	 * @return string[]|null[] Array containing description, description language code.
	 *     Null for both, if entity couldn't be found/ no description present.
	 */
	public function getDescription( $prefixedEntityId ) {
		try {
			$entityId = $this->entityIdParser->parse( $prefixedEntityId );
		} catch ( EntityIdParsingException $e ) {
			return [ null, null ];
		}

		try {
			$term = $this->labelDescriptionLookup->getDescription( $entityId );
		} catch ( StorageException $ex ) {
			// TODO: verify this catch is still needed
			return [ null, null ];
		} catch ( LabelDescriptionLookupException $ex ) {
			return [ null, null ];
		}

		if ( $term === null ) {
			return [ null, null ];
		}

		// XXX: This. Sucks. A lot.
		// Also notes about language fallbacks from getLabel apply
		$this->usageAccumulator->addOtherUsage( $entityId );
		return [ $term->getText(), $term->getActualLanguageCode() ];
	}

}
