<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use Exception;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchResponse;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\MediaWikiSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\SqlTermStoreSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\LanguageCodeValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchRouteHandler extends SimpleHandler {

	private const SEARCH_QUERY_PARAM = 'q';
	private const LANGUAGE_QUERY_PARAM = 'language';

	private SimpleItemSearch $useCase;

	public function __construct( SimpleItemSearch $useCase ) {
		$this->useCase = $useCase;
	}

	public static function factory(): Handler {
		return new self(
			new SimpleItemSearch(
				self::newUseCaseValidator(),
				self::newSearchEngine()
			)
		);
	}

	private static function newUseCaseValidator(): SimpleItemSearchValidator {
		$validators = [];
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new MembershipValidator( WikibaseRepo::getTermsLanguages()->getLanguages(), 'not-a-language' );
		$validators[] = new NotMulValidator( MediaWikiServices::getInstance()->getLanguageNameUtils() );

		return new SimpleItemSearchValidator(
			new LanguageCodeValidator(
				new CompositeValidator( $validators, true )
			)
		);
	}

	private static function newSearchEngine(): ItemSearchEngine {
		global $wgSearchType;

		$mediaWikiServices = MediaWikiServices::getInstance();
		$isWikibaseCirrusSearchEnabled = $mediaWikiServices->getExtensionRegistry()->isLoaded( 'WikibaseCirrusSearch' );
		$isCirrusSearchEnabled = $wgSearchType === 'CirrusSearch';
		$useMediaWikiSearchEngine = $isCirrusSearchEnabled && $isWikibaseCirrusSearchEnabled;

		return $useMediaWikiSearchEngine
			? new MediaWikiSearchEngine(
				$mediaWikiServices->getSearchEngineFactory()->create(),
				WikibaseRepo::getEntityNamespaceLookup(),
				RequestContext::getMain()
			)
			: new SqlTermStoreSearchEngine(
				WikibaseRepo::getMatchingTermsLookupFactory()
					->getLookupForSource( WikibaseRepo::getLocalEntitySource() ),
				WikibaseRepo::getTermLookup()
			);
	}

	/**
	 * @throws HttpException
	 */
	public function run(): Response {
		try {
			$useCaseResponse = $this->useCase->execute( new SimpleItemSearchRequest(
				$this->getValidatedParams()[self::SEARCH_QUERY_PARAM],
				$this->getValidatedParams()[self::LANGUAGE_QUERY_PARAM]
			) );
		} catch ( UseCaseError $e ) {
			return $this->newErrorResponse( $e->getErrorCode(), $e->getErrorMessage(), $e->getErrorContext() );
		} catch ( Exception $e ) {
			throw new HttpException( $e->getMessage() );
		}

		return $this->newSuccessResponse( $useCaseResponse );
	}

	private function newSuccessResponse( SimpleItemSearchResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setBody(
			new StringStream(
				json_encode( [ 'results' => $this->formatResults( $useCaseResponse->getResults() ) ], JSON_UNESCAPED_SLASHES )
			)
		);

		return $httpResponse;
	}

	private function newErrorResponse( string $code, string $message, ?array $context = null ): Response {
		$httpResponse = new Response();
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'Content-Language', 'en' );
		$httpResponse->setStatus( ErrorResponseToHttpStatus::lookup( $code ) );
		$httpResponse->setBody( new StringStream( json_encode(
			// use array_filter to remove 'context' from array if $context is NULL
			array_filter( [ 'code' => $code, 'message' => $message, 'context' => $context ] ),
			JSON_UNESCAPED_SLASHES
		) ) );

		return $httpResponse;
	}

	private function formatResults( ItemSearchResults $results ): array {
		return array_map(
			fn( ItemSearchResult $result ) => [
				'id' => $result->getItemId()->getSerialization(),
				'label' => $result->getLabel() ? [
					'language' => $result->getLabel()->getLanguageCode(),
					'value' => $result->getLabel()->getText(),
				] : null,
				'description' => $result->getDescription() ? [
					'language' => $result->getDescription()->getLanguageCode(),
					'value' => $result->getDescription()->getText(),
				] : null,
			],
			iterator_to_array( $results )
		);
	}

	public function getParamSettings(): array {
		return [
			self::SEARCH_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => false,
			],
			self::LANGUAGE_QUERY_PARAM => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => false,
			],
		];
	}

}
