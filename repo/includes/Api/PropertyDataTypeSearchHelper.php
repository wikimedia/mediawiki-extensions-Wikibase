<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * EntitySearchHelper decorator that adds property data types to the TermSearchResult meta data
 *
 * @license GPL-2.0-or-later
 */
class PropertyDataTypeSearchHelper implements EntitySearchHelper {

	public const DATATYPE_META_DATA_KEY = 'datatype';

	/**
	 * @var EntitySearchHelper
	 */
	private $searchHelper;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	public function __construct( EntitySearchHelper $searchHelper, PropertyDataTypeLookup $dataTypeLookup ) {
		$this->searchHelper = $searchHelper;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	public function getRankedSearchResults( $text, $languageCode, $entityType, $limit, $strictLanguage ) {
		$results = $this->searchHelper->getRankedSearchResults( $text, $languageCode, $entityType, $limit, $strictLanguage );

		return array_map( function ( TermSearchResult $searchResult ) {
			/** @var PropertyId $propertyId */
			$propertyId = $searchResult->getEntityId();
			'@phan-var PropertyId $propertyId';

			return new TermSearchResult(
				$searchResult->getMatchedTerm(),
				$searchResult->getMatchedTermType(),
				$propertyId,
				$searchResult->getDisplayLabel(),
				$searchResult->getDisplayDescription(),
				array_merge(
					$searchResult->getMetaData(),
					[ self::DATATYPE_META_DATA_KEY => $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId ) ]
				) );
		}, $results );
	}

}
