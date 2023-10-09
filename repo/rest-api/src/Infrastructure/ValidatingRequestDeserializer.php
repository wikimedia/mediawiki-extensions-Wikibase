<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Psr\Container\ContainerInterface;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;
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
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\GetPropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\GetPropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsValidator;
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
	GetItemLabelsValidator,
	GetItemLabelValidator,
	GetItemDescriptionsValidator,
	GetItemDescriptionValidator,
	GetItemAliasesValidator,
	GetItemAliasesInLanguageValidator,
	GetItemStatementValidator,
	GetItemStatementsValidator,
	GetPropertyValidator,
	GetPropertyLabelsValidator,
	GetPropertyDescriptionsValidator,
	GetPropertyAliasesValidator,
	GetPropertyAliasesInLanguageValidator,
	GetPropertyStatementValidator,
	GetPropertyStatementsValidator,
	GetStatementValidator,
	PatchItemLabelsValidator,
	PatchItemDescriptionsValidator,
	PatchItemAliasesValidator,
	PatchItemStatementValidator,
	PatchPropertyStatementValidator,
	PatchStatementValidator,
	RemoveItemStatementValidator,
	RemovePropertyStatementValidator,
	RemoveStatementValidator,
	ReplaceItemStatementValidator,
	ReplacePropertyStatementValidator,
	ReplaceStatementValidator,
	SetItemLabelValidator,
	SetItemDescriptionValidator,
	GetPropertyLabelValidator,
	GetPropertyDescriptionValidator
{
	private const PREFIX = 'WbRestApi.RequestValidation.';
	public const ITEM_ID_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemIdRequestValidatingDeserializer';
	public const PROPERTY_ID_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PropertyIdRequestValidatingDeserializer';
	public const STATEMENT_ID_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'StatementIdRequestValidatingDeserializer';
	public const PROPERTY_ID_FILTER_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PropertyIdFilterRequestValidatingDeserializer';
	public const LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'LanguageCodeRequestValidatingDeserializer';
	public const ITEM_FIELDS_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemFieldsRequestValidatingDeserializer';
	public const PROPERTY_FIELDS_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PropertyFieldsRequestValidatingDeserializer';
	public const STATEMENT_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER =
		self::PREFIX . 'StatementSerializationRequestValidatingDeserializer';
	public const EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'EditMetadataRequestValidatingDeserializer';
	public const PATCH_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PatchRequestValidatingDeserializer';
	public const ITEM_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemLabelEditRequestValidatingDeserializer';
	public const ITEM_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemDescriptionEditRequestValidatingDeserializer';

	private ContainerInterface $serviceContainer;
	private array $validRequestResults = [];

	/**
	 * @param ContainerInterface $serviceContainer Using the service container here allows us to lazily instantiate only the validators that
	 *   are needed for the request object.
	 */
	public function __construct( ContainerInterface $serviceContainer ) {
		$this->serviceContainer = $serviceContainer;
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
			ItemIdRequest::class => self::ITEM_ID_REQUEST_VALIDATING_DESERIALIZER,
			PropertyIdRequest::class => self::PROPERTY_ID_REQUEST_VALIDATING_DESERIALIZER,
			StatementIdRequest::class => self::STATEMENT_ID_REQUEST_VALIDATING_DESERIALIZER,
			PropertyIdFilterRequest::class => self::PROPERTY_ID_FILTER_REQUEST_VALIDATING_DESERIALIZER,
			LanguageCodeRequest::class => self::LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER,
			ItemFieldsRequest::class => self::ITEM_FIELDS_REQUEST_VALIDATING_DESERIALIZER,
			PropertyFieldsRequest::class => self::PROPERTY_FIELDS_REQUEST_VALIDATING_DESERIALIZER,
			StatementSerializationRequest::class => self::STATEMENT_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER,
			EditMetadataRequest::class => self::EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER,
			PatchRequest::class => self::PATCH_REQUEST_VALIDATING_DESERIALIZER,
			ItemLabelEditRequest::class => self::ITEM_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER,
			ItemDescriptionEditRequest::class => self::ITEM_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER,
		];
		$result = [];

		foreach ( $requestTypeToValidatorMap as $requestType => $validatorName ) {
			if ( array_key_exists( $requestType, class_implements( $request ) ) ) {
				$result[$requestType] = $this->serviceContainer->get( $validatorName )
					->validateAndDeserialize( $request );
			}
		}

		$this->validRequestResults[$requestObjectId] = new DeserializedRequestAdapter( $result );

		return $this->validRequestResults[$requestObjectId];
	}

}
