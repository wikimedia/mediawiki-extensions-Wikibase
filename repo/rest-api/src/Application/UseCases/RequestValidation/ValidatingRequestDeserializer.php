<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class ValidatingRequestDeserializer {

	private ValidatingRequestFieldDeserializerFactory $factory;

	public function __construct( ValidatingRequestFieldDeserializerFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( UseCaseRequest $request ): array {
		$requestTypeToValidatorMap = [
			ItemIdRequest::class => [ $this->factory, 'newItemIdRequestValidatingDeserializer' ],
			PropertyIdRequest::class => [ $this->factory, 'newPropertyIdRequestValidatingDeserializer' ],
			StatementIdRequest::class => [ $this->factory, 'newStatementIdRequestValidatingDeserializer' ],
			PropertyIdFilterRequest::class => [ $this->factory, 'newPropertyIdFilterRequestValidatingDeserializer' ],
			LanguageCodeRequest::class => [ $this->factory, 'newLanguageCodeRequestValidatingDeserializer' ],
		];
		$result = [];

		foreach ( class_implements( $request ) as $requestType ) {
			if ( isset( $requestTypeToValidatorMap[$requestType] ) ) {
				$result = array_merge( $result, $requestTypeToValidatorMap[$requestType]()->validateAndDeserialize( $request ) );
			}
		}

		return $result;
	}

}
