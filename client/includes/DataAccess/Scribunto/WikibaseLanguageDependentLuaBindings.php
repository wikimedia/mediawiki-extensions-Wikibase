<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\StorageException;

/**
 * Actual implementations of various functions to access Wikibase functionality
 * through Scribunto. Functions in here can depend on the target language.
 *
 * @license GPL-2.0-or-later
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLanguageDependentLuaBindings {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var FallbackLabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param FallbackLabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @note label usage is not tracked in $usageAccumulator. This should be done inside
	 *        the $labelDescriptionLookup or an underlying TermsLookup.
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		FallbackLabelDescriptionLookup $labelDescriptionLookup
	) {
		$this->entityIdParser = $entityIdParser;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
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
			// @phan-suppress-previous-line PhanPluginDuplicateCatchStatementBody
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
			// @phan-suppress-previous-line PhanPluginDuplicateCatchStatementBody
			return [ null, null ];
		}

		if ( $term === null ) {
			return [ null, null ];
		}

		return [ $term->getText(), $term->getActualLanguageCode() ];
	}

}
