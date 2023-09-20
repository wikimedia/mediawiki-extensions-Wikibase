<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement\RemovePropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ValidatingRequestDeserializer	implements
	AddItemStatementValidator,
	AddPropertyStatementValidator,
	GetItemValidator,
	GetItemAliasesValidator,
	GetItemAliasesInLanguageValidator,
	GetItemDescriptionValidator,
	GetItemDescriptionsValidator,
	GetItemLabelValidator,
	GetItemLabelsValidator,
	GetItemStatementValidator,
	GetItemStatementsValidator,
	GetPropertyValidator,
	GetPropertyLabelsValidator,
	GetPropertyStatementValidator,
	GetPropertyStatementsValidator,
	GetStatementValidator,
	PatchItemLabelsValidator,
	PatchItemStatementValidator,
	PatchPropertyStatementValidator,
	PatchStatementValidator,
	RemoveItemStatementValidator,
	RemovePropertyStatementValidator,
	RemoveStatementValidator,
	ReplaceItemStatementValidator,
	ReplacePropertyStatementValidator,
	ReplaceStatementValidator,
	SetItemDescriptionValidator,
	SetItemLabelValidator
{

	private ValidatingRequestFieldDeserializerFactory $factory;
	private array $validRequestResults = [];

	public function __construct( ValidatingRequestFieldDeserializerFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( UseCaseRequest $request ): DeserializedRequestAdapter {
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
			PropertyFieldsRequest::class => [ $this->factory, 'newPropertyFieldsRequestValidatingDeserializer' ],
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

		$this->validRequestResults[$requestObjectId] = new DeserializedRequestAdapter( $result );

		return $this->validRequestResults[$requestObjectId];
	}

}
