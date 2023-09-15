<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\Repo\RestApi\Application\UseCases\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class ValidatingRequestDeserializer {

	private ValidatingRequestFieldDeserializerFactory $factory;
	private array $validRequestResults = [];

	public function __construct( ValidatingRequestFieldDeserializerFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( UseCaseRequest $request ): array {
		$requestObjectId = spl_object_id( $request );
		if ( array_key_exists( $requestObjectId, $this->validRequestResults ) ) {
			return $this->validRequestResults[$requestObjectId];
		}

		$requestTypeToValidatorMap = [
			ItemIdRequest::class => [ $this->factory, 'newItemIdRequestValidatingDeserializer' ],
			PropertyIdRequest::class => [ $this->factory, 'newPropertyIdRequestValidatingDeserializer' ],
			StatementIdRequest::class => [ $this->factory, 'newStatementIdRequestValidatingDeserializer' ],
			PropertyIdFilterRequest::class => [ $this->factory, 'newPropertyIdFilterRequestValidatingDeserializer' ],
			LanguageCodeRequest::class => [ $this->factory, 'newLanguageCodeRequestValidatingDeserializer' ],
			ItemFieldsRequest::class => [ $this->factory, 'newItemFieldsRequestValidatingDeserializer' ],
			StatementSerializationRequest::class => [ $this->factory, 'newStatementSerializationRequestValidatingDeserializer' ],
			EditMetadataRequest::class => [ $this->factory, 'newEditMetadataRequestValidatingDeserializer' ],
			PatchRequest::class => [ $this->factory, 'newPatchRequestValidatingDeserializer' ],
			ItemLabelEditRequest::class => [ $this->factory, 'newItemLabelEditRequestValidatingDeserializer' ],
			ItemDescriptionEditRequest::class => [ $this->factory, 'newItemDescriptionEditRequestValidatingDeserializer' ],
		];
		$result = [];

		foreach ( $requestTypeToValidatorMap as $requestType => $newValidator ) {
			if ( array_key_exists( $requestType, class_implements( $request ) ) ) {
				$result[$requestType] = $newValidator()->validateAndDeserialize( $request );
			}
		}

		$this->validRequestResults[$requestObjectId] = $result;

		return $result;
	}

}
