<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesException;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to search for Wikibase entities.
 *
 * @license GPL-2.0-or-later
 */
class SearchEntities extends ApiBase {

	/**
	 * @var EntitySearchHelper
	 */
	private $entitySearchHelper;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var EntityTitleTextLookup
	 */
	private $entityTitleTextLookup;

	/**
	 * @var EntityUrlLookup
	 */
	private $entityUrlLookup;

	/**
	 * @var EntityArticleIdLookup
	 */
	private $entityArticleIdLookup;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @see ApiBase::__construct
	 */
	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		EntitySearchHelper $entitySearchHelper,
		$unused, // backwards compat, removed later
		ContentLanguages $termLanguages,
		EntitySourceDefinitions $entitySourceDefinitions,
		EntityTitleTextLookup $entityTitleTextLookup,
		EntityUrlLookup $entityUrlLookup,
		EntityArticleIdLookup $entityArticleIdLookup,
		ApiErrorReporter $errorReporter
	) {
		parent::__construct( $mainModule, $moduleName, '' );

		// Always try to add a conceptUri to results if not already set
		$entitySearchHelper = new ConceptUriSearchHelper(
			$entitySearchHelper,
			$entitySourceDefinitions
		);

		$this->entitySearchHelper = $entitySearchHelper;
		$this->termsLanguages = $termLanguages;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->entityTitleTextLookup = $entityTitleTextLookup;
		$this->entityUrlLookup = $entityUrlLookup;
		$this->entityArticleIdLookup = $entityArticleIdLookup;
		$this->errorReporter = $errorReporter;
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

		$entries = [];
		foreach ( $searchResults as $match ) {
			$entries[] = $this->buildTermSearchMatchEntry( $match, $params['props'] );
		}

		return $entries;
	}

	/**
	 * @param TermSearchResult $match
	 * @param string[]|null $props
	 *
	 * @return array
	 */
	private function buildTermSearchMatchEntry( TermSearchResult $match, array $props = null ) {
		$entityId = $match->getEntityId();

		$entry = [
			'id' => $entityId->getSerialization(),
			'title' => $this->entityTitleTextLookup->getPrefixedText( $entityId ),
			'pageid' => $this->entityArticleIdLookup->getArticleId( $entityId )
		];

		/**
		 * The repository key should be deprecated and removed, for now avoid adding it when using federatedProperties to avoid confusion
		 * in the new feature and avoid the need to "fix" it..
		 * This is deliberately not tested and thus not injected as for federated properties we "don't care much" and for default Wikibase
		 * this is already covered by the SearchEntitiesTest.
		 */
		if ( !WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'federatedPropertiesEnabled' ) ) {
			$entry['repository'] = $this->getRepositoryOrEntitySourceName( $entityId );
		}

		if ( $props !== null && in_array( 'url', $props ) ) {
			$entry['url'] = $this->entityUrlLookup->getFullUrl( $entityId );
		}
		foreach ( $match->getMetaData() as $metaKey => $metaValue ) {
			$entry[$metaKey] = $metaValue;
		}

		$displayLabel = $match->getDisplayLabel();

		if ( $displayLabel !== null ) {
			$entry['label'] = $displayLabel->getText();
		}

		$displayDescription = $match->getDisplayDescription();

		if ( $displayDescription !== null ) {
			$entry['description'] = $displayDescription->getText();
		}

		$entry['match']['type'] = $match->getMatchedTermType();

		// Special handling for 'entityId's as these are not actually Term objects
		if ( $entry['match']['type'] === 'entityId' ) {
			$entry['match']['text'] = $entry['id'];
			$entry['aliases'] = [ $entry['id'] ];
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
				$entry['aliases'] = [ $matchedTerm->getText() ];
			}
		}

		return $entry;
	}

	private function getRepositoryOrEntitySourceName( EntityId $entityId ) {
		$source = $this->entitySourceDefinitions->getSourceForEntityType( $entityId->getEntityType() );
		if ( $source === null ) {
			return '';
		}
		return $source->getSourceName();
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		try {
			$this->executeInternal();
		} catch ( FederatedPropertiesException $ex ) {
			$this->errorReporter->dieWithError(
				'wikibase-federated-properties-search-api-error-message',
				'failed-property-search'
			);
		}
	}

	public function executeInternal() {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();

		$entries = $this->getSearchEntries( $params );

		$this->getResult()->addValue(
			null,
			'searchinfo',
			[
				'search' => $params['search']
			]
		);

		$this->getResult()->addValue(
			null,
			'search',
			[]
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

		$this->getResult()->addIndexedTagName( [ 'search' ], 'entity' );

		// @todo use result builder?
		$this->getResult()->addValue(
			null,
			'success',
			(int)true
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'search' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'language' => [
				self::PARAM_TYPE => $this->termsLanguages->getLanguages(),
				self::PARAM_REQUIRED => true,
			],
			'strictlanguage' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false
			],
			'type' => [
				self::PARAM_TYPE => array_keys( $this->entitySourceDefinitions->getEntityTypeToSourceMapping() ),
				self::PARAM_DFLT => 'item',
			],
			'limit' => [
				self::PARAM_TYPE => 'limit',
				self::PARAM_DFLT => 7,
				self::PARAM_MAX => self::LIMIT_SML1,
				self::PARAM_MAX2 => self::LIMIT_SML2,
				self::PARAM_MIN => 0,
			],
			'continue' => [
				self::PARAM_TYPE => 'integer',
				self::PARAM_REQUIRED => false,
				self::PARAM_DFLT => 0
			],
			'props' => [
				self::PARAM_TYPE => [ 'url' ],
				ApiBase::PARAM_ISMULTI => true,
				self::PARAM_DFLT => 'url',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=wbsearchentities&search=abc&language=en' =>
				'apihelp-wbsearchentities-example-1',
			'action=wbsearchentities&search=abc&language=en&limit=50' =>
				'apihelp-wbsearchentities-example-2',
			'action=wbsearchentities&search=abc&language=en&limit=2&continue=2' =>
				'apihelp-wbsearchentities-example-4',
			'action=wbsearchentities&search=alphabet&language=en&type=property' =>
				'apihelp-wbsearchentities-example-3',
			'action=wbsearchentities&search=alphabet&language=en&props=' =>
				'apihelp-wbsearchentities-example-5',
			'action=wbsearchentities&search=Q1234&language=en' =>
				'apihelp-wbsearchentities-example-6',
		];
	}

}
