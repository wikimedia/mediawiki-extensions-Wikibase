<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use LogicException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * API module to search for Wikibase entities.
 *
 * @license GPL-2.0+
 */
class SearchEntities extends ApiBase {

	/**
	 * @var EntitySearchHelper
	 */
	private $entitySearchHelper;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var string[]
	 */
	private $conceptBaseUris;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param EntitySearchHelper $entitySearchHelper
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param ContentLanguages $termLanguages
	 * @param string[] $entityTypes
	 * @param string[] $conceptBaseUris Associative array mapping repository names to base URIs of concept URIs
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		EntitySearchHelper $entitySearchHelper,
		EntityTitleLookup $entityTitleLookup,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ContentLanguages $termLanguages,
		array $entityTypes,
		array $conceptBaseUris
	) {
		parent::__construct( $mainModule, $moduleName, '' );

		$this->entitySearchHelper = $entitySearchHelper;
		$this->titleLookup = $entityTitleLookup;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->termsLanguages = $termLanguages;
		$this->entityTypes = $entityTypes;
		$this->conceptBaseUris = $conceptBaseUris;
	}

	/**
	 * Populates the search result returning the number of requested matches plus one additional
	 * item for being able to determine if there would be any more results.
	 * If there are not enough exact matches, the list of returned entries will be additionally
	 * filled with prefixed matches.
	 *
	 * @param array $params
	 *
	 * @return array[]
	 */
	private function getSearchEntries( array $params ) {
		$searchResults = $this->entitySearchHelper->getRankedSearchResults(
			$params['search'],
			$params['language'],
			$params['type'],
			$params['continue'] + $params['limit'] + 1,
			$params['strictlanguage']
		);

		$entries = array();

		foreach ( $searchResults as $match ) {
			$entries[] = $this->buildTermSearchMatchEntry( $match );
		}

		return $entries;
	}

	/**
	 * @param TermSearchResult $match
	 *
	 * @return array
	 */
	private function buildTermSearchMatchEntry( $match ) {
		// TODO: use EntityInfoBuilder, EntityInfoTermLookup
		$entityId = $match->getEntityId();
		$title = $this->titleLookup->getTitleForId( $entityId );

		$entry = array(
			'repository' => $entityId->getRepositoryName(),
			'id' => $entityId->getSerialization(),
			'concepturi' => $this->getConceptUri( $entityId ),
			'url' => $title->getFullURL(),
			'title' => $title->getPrefixedText(),
			'pageid' => $title->getArticleID()
		);

		if ( $entityId instanceof PropertyId ) {
			$entry['datatype'] = $this->propertyDataTypeLookup
				->getDataTypeIdForProperty( $entityId );
		}

		$displayLabel = $match->getDisplayLabel();

		if ( !is_null( $displayLabel ) ) {
			$entry['label'] = $displayLabel->getText();
		}

		$displayDescription = $match->getDisplayDescription();

		if ( !is_null( $displayDescription ) ) {
			$entry['description'] = $displayDescription->getText();
		}

		$entry['match']['type'] = $match->getMatchedTermType();

		// Special handling for 'entityId's as these are not actually Term objects
		if ( $entry['match']['type'] === 'entityId' ) {
			$entry['match']['text'] = $entry['id'];
			$entry['aliases'] = array( $entry['id'] );
		} else {
			$matchedTerm = $match->getMatchedTerm();
			$matchedTermText = $matchedTerm->getText();
			$entry['match']['language'] = $matchedTerm->getLanguageCode();
			$entry['match']['text'] = $matchedTermText;

			/**
			 * Add matched terms to the aliases key in the result to give some context
			 * for the matched Term if the matched term is different to the alias.
			 * XXX: This appears odd but is used in the UI / Entity suggesters
			 */
			if ( !array_key_exists( 'label', $entry ) || $matchedTermText != $entry['label'] ) {
				$entry['aliases'] = array( $matchedTerm->getText() );
			}
		}

		return $entry;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getConceptUri( EntityId $entityId ) {
		$baseUri = $this->getConceptBaseUri( $entityId );
		return $baseUri . $entityId->getLocalPart();
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LogicException when there is no base URI for the repository $entityId belongs to
	 *
	 * @return string
	 */
	private function getConceptBaseUri( EntityId $entityId ) {
		if ( !isset( $this->conceptBaseUris[$entityId->getRepositoryName()] ) ) {
			throw new LogicException( 'No base URI for for concept URI for repository: ' . $entityId->getRepositoryName() );
		}

		return $this->conceptBaseUris[$entityId->getRepositoryName()];
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();

		$entries = $this->getSearchEntries( $params );

		$this->getResult()->addValue(
			null,
			'searchinfo',
			array(
				'search' => $params['search']
			)
		);

		$this->getResult()->addValue(
			null,
			'search',
			array()
		);

		// getSearchEntities returns one more item than requested in order to determine if there
		// would be any more results coming up.
		$hits = count( $entries );

		// Actual result set.
		$entries = array_slice( $entries, $params['continue'], $params['limit'] );

		$nextContinuation = $params['continue'] + $params['limit'];

		// Only pass search-continue param if there are more results and the maximum continuation
		// limit is not exceeded.
		if ( $hits > $nextContinuation && $nextContinuation <= self::LIMIT_SML1 ) {
			$this->getResult()->addValue(
				null,
				'search-continue',
				$nextContinuation
			);
		}

		$this->getResult()->addValue(
			null,
			'search',
			$entries
		);

		$this->getResult()->addIndexedTagName( array( 'search' ), 'entity' );

		// @todo use result builder?
		$this->getResult()->addValue(
			null,
			'success',
			(int)true
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array(
			'search' => array(
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			),
			'language' => array(
				self::PARAM_TYPE => $this->termsLanguages->getLanguages(),
				self::PARAM_REQUIRED => true,
			),
			'strictlanguage' => array(
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false
			),
			'type' => array(
				self::PARAM_TYPE => $this->entityTypes,
				self::PARAM_DFLT => 'item',
			),
			'limit' => array(
				self::PARAM_TYPE => 'limit',
				self::PARAM_DFLT => 7,
				self::PARAM_MAX => self::LIMIT_SML1,
				self::PARAM_MAX2 => self::LIMIT_SML2,
				self::PARAM_MIN => 0,
				self::PARAM_RANGE_ENFORCE => true,
			),
			'continue' => array(
				self::PARAM_TYPE => 'integer',
				self::PARAM_REQUIRED => false,
				self::PARAM_DFLT => 0
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsearchentities&search=abc&language=en' =>
				'apihelp-wbsearchentities-example-1',
			'action=wbsearchentities&search=abc&language=en&limit=50' =>
				'apihelp-wbsearchentities-example-2',
			'action=wbsearchentities&search=abc&language=en&limit=2&continue=2' =>
				'apihelp-wbsearchentities-example-4',
			'action=wbsearchentities&search=alphabet&language=en&type=property' =>
				'apihelp-wbsearchentities-example-3',
		);
	}

}
