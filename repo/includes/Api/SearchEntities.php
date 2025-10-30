<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use InvalidArgumentException;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiResult;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesException;
use Wikibase\Repo\FederatedValues\EntitySearchContext;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\InvariantException;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

/**
 * API module to search for Wikibase entities.
 *
 * @license GPL-2.0-or-later
 */
class SearchEntities extends ApiBase {

	/**
	 * "Soft" limit on the "continue" parameter.
	 * Past this point, we won't add it to the response,
	 * though users can still ask for higher continuation offsets manually.
	 */
	private const CONTINUE_SOFT_LIMIT = self::LIMIT_SML1;

	/**
	 * "Hard" limit on the "continue" parameter.
	 * Past this point, continuation is not allowed (T355251).
	 * The value is mostly arbitrary (could be somewhat higher or lower),
	 * but chosen to coincide with CirrusSearch's Searcher::MAX_OFFSET_LIMIT:
	 * when using CirrusSearch, it's not possible to get more than 10000 search results anyway.
	 */
	private const CONTINUE_HARD_LIMIT = 10000;

	private LinkBatchFactory $linkBatchFactory;

	private EntitySearchHelper $entitySearchHelper;

	private ContentLanguages $termsLanguages;

	private EntitySourceLookup $entitySourceLookup;

	private EntityTitleLookup $entityTitleLookup;

	private EntityTitleTextLookup $entityTitleTextLookup;

	private EntityUrlLookup $entityUrlLookup;

	private EntityArticleIdLookup $entityArticleIdLookup;

	private ApiErrorReporter $errorReporter;

	private array $enabledEntityTypes;

	/** @var (string|null)[] */
	private array $searchProfiles;

