<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use Psr\Container\ContainerInterface;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\AliasLanguageCodeRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DescriptionLanguageCodeRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemAliasesInLanguageEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemDescriptionEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemLabelEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemSerializationRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemStatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\LabelLanguageCodeRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PatchRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyAliasesInLanguageEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyDescriptionEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdFilterRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyLabelEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyStatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SiteIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SitelinkEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementSerializationRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\UseCaseRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateItem\CreateItemValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliases\GetItemAliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescription\GetItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptions\GetItemDescriptionsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallbackValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabel\GetItemLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallbackValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetProperty\GetPropertyValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\GetPropertyAliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescription\GetPropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptionsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallbackValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\GetPropertyLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\GetPropertyLabelsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallbackValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatements\GetPropertyStatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\GetSitelinkValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelinks\GetSitelinksValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItemValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemAliases\PatchItemAliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabelsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchPropertyValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyAliases\PatchPropertyAliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptionsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyLabels\PatchPropertyLabelsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\PatchSitelinksValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\RemoveItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemLabel\RemoveItemLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyDescription\RemovePropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyLabel\RemovePropertyLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement\RemovePropertyStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\RemoveSitelinkValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\ReplaceStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemDescription\SetItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\SetItemLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyLabel\SetPropertyLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelinkValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ValidatingRequestDeserializer	implements
	AddItemStatementValidator,
	AddPropertyStatementValidator,
	GetItemValidator,
	GetSitelinksValidator,
	GetSitelinkValidator,
	GetItemLabelsValidator,
	GetItemLabelValidator,
	GetItemLabelWithFallbackValidator,
	GetItemDescriptionsValidator,
	GetItemDescriptionValidator,
	GetItemDescriptionWithFallbackValidator,
	GetItemAliasesValidator,
	GetItemAliasesInLanguageValidator,
	GetItemStatementValidator,
	GetItemStatementsValidator,
	GetPropertyValidator,
	GetPropertyLabelsValidator,
	GetPropertyDescriptionsValidator,
	GetPropertyDescriptionWithFallbackValidator,
	GetPropertyAliasesValidator,
	GetPropertyAliasesInLanguageValidator,
	GetPropertyStatementValidator,
	GetPropertyStatementsValidator,
	GetStatementValidator,
	PatchItemValidator,
	PatchItemLabelsValidator,
	PatchItemDescriptionsValidator,
	PatchItemAliasesValidator,
	PatchItemStatementValidator,
	PatchPropertyValidator,
	PatchPropertyStatementValidator,
	PatchStatementValidator,
	RemoveItemLabelValidator,
	RemoveItemDescriptionValidator,
	RemoveItemStatementValidator,
	RemovePropertyLabelValidator,
	RemovePropertyDescriptionValidator,
	RemovePropertyStatementValidator,
	RemoveStatementValidator,
	ReplaceItemStatementValidator,
	ReplacePropertyStatementValidator,
	ReplaceStatementValidator,
	SetItemLabelValidator,
	SetItemDescriptionValidator,
	GetPropertyLabelValidator,
	GetPropertyDescriptionValidator,
	GetPropertyLabelWithFallbackValidator,
	SetPropertyDescriptionValidator,
	PatchPropertyLabelsValidator,
	PatchPropertyDescriptionsValidator,
	PatchPropertyAliasesValidator,
	SetPropertyLabelValidator,
	AddItemAliasesInLanguageValidator,
	AddPropertyAliasesInLanguageValidator,
	RemoveSitelinkValidator,
	SetSitelinkValidator,
	PatchSitelinksValidator,
	CreateItemValidator
{
	private const PREFIX = 'WbCrud.RequestValidation.';
	public const ITEM_ID_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemIdRequestValidatingDeserializer';
	public const PROPERTY_ID_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PropertyIdRequestValidatingDeserializer';
	public const STATEMENT_ID_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'StatementIdRequestValidatingDeserializer';
	public const PROPERTY_ID_FILTER_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PropertyIdFilterRequestValidatingDeserializer';
	public const LABEL_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'LabelLanguageCodeRequestValidatingDeserializer';
	public const DESCRIPTION_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER =
		self::PREFIX . 'DescriptionLanguageCodeRequestValidatingDeserializer';
	public const ALIAS_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'AliasLanguageCodeRequestValidatingDeserializer';
	public const SITE_ID_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'SiteIdRequestValidatingDeserializer';
	public const ITEM_FIELDS_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemFieldsRequestValidatingDeserializer';
	public const PROPERTY_FIELDS_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PropertyFieldsRequestValidatingDeserializer';
	public const STATEMENT_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER =
		self::PREFIX . 'StatementSerializationRequestValidatingDeserializer';
	public const EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'EditMetadataRequestValidatingDeserializer';
	public const PATCH_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PatchRequestValidatingDeserializer';
	public const ITEM_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemLabelEditRequestValidatingDeserializer';
	public const ITEM_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemDescriptionEditRequestValidatingDeserializer';
	public const ITEM_ALIASES_IN_LANGUAGE_EDIT_REQUEST_VALIDATING_DESERIALIZER =
		self::PREFIX . 'ItemAliasesEditRequestValidatingDeserializer';

	public const PROPERTY_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER =
		self::PREFIX . 'PropertyDescriptionEditRequestValidatingDeserializer';
	public const PROPERTY_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'PropertyLabelEditRequestValidatingDeserializer';
	public const PROPERTY_ALIASES_IN_LANGUAGE_EDIT_REQUEST_VALIDATING_DESERIALIZER =
		self::PREFIX . 'PropertyAliasesInLanguageEditRequestValidatingDeserializer';
	public const SITELINK_EDIT_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'SitelinkEditRequestValidatingDeserializer';
	public const ITEM_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER = self::PREFIX . 'ItemSerializationRequestValidatingDeserializer';
	public const ITEM_STATEMENT_ID_REQUEST_VALIDATOR = self::PREFIX . 'ItemStatementIdRequestValidator';
	public const PROPERTY_STATEMENT_ID_REQUEST_VALIDATOR = self::PREFIX . 'PropertyStatementIdRequestValidator';

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
			SiteIdRequest::class => self::SITE_ID_REQUEST_VALIDATING_DESERIALIZER,
			StatementIdRequest::class => self::STATEMENT_ID_REQUEST_VALIDATING_DESERIALIZER,
			PropertyIdFilterRequest::class => self::PROPERTY_ID_FILTER_REQUEST_VALIDATING_DESERIALIZER,
			LabelLanguageCodeRequest::class => self::LABEL_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER,
			DescriptionLanguageCodeRequest::class => self::DESCRIPTION_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER,
			AliasLanguageCodeRequest::class => self::ALIAS_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER,
			ItemFieldsRequest::class => self::ITEM_FIELDS_REQUEST_VALIDATING_DESERIALIZER,
			PropertyFieldsRequest::class => self::PROPERTY_FIELDS_REQUEST_VALIDATING_DESERIALIZER,
			StatementSerializationRequest::class => self::STATEMENT_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER,
			EditMetadataRequest::class => self::EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER,
			PatchRequest::class => self::PATCH_REQUEST_VALIDATING_DESERIALIZER,
			ItemLabelEditRequest::class => self::ITEM_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER,
			ItemDescriptionEditRequest::class => self::ITEM_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER,
			ItemAliasesInLanguageEditRequest::class => self::ITEM_ALIASES_IN_LANGUAGE_EDIT_REQUEST_VALIDATING_DESERIALIZER,
			PropertyLabelEditRequest::class => self::PROPERTY_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER,
			PropertyDescriptionEditRequest::class => self::PROPERTY_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER,
			PropertyAliasesInLanguageEditRequest::class => self::PROPERTY_ALIASES_IN_LANGUAGE_EDIT_REQUEST_VALIDATING_DESERIALIZER,
			SitelinkEditRequest::class => self::SITELINK_EDIT_REQUEST_VALIDATING_DESERIALIZER,
			ItemSerializationRequest::class => self::ITEM_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER,
			ItemStatementIdRequest::class => self::ITEM_STATEMENT_ID_REQUEST_VALIDATOR,
			PropertyStatementIdRequest::class => self::PROPERTY_STATEMENT_ID_REQUEST_VALIDATOR,
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
