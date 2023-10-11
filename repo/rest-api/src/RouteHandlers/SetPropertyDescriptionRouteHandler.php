<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use MediaWiki\Rest\Validator\BodyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class SetPropertyDescriptionRouteHandler extends SimpleHandler {

	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';
	private const DESCRIPTION_BODY_PARAM = 'description';
	private const TAGS_BODY_PARAM = 'tags';
	private const BOT_BODY_PARAM = 'bot';
	private const COMMENT_BODY_PARAM = 'comment';

	private SetPropertyDescription $useCase;
	private ResponseFactory $responseFactory;

	public function __construct( SetPropertyDescription $useCase, ResponseFactory $responseFactory ) {
		$this->useCase = $useCase;
		$this->responseFactory = $responseFactory;
	}

	public static function factory(): self {
		return new self( WbRestApi::getSetPropertyDescription(), new ResponseFactory() );
	}

	public function run( string $propertyId, string $languageCode ): Response {
		$jsonBody = $this->getValidatedBody();

		try {
			return $this->newSuccessHttpResponse(
				$this->useCase->execute(
					new SetPropertyDescriptionRequest(
						$propertyId,
						$languageCode,
						$jsonBody[self::DESCRIPTION_BODY_PARAM],
						$jsonBody[self::TAGS_BODY_PARAM],
						$jsonBody[self::BOT_BODY_PARAM],
						$jsonBody[self::COMMENT_BODY_PARAM],
						null
					)
				)
			);
		} catch ( UseCaseError $e ) {
			return $this->responseFactory->newErrorResponseFromException( $e );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ): BodyValidator {
		return $contentType === 'application/json' ?
			new TypeValidatingJsonBodyValidator( [
				self::DESCRIPTION_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				self::TAGS_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'array',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => [],
				],
				self::BOT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'boolean',
					ParamValidator::PARAM_REQUIRED => false,
					ParamValidator::PARAM_DEFAULT => false,
				],
				self::COMMENT_BODY_PARAM => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false,
				],
			] ) : parent::getBodyValidator( $contentType );
	}

	public function getParamSettings(): array {
		return [
			self::PROPERTY_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::LANGUAGE_CODE_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	private function newSuccessHttpResponse( SetPropertyDescriptionResponse $useCaseResponse ): Response {
		$httpResponse = $this->getResponseFactory()->create();
		$httpResponse->setStatus( $useCaseResponse->wasReplaced() ? 200 : 201 );
		$httpResponse->setHeader( 'Content-Type', 'application/json' );
		$httpResponse->setHeader( 'ETag', "\"{$useCaseResponse->getRevisionId()}\"" );
		$httpResponse->setHeader(
			'Last-Modified',
			wfTimestamp( TS_RFC2822, $useCaseResponse->getLastModified() )
		);
		$httpResponse->setBody(
			new StringStream( json_encode( $useCaseResponse->getDescription()->getText() ) )
		);

		return $httpResponse;
	}

}