	/**
	 * @see ApiBase::__construct
	 */
	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		LinkBatchFactory $linkBatchFactory,
		EntitySearchHelper $entitySearchHelper,
		ContentLanguages $termLanguages,
		EntitySourceLookup $entitySourceLookup,
		EntityTitleLookup $entityTitleLookup,
		EntityTitleTextLookup $entityTitleTextLookup,
		EntityUrlLookup $entityUrlLookup,
		EntityArticleIdLookup $entityArticleIdLookup,
		ApiErrorReporter $errorReporter,
		array $enabledEntityTypes,
		array $searchProfiles
	) {
		parent::__construct( $mainModule, $moduleName, '' );

		// Always try to add a conceptUri to results if not already set
		$this->entitySearchHelper = new ConceptUriSearchHelper( $entitySearchHelper, $entitySourceLookup );

		$this->linkBatchFactory = $linkBatchFactory;
		$this->termsLanguages = $termLanguages;
		$this->entitySourceLookup = $entitySourceLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityTitleTextLookup = $entityTitleTextLookup;
		$this->entityUrlLookup = $entityUrlLookup;
		$this->entityArticleIdLookup = $entityArticleIdLookup;
		$this->errorReporter = $errorReporter;
		$this->enabledEntityTypes = $enabledEntityTypes;
		$this->searchProfiles = $searchProfiles;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		LinkBatchFactory $linkBatchFactory,
		ApiHelperFactory $apiHelperFactory,
		array $enabledEntityTypes,
		EntityArticleIdLookup $entityArticleIdLookup,
		EntitySearchHelper $entitySearchHelper,
		EntitySourceLookup $entitySourceLookup,
		EntityTitleLookup $entityTitleLookup,
		EntityTitleTextLookup $entityTitleTextLookup,
		EntityUrlLookup $entityUrlLookup,
		SettingsArray $repoSettings,
		ContentLanguages $termsLanguages
	): self {

		return new self(
			$mainModule,
			$moduleName,
			$linkBatchFactory,
			$entitySearchHelper,
			$termsLanguages,
			$entitySourceLookup,
			$entityTitleLookup,
			$entityTitleTextLookup,
			$entityUrlLookup,
			$entityArticleIdLookup,
			$apiHelperFactory->getErrorReporter( $mainModule ),
			$enabledEntityTypes,
			$repoSettings->getSetting( 'searchProfiles' )
		);
	}

	/**
	 * Populates the search result returning the number of requested matches plus one additional
	 * item for being able to determine if there would be any more results.
	 * If there are not enough exact matches, the list of returned entries will be additionally
	 * filled with prefixed matches.
	 *
	 * @param array $params
	 *
	 * @return TermSearchResult[]
	 * @throws ApiUsageException
	 */
	private function getSearchResults( array $params ): array {
		try {
			return $this->entitySearchHelper->getRankedSearchResults(
				$params['search'],
				$params['language'],
				$params['type'],
				$params['continue'] + $params['limit'] + 1,
				$params['strictlanguage'],
				$this->searchProfiles[$params['profile']]
			);
		} catch ( EntitySearchException $ese ) {
			$this->dieStatus( $ese->getStatus() );

			// @phan-suppress-next-line PhanPluginUnreachableCode Wanted
			throw new InvariantException( "dieStatus() must throw an exception" );
		}
	}

	/**
	 * @param TermSearchResult $match
	 * @param string[]|null $props
	 *
	 * @return array
	 */
	private function buildTermSearchMatchEntry( TermSearchResult $match, ?array $props ): array {
		$entry = $this->buildTermSearchMatchPageEntry( $match, $props );
		$entry = $this->buildTermSearchMatchDisplayEntry( $match, $entry );
		return $entry;
	}

	/**
	 * @param TermSearchResult $match
	 * @param string[]|null $props
	 */
	private function buildTermSearchMatchPageEntry( TermSearchResult $match, ?array $props ): array {
		$entityId = $match->getEntityId();
		if ( $entityId !== null ) {
			$entry = [
				'id' => $entityId->getSerialization(),
				'title' => $this->entityTitleTextLookup->getPrefixedText( $entityId ),
				'pageid' => $this->entityArticleIdLookup->getArticleId( $entityId ),
			];
		} else {
			$entry = [
				// id, title, pageid added via metadata (see below)
			];
		}

		$metaData = $match->getMetaData();
		foreach ( $metaData as $metaKey => $metaValue ) {
			$entry[$metaKey] = $metaValue;
		}

		if ( $entityId !== null ) {
			/**
			 * The repository key should be deprecated and removed, for now avoid adding it when using federatedProperties
			 * to avoid confusion in the new feature and avoid the need to "fix" it..
			 * This is deliberately not tested and thus not injected as for federated properties we "don't care much"
			 * and for default Wikibase this is already covered by the SearchEntitiesTest.
			 */
			if ( !WikibaseRepo::getSettings()->getSetting( 'federatedPropertiesEnabled' ) ) {
				$entry['repository'] = $this->getRepositoryOrEntitySourceName( $entityId );
			}

			if ( $props !== null && in_array( 'url', $props ) ) {
				$entry['url'] = $this->entityUrlLookup->getFullUrl( $entityId );
			}
		} else {
			foreach ( [ 'id', 'title', 'pageid', 'url' ] as $key ) {
				if ( !array_key_exists( $key, $metaData ) ) {
					throw new InvalidArgumentException(
						'Invalid TermSearchResult: ' .
						"if id is null, then $key must be set in the metadata!"
					);
				}
			}

			if ( $props === null || !in_array( 'url', $props ) ) {
				unset( $entry['url'] );
			}
		}

		return $entry;
	}

	private function buildTermSearchMatchDisplayEntry( TermSearchResult $match, array $entry ): array {
		$entry['display'] = [];
		ApiResult::setArrayType( $entry['display'], 'assoc' );

		$displayLabel = $match->getDisplayLabel();

		if ( $displayLabel !== null ) {
			$entry['display']['label'] = $this->getDisplayTerm( $displayLabel );
			$entry['label'] = $displayLabel->getText();
		}

		$displayDescription = $match->getDisplayDescription();

		if ( $displayDescription !== null ) {
			$entry['display']['description'] = $this->getDisplayTerm( $displayDescription );
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

	private function getRepositoryOrEntitySourceName( EntityId $entityId ): string {
		return $this->entitySourceLookup->getEntitySourceById( $entityId )->getSourceName();
	}

	private function getDisplayTerm( Term $term ): array {
		return [
			'value' => $term->getText(),
			'language' => $term instanceof TermFallback
				? $term->getActualLanguageCode()
				: $term->getLanguageCode(),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		try {
			$this->executeInternal();
		} catch ( FederatedPropertiesException ) {
			$this->errorReporter->dieWithError(
				'wikibase-federated-properties-search-api-error-message',
				'failed-property-search'
			);
		}
	}

	/**
	 * @throws ApiUsageException
	 * @throws EntitySearchException
	 */
	public function executeInternal(): void {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();

		// TODO: Federated Values - Merge with local results
		if (
			WikibaseRepo::getSettings()->getSetting( 'federatedValuesEnabled' ) &&
			($params['type'] ?? '') === 'item' &&
			($params['searchcontext'] ?? '') === EntitySearchContext::VALUE
		) {
				// Build a clean param set for Wikidata
				// proxy identical query to Wikidata when searching for a value
				$remote = [
						'action'      => 'wbsearchentities',
						'format'      => 'json',
						'errorformat' => 'plaintext',
						'search'      => (string)$params['search'],
						'language'    => (string)$params['language'],
						'uselang'     => (string)($params['uselang'] ?? $params['language']),
						'type'        => 'item',
				];

				if ( isset( $params['limit'] ) ) {
						$remote['limit'] = (int)$params['limit'];
				}
				if ( isset( $params['continue'] ) ) {
						$remote['continue'] = (int)$params['continue'];
				}
				if ( array_key_exists( 'strictlanguage', $params ) ) {
						// wbsearchentities expects 1/0
						$remote['strictlanguage'] = $params['strictlanguage'] ? 1 : 0;
				}
				if ( isset( $params['profile'] ) ) {
						$remote['profile'] = (string)$params['profile'];
				}
				if ( isset( $params['props'] ) ) {
						// flatten multi to pipe-separated
						$remote['props'] = is_array( $params['props'] ) ? implode( '|', $params['props'] ) : (string)$params['props'];
				}

				// Use GET with query string (mirrors how the UI calls the local API)
				// TODO: Federated Values - get from settings
				$remoteUrl = 'https://www.wikidata.org/w/api.php?' . \wfArrayToCgi( $remote );

				$http = \MediaWiki\MediaWikiServices::getInstance()->getHttpRequestFactory();
				$req  = $http->create( $remoteUrl, [
						'method'  => 'GET',
						'timeout' => 10,
				] );

				$status = $req->execute();
				if ( !$status->isOK() ) {
						$this->dieWithError( [ 'apierror-badaccess-generic', 'Remote search request failed' ] );
				}

				$resp = \FormatJson::decode( $req->getContent(), true ) ?: [];

				// Return fields with the same shape as the local path
				$result = $this->getResult();

				// (Optional) debug: inspect the raw remote response
				// wfDebugLog( 'wikibase', 'wikidata wbsearchentities response: ' . json_encode( $resp ) );

				if ( array_key_exists( 'searchinfo', $resp ) ) {
						$result->addValue( null, 'searchinfo', $resp['searchinfo'] );
				} else {
						$result->addValue( null, 'searchinfo', [ 'search' => (string)$params['search'] ] );
				}

				$result->addValue( null, 'search', $resp['search'] ?? [] );

				if ( isset( $resp['search-continue'] ) ) {
						$result->addValue( null, 'search-continue', $resp['search-continue'] );
				}

				$this->getResult()->addIndexedTagName( [ 'search' ], 'entity' );
				$result->addValue( null, 'success', 1 );
				return;
		}

		$results = $this->getSearchResults( $params );

		$this->getResult()->addValue(
			null,
			'searchinfo',
			[
				'search' => $params['search'],
			]
		);

		$this->getResult()->addValue(
			null,
			'search',
			[]
		);

		// getSearchResults returns one more item than requested in order to determine if there
		// would be any more results coming up.
		$hits = count( $results );

		// slice off the extra results at the beginning that $params['continue'] "skips" over
		$returnedResults = array_slice( $results, $params['continue'], $params['limit'] );

		// prefetch page IDs
		$this->linkBatchFactory->newLinkBatch( array_map(
			fn ( TermSearchResult $match ) => $this->entityTitleLookup->getTitleForId( $match->getEntityId() ),
			array_filter(
				$returnedResults,
				fn ( TermSearchResult $match ) => $match->getEntityId() !== null
			)
		) )->execute();

		// Actual result set.
		$entries = [];
		foreach ( $returnedResults as $match ) {
			$entries[] = $this->buildTermSearchMatchEntry( $match, $params['props'] );
		}

		$nextContinuation = $params['continue'] + $params['limit'];

		// Only pass search-continue param if there are more results and the maximum continuation
		// limit is not exceeded.
		if ( $hits > $nextContinuation && $nextContinuation <= self::CONTINUE_SOFT_LIMIT ) {
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
	protected function getAllowedParams(): array {
		return [
			'search' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'language' => [
				ParamValidator::PARAM_TYPE => $this->termsLanguages->getLanguages(),
				ParamValidator::PARAM_REQUIRED => true,
			],
			// Federated Values
			'searchcontext' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
			],
			// END Federated Values
			'strictlanguage' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false,
			],
			'type' => [
				ParamValidator::PARAM_TYPE => $this->enabledEntityTypes,
				ParamValidator::PARAM_DEFAULT => 'item',
			],
			'limit' => [
				ParamValidator::PARAM_TYPE => 'limit',
				ParamValidator::PARAM_DEFAULT => 7,
				IntegerDef::PARAM_MAX => self::LIMIT_SML1,
				IntegerDef::PARAM_MAX2 => self::LIMIT_SML2,
				IntegerDef::PARAM_MIN => 0,
			],
			'continue' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 0,
				IntegerDef::PARAM_MAX => self::CONTINUE_HARD_LIMIT,
				IntegerDef::PARAM_MIN => 0,
			],
			'props' => [
				ParamValidator::PARAM_TYPE => [ 'url' ],
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_DEFAULT => 'url',
			],
			'profile' => [
				ParamValidator::PARAM_TYPE => array_keys( $this->searchProfiles ),
				ParamValidator::PARAM_DEFAULT => array_key_first( $this->searchProfiles ),
				self::PARAM_HELP_MSG_PER_VALUE => [],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
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
