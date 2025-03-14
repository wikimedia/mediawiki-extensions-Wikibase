<?php declare( strict_types=1 );

use MediaWiki\CommentStore\CommentStore;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Reporter\MWErrorReporter;
use MediaWiki\Title\MediaWikiTitleCodec;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ItemSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertySerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ReferenceSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\FieldsFilterValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemAliasesInLanguageEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemIdRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemLabelEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemSerializationRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemStatementIdRequestValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\LanguageCodeRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\MappedRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PatchRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyAliasesInLanguageEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdFilterRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdFilterValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyLabelEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyStatementIdRequestValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SiteIdRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SitelinkEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementIdRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementSerializationRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateItem\CreateItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreateProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreatePropertyValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\GetItemDescriptionWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabelWithFallback\GetItemLabelWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescription\GetPropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\GetPropertyDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\GetPropertyDescriptionWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\GetPropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\GetPropertyLabelWithFallback;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatement\GetPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\GetSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelinks\GetSitelinks;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchedItemValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemAliases\PatchedItemAliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemAliases\PatchItemAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions\PatchedItemDescriptionsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchedItemLabelsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchedPropertyValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyAliases\PatchedPropertyAliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\PatchedPropertyDescriptionsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptions;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyLabels\PatchedPropertyLabelsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\PatchedSitelinksValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\PatchSitelinks;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchedStatementValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\RemoveItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemLabel\RemoveItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyDescription\RemovePropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyLabel\RemovePropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement\RemovePropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\RemoveSitelink;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyLabel\SetPropertyLabel;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\SetSitelink;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\SitelinksValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementIdValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemParts;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementRemover;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityRevisionLookupItemDataRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityRevisionLookupPropertyDataRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityRevisionLookupStatementRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterItemUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterStatementRemover;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterStatementUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\FallbackLookupFactoryTermsRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\PrefetchingTermLookupAliasesRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\SiteLinkPageNormalizerSitelinkTargetResolver;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\StatementSubjectRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\TermLookupEntityTermsRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\WikibaseEntityRevisionLookupItemRevisionMetadataRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\Domains\Crud\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatchValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\SiteLinkLookupSitelinkValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\SitelinksReadModelConverter;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermsEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryItemLabelValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryPropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryPropertyLabelValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\ValidatingRequestDeserializer as VRD;
use Wikibase\Repo\Domains\Crud\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\WholeEntityEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\StatementRedirectMiddlewareFactory;
use Wikibase\Repo\Domains\Crud\WbRestApi;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	VRD::ALIAS_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): LanguageCodeRequestValidatingDeserializer {
			return new LanguageCodeRequestValidatingDeserializer( WbRestApi::getAliasLanguageCodeValidator( $services ) );
		},

	VRD::DESCRIPTION_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): LanguageCodeRequestValidatingDeserializer {
			return new LanguageCodeRequestValidatingDeserializer( WbRestApi::getDescriptionLanguageCodeValidator( $services ) );
		},

	VRD::EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): EditMetadataRequestValidatingDeserializer {
			return new EditMetadataRequestValidatingDeserializer(
				new EditMetadataValidator(
					CommentStore::COMMENT_CHARACTER_LIMIT,
					$services->getChangeTagsStore()->listExplicitlyDefinedTags()
				)
			);
		},

	VRD::ITEM_ALIASES_IN_LANGUAGE_EDIT_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): ItemAliasesInLanguageEditRequestValidatingDeserializer {
			return new ItemAliasesInLanguageEditRequestValidatingDeserializer(
				new AliasesInLanguageDeserializer(),
				new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) )
			);
		},

	VRD::ITEM_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): ItemDescriptionEditRequestValidatingDeserializer {
			return new ItemDescriptionEditRequestValidatingDeserializer(
				new TermValidatorFactoryItemDescriptionValidator(
					WikibaseRepo::getTermValidatorFactory( $services ),
					WikibaseRepo::getItemTermsCollisionDetector( $services )
				),
				WbRestApi::getItemDataRetriever( $services )
			);
		},

	VRD::ITEM_FIELDS_REQUEST_VALIDATING_DESERIALIZER => function (): MappedRequestValidatingDeserializer {
		$fieldsValidator = new FieldsFilterValidatingDeserializer( ItemParts::VALID_FIELDS );
		return new MappedRequestValidatingDeserializer(
			fn( ItemFieldsRequest $r ) => $fieldsValidator->validateAndDeserialize( $r->getItemFields() )
		);
	},

	VRD::ITEM_ID_REQUEST_VALIDATING_DESERIALIZER => function(): ItemIdRequestValidatingDeserializer {
		return new ItemIdRequestValidatingDeserializer();
	},

	VRD::ITEM_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): ItemLabelEditRequestValidatingDeserializer {
			return new ItemLabelEditRequestValidatingDeserializer(
				new TermValidatorFactoryItemLabelValidator(
					WikibaseRepo::getTermValidatorFactory( $services ),
					WikibaseRepo::getItemTermsCollisionDetector( $services )
				),
				WbRestApi::getItemDataRetriever( $services )
			);
		},

	VRD::ITEM_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER =>
		function( MediaWikiServices $services ): ItemSerializationRequestValidatingDeserializer {
			return new ItemSerializationRequestValidatingDeserializer(
				new ItemValidator(
					new LabelsSyntaxValidator(
						new LabelsDeserializer(),
						WbRestApi::getLabelLanguageCodeValidator( $services )
					),
					new ItemLabelsContentsValidator(
						new TermValidatorFactoryItemLabelValidator(
							WikibaseRepo::getTermValidatorFactory( $services ),
							WikibaseRepo::getItemTermsCollisionDetector( $services )
						)
					),
					new DescriptionsSyntaxValidator(
						new DescriptionsDeserializer(),
						WbRestApi::getDescriptionLanguageCodeValidator( $services )
					),
					new ItemDescriptionsContentsValidator(
						new TermValidatorFactoryItemDescriptionValidator(
							WikibaseRepo::getTermValidatorFactory( $services ),
							WikibaseRepo::getItemTermsCollisionDetector( $services )
						)
					),
					new AliasesValidator(
						new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
						WbRestApi::getAliasLanguageCodeValidator( $services ),
						new AliasesDeserializer( new AliasesInLanguageDeserializer() )
					),
					new StatementsValidator( new StatementValidator( WbRestApi::getStatementDeserializer( $services ) ) ),
					new SitelinksValidator(
						new SiteIdValidator( WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services )->getList(
							WikibaseRepo::getSettings( $services )->getSetting( 'siteLinkGroups' )
						) ),
						new SiteLinkLookupSitelinkValidator(
							WbRestApi::getSitelinkDeserializer( $services ),
							WikibaseRepo::getStore( $services )->newSiteLinkStore()
						),
					)
				)
			);
		},

	VRD::ITEM_STATEMENT_ID_REQUEST_VALIDATOR => function (): ItemStatementIdRequestValidator {
		return new ItemStatementIdRequestValidator();
	},

	VRD::LABEL_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): LanguageCodeRequestValidatingDeserializer {
			return new LanguageCodeRequestValidatingDeserializer( WbRestApi::getLabelLanguageCodeValidator( $services ) );
		},

	VRD::PATCH_REQUEST_VALIDATING_DESERIALIZER => function (): PatchRequestValidatingDeserializer {
		return new PatchRequestValidatingDeserializer( new JsonDiffJsonPatchValidator() );
	},

	VRD::PROPERTY_ALIASES_IN_LANGUAGE_EDIT_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): PropertyAliasesInLanguageEditRequestValidatingDeserializer {
			return new PropertyAliasesInLanguageEditRequestValidatingDeserializer(
				new AliasesInLanguageDeserializer(),
				new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) )
			);
		},

	VRD::PROPERTY_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): PropertyDescriptionEditRequestValidatingDeserializer {
			return new PropertyDescriptionEditRequestValidatingDeserializer(
				new TermValidatorFactoryPropertyDescriptionValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
				WbRestApi::getPropertyDataRetriever( $services )
			);
		},

	VRD::PROPERTY_FIELDS_REQUEST_VALIDATING_DESERIALIZER => function (): MappedRequestValidatingDeserializer {
		$fieldsValidator = new FieldsFilterValidatingDeserializer( PropertyParts::VALID_FIELDS );
		return new MappedRequestValidatingDeserializer(
			fn( PropertyFieldsRequest $r ) => $fieldsValidator->validateAndDeserialize( $r->getPropertyFields() )
		);
	},

	VRD::PROPERTY_ID_FILTER_REQUEST_VALIDATING_DESERIALIZER => function(): MappedRequestValidatingDeserializer {
		$propertyIdFilterValidatingDeserializer = new PropertyIdFilterValidatingDeserializer( new PropertyIdValidator() );
		return new MappedRequestValidatingDeserializer(
			fn( PropertyIdFilterRequest $r ) => $r->getPropertyIdFilter() === null
				? null
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
				: $propertyIdFilterValidatingDeserializer->validateAndDeserialize( $r->getPropertyIdFilter() )
		);
	},

	VRD::PROPERTY_ID_REQUEST_VALIDATING_DESERIALIZER => function(): MappedRequestValidatingDeserializer {
		$propertyIdValidatingDeserializer = new PropertyIdValidatingDeserializer( new PropertyIdValidator() );
		return new MappedRequestValidatingDeserializer(
			fn( PropertyIdRequest $r ) => $propertyIdValidatingDeserializer->validateAndDeserialize( $r->getPropertyId() )
		);
	},

	VRD::PROPERTY_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): PropertyLabelEditRequestValidatingDeserializer {
			return new PropertyLabelEditRequestValidatingDeserializer(
				new TermValidatorFactoryPropertyLabelValidator(
					WikibaseRepo::getTermValidatorFactory( $services ),
					WikibaseRepo::getPropertyTermsCollisionDetector( $services )
				),
				WbRestApi::getPropertyDataRetriever( $services )
			);
		},

	VRD::PROPERTY_STATEMENT_ID_REQUEST_VALIDATOR => function (): PropertyStatementIdRequestValidator {
		return new PropertyStatementIdRequestValidator();
	},

	VRD::SITELINK_EDIT_REQUEST_VALIDATING_DESERIALIZER =>
		function( MediaWikiServices $services ): SitelinkEditRequestValidatingDeserializer {
			return new SitelinkEditRequestValidatingDeserializer(
				new SiteLinkLookupSitelinkValidator(
					WbRestApi::getSitelinkDeserializer( $services ),
					WikibaseRepo::getStore( $services )->newSiteLinkStore()
				)
			);
		},

	VRD::SITE_ID_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): SiteIdRequestValidatingDeserializer {
			return new SiteIdRequestValidatingDeserializer(
				new SiteIdValidator( WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services )->getList(
					WikibaseRepo::getSettings( $services )->getSetting( 'siteLinkGroups' )
				) )
			);
		},

	VRD::STATEMENT_ID_REQUEST_VALIDATING_DESERIALIZER => function(): StatementIdRequestValidatingDeserializer {
		$entityIdParser = new BasicEntityIdParser();

		return new StatementIdRequestValidatingDeserializer(
			new StatementIdValidator( $entityIdParser ),
			new StatementGuidParser( $entityIdParser )
		);
	},

	VRD::STATEMENT_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): StatementSerializationRequestValidatingDeserializer {
			return new StatementSerializationRequestValidatingDeserializer(
				new StatementValidator( WbRestApi::getStatementDeserializer( $services ) )
			);
		},

	'WbRestApi.AddItemAliasesInLanguage' => function( MediaWikiServices $services ): AddItemAliasesInLanguage {
		return new AddItemAliasesInLanguage(
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getItemUpdater( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.AddItemStatement' => function( MediaWikiServices $services ): AddItemStatement {
		return new AddItemStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			new GuidGenerator(),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.AddPropertyAliasesInLanguage' => function( MediaWikiServices $services ): AddPropertyAliasesInLanguage {
		return new AddPropertyAliasesInLanguage(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getPropertyUpdater( $services ),
		);
	},

	'WbRestApi.AddPropertyStatement' => function( MediaWikiServices $services ): AddPropertyStatement {
		$statementReadModelConverter = new StatementReadModelConverter(
			WikibaseRepo::getStatementGuidParser( $services ),
			WikibaseRepo::getPropertyDataTypeLookup( $services )
		);
		return new AddPropertyStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			new EntityRevisionLookupPropertyDataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services ),
				$statementReadModelConverter
			),
			new GuidGenerator(),
			WbRestApi::getPropertyUpdater( $services ),
			new AssertUserIsAuthorized(
				new WikibaseEntityPermissionChecker(
					WikibaseRepo::getEntityPermissionChecker( $services ),
					$services->getUserFactory()
				)
			)
		);
	},

	'WbRestApi.AliasLanguageCodeValidator' => function( MediaWikiServices $services ): AliasLanguageCodeValidator {
		return new ValueValidatorLanguageCodeValidator( WikibaseRepo::getTermValidatorFactory( $services )->getAliasLanguageValidator() );
	},

	'WbRestApi.AssertItemExists' => function( MediaWikiServices $services ): AssertItemExists {
		return new AssertItemExists( WbRestApi::getGetLatestItemRevisionMetadata( $services ) );
	},

	'WbRestApi.AssertPropertyExists' => function( MediaWikiServices $services ): AssertPropertyExists {
		return new AssertPropertyExists( WbRestApi::getGetLatestPropertyRevisionMetadata( $services ) );
	},

	'WbRestApi.AssertStatementSubjectExists' => function( MediaWikiServices $services ): AssertStatementSubjectExists {
		return new AssertStatementSubjectExists( WbRestApi::getGetLatestStatementSubjectRevisionMetadata( $services ) );
	},

	'WbRestApi.AssertUserIsAuthorized' => function( MediaWikiServices $services ): AssertUserIsAuthorized {
		return new AssertUserIsAuthorized(
			new WikibaseEntityPermissionChecker(
				WikibaseRepo::getEntityPermissionChecker( $services ),
				$services->getUserFactory()
			)
		);
	},

	'WbRestApi.CreateItem' => function( MediaWikiServices $services ): CreateItem {
		return new CreateItem(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getItemUpdater( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.CreateProperty' => function( MediaWikiServices $services ): CreateProperty {
		return new CreateProperty(
			new CreatePropertyValidator(
				WbRestApi::getEditMetadataRequestValidatingDeserializer( $services ),
				WikibaseRepo::getDataTypeDefinitions()->getTypeIds(),
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbRestApi::getLabelLanguageCodeValidator( $services )
				),
				new PropertyLabelsContentsValidator(
					new TermValidatorFactoryPropertyLabelValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getPropertyTermsCollisionDetector( $services )
					)
				),
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbRestApi::getDescriptionLanguageCodeValidator( $services )
				),
				new PropertyDescriptionsContentsValidator(
					new TermValidatorFactoryPropertyDescriptionValidator( WikibaseRepo::getTermValidatorFactory( $services ) )
				),
				new AliasesValidator(
					new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
					WbRestApi::getAliasLanguageCodeValidator( $services ),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() )
				),
				new StatementsValidator( new StatementValidator( WbRestApi::getStatementDeserializer() ) )
			),
			WbRestApi::getPropertyUpdater( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.DescriptionLanguageCodeValidator' => function( MediaWikiServices $services ): DescriptionLanguageCodeValidator {
		return new ValueValidatorLanguageCodeValidator(
			WikibaseRepo::getTermValidatorFactory( $services )->getDescriptionLanguageValidator()
		);
	},

	'WbRestApi.EntityUpdater' => function( MediaWikiServices $services ): EntityUpdater {
		return new EntityUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory( $services ),
			WikibaseRepo::getLogger( $services ),
			new EditSummaryFormatter(
				WikibaseRepo::getSummaryFormatter( $services ),
				new TermsEditSummaryToFormattableSummaryConverter(),
				new WholeEntityEditSummaryToFormattableSummaryConverter()
			),
			$services->getPermissionManager(),
			WikibaseRepo::getEntityStore( $services ),
			new GuidGenerator(),
			WikibaseRepo::getSettings( $services )
		);
	},

	'WbRestApi.ErrorReporter' => function( MediaWikiServices $services ): ErrorReporter {
		return new MWErrorReporter();
	},

	'WbRestApi.GetItem' => function( MediaWikiServices $services ): GetItem {
		return new GetItem(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetItemAliases' => function( MediaWikiServices $services ): GetItemAliases {
		return new GetItemAliases(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetItemAliasesInLanguage' => function( MediaWikiServices $services ): GetItemAliasesInLanguage {
		return new GetItemAliasesInLanguage(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetItemDescription' => function( MediaWikiServices $services ): GetItemDescription {
		return new GetItemDescription(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetItemDescriptions' => function( MediaWikiServices $services ): GetItemDescriptions {
		return new GetItemDescriptions(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetItemDescriptionWithFallback' => function( MediaWikiServices $services ): GetItemDescriptionWithFallback {
		return new GetItemDescriptionWithFallback(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new FallbackLookupFactoryTermsRetriever(
				$services->getLanguageFactory(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
			)
		);
	},

	'WbRestApi.GetItemLabel' => function( MediaWikiServices $services ): GetItemLabel {
		return new GetItemLabel(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetItemLabels' => function( MediaWikiServices $services ): GetItemLabels {
		return new GetItemLabels(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetItemLabelWithFallback' => function( MediaWikiServices $services ): GetItemLabelWithFallback {
		return new GetItemLabelWithFallback(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new FallbackLookupFactoryTermsRetriever(
				$services->getLanguageFactory(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
			),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetItemStatement' => function( MediaWikiServices $services ): GetItemStatement {
		return new GetItemStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getGetStatement( $services )
		);
	},

	'WbRestApi.GetItemStatements' => function( MediaWikiServices $services ): GetItemStatements {
		return new GetItemStatements(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getGetLatestItemRevisionMetadata( $services )
		);
	},

	'WbRestApi.GetLatestItemRevisionMetadata' => function( MediaWikiServices $services ): GetLatestItemRevisionMetadata {
		return new GetLatestItemRevisionMetadata( new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services )
		) );
	},

	'WbRestApi.GetLatestPropertyRevisionMetadata' => function( MediaWikiServices $services ): GetLatestPropertyRevisionMetadata {
		return new GetLatestPropertyRevisionMetadata( new WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services )
		) );
	},

	'WbRestApi.GetLatestStatementSubjectRevisionMetadata' => function(
		MediaWikiServices $services
	): GetLatestStatementSubjectRevisionMetadata {
		return new GetLatestStatementSubjectRevisionMetadata( new WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services )
		) );
	},

	'WbRestApi.GetProperty' => function( MediaWikiServices $services ): GetProperty {
		return new GetProperty(
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetPropertyAliases' => function( MediaWikiServices $services ): GetPropertyAliases {
		return new GetPropertyAliases(
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetPropertyAliasesInLanguage' => function( MediaWikiServices $services ): GetPropertyAliasesInLanguage {
		return new GetPropertyAliasesInLanguage(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			)
		);
	},

	'WbRestApi.GetPropertyDescription' => function( MediaWikiServices $services ): GetPropertyDescription {
		return new GetPropertyDescription(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services )
		);
	},

	'WbRestApi.GetPropertyDescriptions' => function( MediaWikiServices $services ): GetPropertyDescriptions {
		return new GetPropertyDescriptions(
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetPropertyDescriptionWithFallback' => function( MediaWikiServices $services ): GetPropertyDescriptionWithFallback {
		return new GetPropertyDescriptionWithFallback(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			new FallbackLookupFactoryTermsRetriever(
				$services->getLanguageFactory(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
			)
		);
	},

	'WbRestApi.GetPropertyLabel' => function( MediaWikiServices $services ): GetPropertyLabel {
		return new GetPropertyLabel(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services )
		);
	},

	'WbRestApi.GetPropertyLabels' => function( MediaWikiServices $services ): GetPropertyLabels {
		return new GetPropertyLabels(
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.GetPropertyLabelWithFallback' => function( MediaWikiServices $services ): GetPropertyLabelWithFallback {
		return new GetPropertyLabelWithFallback(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			new FallbackLookupFactoryTermsRetriever(
				$services->getLanguageFactory(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
			)
		);
	},

	'WbRestApi.GetPropertyStatement' => function( MediaWikiServices $services ): GetPropertyStatement {
		return new GetPropertyStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getGetStatement( $services )
		);
	},

	'WbRestApi.GetPropertyStatements' => function( MediaWikiServices $services ): GetPropertyStatements {
		return new GetPropertyStatements(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services )
		);
	},

	'WbRestApi.GetSitelink' => function( MediaWikiServices $services ): GetSitelink {
		return new GetSitelink(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			WbRestApi::getItemDataRetriever( $services ),
		);
	},

	'WbRestApi.GetSitelinks' => function( MediaWikiServices $services ): GetSitelinks {
		return new GetSitelinks(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			WbRestApi::getItemDataRetriever( $services ),
		);
	},

	'WbRestApi.GetStatement' => function( MediaWikiServices $services ): GetStatement {
		return new GetStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getStatementRetriever( $services ),
			WbRestApi::getGetLatestStatementSubjectRevisionMetadata( $services )
		);
	},

	'WbRestApi.ItemDataRetriever' => function( MediaWikiServices $services ): EntityRevisionLookupItemDataRetriever {
		return new EntityRevisionLookupItemDataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			),
			new SitelinksReadModelConverter( $services->getSiteLookup() )
		);
	},

	'WbRestApi.ItemUpdater' => function( MediaWikiServices $services ): EntityUpdaterItemUpdater {
		return new EntityUpdaterItemUpdater(
			WbRestApi::getEntityUpdater( $services ),
			new SitelinksReadModelConverter( $services->getSiteLookup() ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbRestApi.LabelLanguageCodeValidator' => function( MediaWikiServices $services ): LabelLanguageCodeValidator {
		return new ValueValidatorLanguageCodeValidator( WikibaseRepo::getTermValidatorFactory( $services )->getLabelLanguageValidator() );
	},

	'WbRestApi.PatchItem' => function( MediaWikiServices $services ): PatchItem {
		return new PatchItem(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists(),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getItemDataRetriever(),
			new ItemSerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				new StatementListSerializer( WbRestApi::getStatementSerializer( $services ) ),
				new SitelinksSerializer( new SitelinkSerializer() )
			),
			new PatchJson( new JsonDiffJsonPatcher() ),
			new PatchedItemValidator(
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbRestApi::getLabelLanguageCodeValidator( $services )
				),
				new ItemLabelsContentsValidator(
					new TermValidatorFactoryItemLabelValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getItemTermsCollisionDetector()
					)
				),
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbRestApi::getDescriptionLanguageCodeValidator( $services )
				),
				new ItemDescriptionsContentsValidator(
					new TermValidatorFactoryItemDescriptionValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getItemTermsCollisionDetector()
					)
				),
				new AliasesValidator(
					new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
					WbRestApi::getAliasLanguageCodeValidator( $services ),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() )
				),
				new SitelinksValidator(
					new SiteIdValidator( WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services )->getList(
						WikibaseRepo::getSettings( $services )->getSetting( 'siteLinkGroups' )
					) ),
					new SiteLinkLookupSitelinkValidator(
						WbRestApi::getSitelinkDeserializer( $services ),
						WikibaseRepo::getStore( $services )->newSiteLinkStore()
					),
				),
				new StatementsValidator( new StatementValidator( WbRestApi::getStatementDeserializer() ) )
			),
			WbRestApi::getItemDataRetriever(),
			WbRestApi::getItemUpdater()
		);
	},

	'WbRestApi.PatchItemAliases' => function( MediaWikiServices $services ): PatchItemAliases {
		return new PatchItemAliases(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new AliasesSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			new PatchedItemAliasesValidator(
				new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
				new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
				WbRestApi::getAliasLanguageCodeValidator( $services )
			),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services )
		);
	},

	'WbRestApi.PatchItemDescriptions' => function( MediaWikiServices $services ): PatchItemDescriptions {
		return new PatchItemDescriptions(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services ),
			new DescriptionsSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbRestApi::getItemDataRetriever( $services ),
			new PatchedItemDescriptionsValidator(
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbRestApi::getDescriptionLanguageCodeValidator( $services )
				),
				new ItemDescriptionsContentsValidator( new TermValidatorFactoryItemDescriptionValidator(
					WikibaseRepo::getTermValidatorFactory( $services ),
					WikibaseRepo::getItemTermsCollisionDetector( $services )
				) )
			),
			WbRestApi::getItemUpdater( $services )
		);
	},

	'WbRestApi.PatchItemLabels' => function( MediaWikiServices $services ): PatchItemLabels {
		return new PatchItemLabels(
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getTermLookupEntityTermsRetriever( $services ),
			new LabelsSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			new PatchedItemLabelsValidator(
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbRestApi::getLabelLanguageCodeValidator( $services )
				),
				new ItemLabelsContentsValidator(
					new TermValidatorFactoryItemLabelValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getItemTermsCollisionDetector( $services )
					)
				)
			),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.PatchItemStatement' => function( MediaWikiServices $services ): PatchItemStatement {
		return new PatchItemStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getPatchStatement( $services )
		);
	},

	'WbRestApi.PatchProperty' => function( MediaWikiServices $services ): PatchProperty {
		return new PatchProperty(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			new PropertySerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				new StatementListSerializer( WbRestApi::getStatementSerializer( $services ) )
			),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbRestApi::getPropertyUpdater( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			new PatchedPropertyValidator(
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbRestApi::getLabelLanguageCodeValidator( $services )
				),
				new PropertyLabelsContentsValidator(
					new TermValidatorFactoryPropertyLabelValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getPropertyTermsCollisionDetector( $services )
					)
				),
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbRestApi::getDescriptionLanguageCodeValidator( $services )
				),
				new PropertyDescriptionsContentsValidator(
					new TermValidatorFactoryPropertyDescriptionValidator( WikibaseRepo::getTermValidatorFactory( $services ) )
				),
				new AliasesValidator(
					new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
					WbRestApi::getAliasLanguageCodeValidator( $services ),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() )
				),
				new StatementsValidator( new StatementValidator( WbRestApi::getStatementDeserializer( $services ) ) )
			)
		);
	},

	'WbRestApi.PatchPropertyAliases' => function( MediaWikiServices $services ): PatchPropertyAliases {
		$termLanguages = WikibaseRepo::getTermsLanguages( $services );

		return new PatchPropertyAliases(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				$termLanguages
			),
			new AliasesSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			new PatchedPropertyAliasesValidator(
				new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
				new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
				WbRestApi::getAliasLanguageCodeValidator( $services )
			),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getPropertyUpdater( $services )
		);
	},

	'WbRestApi.PatchPropertyDescriptions' => function( MediaWikiServices $services ): PatchPropertyDescriptions {
		return new PatchPropertyDescriptions(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new DescriptionsSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbRestApi::getPropertyDataRetriever( $services ),
			new PatchedPropertyDescriptionsValidator(
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbRestApi::getDescriptionLanguageCodeValidator( $services )
				),
				new PropertyDescriptionsContentsValidator(
					new TermValidatorFactoryPropertyDescriptionValidator( WikibaseRepo::getTermValidatorFactory( $services ) )
				)
			),
			WbRestApi::getPropertyUpdater( $services )
		);
	},

	'WbRestApi.PatchPropertyLabels' => function( MediaWikiServices $services ): PatchPropertyLabels {
		return new PatchPropertyLabels(
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new LabelsSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getPropertyUpdater( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services ),
			new PatchedPropertyLabelsValidator(
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbRestApi::getLabelLanguageCodeValidator( $services )
				),
				new PropertyLabelsContentsValidator( new TermValidatorFactoryPropertyLabelValidator(
					WikibaseRepo::getTermValidatorFactory( $services ),
					WikibaseRepo::getPropertyTermsCollisionDetector( $services )
				) )
			),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.PatchPropertyStatement' => function( MediaWikiServices $services ): PatchPropertyStatement {
		return new PatchPropertyStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getPatchStatement( $services )
		);
	},

	'WbRestApi.PatchSitelinks' => function( MediaWikiServices $services ): PatchSitelinks {
		return new PatchSitelinks(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			new SitelinksSerializer( new SitelinkSerializer() ),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbRestApi::getItemDataRetriever( $services ),
			new PatchedSitelinksValidator( new SitelinksValidator(
				new SiteIdValidator( WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services )->getList(
					WikibaseRepo::getSettings( $services )->getSetting( 'siteLinkGroups' )
				) ),
				new SiteLinkLookupSitelinkValidator(
					WbRestApi::getSitelinkDeserializer( $services ),
					WikibaseRepo::getStore( $services )->newSiteLinkStore()
				),
			) ),
			WbRestApi::getItemUpdater( $services )
		);
	},

	'WbRestApi.PatchStatement' => function( MediaWikiServices $services ): PatchStatement {
		return new PatchStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			new PatchedStatementValidator( new StatementValidator( WbRestApi::getStatementDeserializer( $services ) ) ),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbRestApi::getStatementSerializer( $services ),
			WbRestApi::getAssertStatementSubjectExists( $services ),
			WbRestApi::getStatementRetriever( $services ),
			WbRestApi::getStatementUpdater( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.PreconditionMiddlewareFactory' => function( MediaWikiServices $services ): PreconditionMiddlewareFactory {
		return new PreconditionMiddlewareFactory(
			WikibaseRepo::getEntityRevisionLookup( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			new ConditionalHeaderUtil()
		);
	},

	'WbRestApi.PropertyDataRetriever' => function( MediaWikiServices $services ): EntityRevisionLookupPropertyDataRetriever {
		return new EntityRevisionLookupPropertyDataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			),
		);
	},

	'WbRestApi.PropertyUpdater' => function( MediaWikiServices $services ): EntityUpdaterPropertyUpdater {
		return new EntityUpdaterPropertyUpdater(
			WbRestApi::getEntityUpdater( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbRestApi.RemoveItemDescription' => function( MediaWikiServices $services ): RemoveItemDescription {
		return new RemoveItemDescription(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services )
		);
	},

	'WbRestApi.RemoveItemLabel' => function( MediaWikiServices $services ): RemoveItemLabel {
		return new RemoveItemLabel(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services )
		);
	},

	'WbRestApi.RemoveItemStatement' => function( MediaWikiServices $services ): RemoveItemStatement {
		return new RemoveItemStatement(
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getRemoveStatement( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.RemovePropertyDescription' => function( MediaWikiServices $services ): RemovePropertyDescription {
		return new RemovePropertyDescription(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getPropertyUpdater( $services )
		);
	},

	'WbRestApi.RemovePropertyLabel' => function( MediaWikiServices $services ): RemovePropertyLabel {
		return new RemovePropertyLabel(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getPropertyUpdater( $services )
		);
	},

	'WbRestApi.RemovePropertyStatement' => function( MediaWikiServices $services ): RemovePropertyStatement {
		return new RemovePropertyStatement(
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getRemoveStatement( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services )
		);
	},

	'WbRestApi.RemoveSitelink' => function( MediaWikiServices $services ): RemoveSitelink {
		return new RemoveSitelink(
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.RemoveStatement' => function( MediaWikiServices $services ): RemoveStatement {
		return new RemoveStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getAssertStatementSubjectExists( $services ),
			WbRestApi::getStatementRetriever( $services ),
			WbRestApi::getStatementRemover( $services )
		);
	},

	'WbRestApi.ReplaceItemStatement' => function( MediaWikiServices $services ): ReplaceItemStatement {
		return new ReplaceItemStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getReplaceStatement( $services )
		);
	},

	'WbRestApi.ReplacePropertyStatement' => function( MediaWikiServices $services ): ReplacePropertyStatement {
		return new ReplacePropertyStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getReplaceStatement( $services )
		);
	},

	'WbRestApi.ReplaceStatement' => function( MediaWikiServices $services ): ReplaceStatement {
		return new ReplaceStatement(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertStatementSubjectExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getStatementUpdater( $services )
		);
	},

	'WbRestApi.SetItemDescription' => function( MediaWikiServices $services ): SetItemDescription {
		return new SetItemDescription(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.SetItemLabel' => function( MediaWikiServices $services ): SetItemLabel {
		return new SetItemLabel(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.SetPropertyDescription' => function( MediaWikiServices $services ): SetPropertyDescription {
		return new SetPropertyDescription(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getPropertyUpdater( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.SetPropertyLabel' => function( MediaWikiServices $services ): SetPropertyLabel {
		return new SetPropertyLabel(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getPropertyUpdater( $services ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.SetSitelink' => function( MediaWikiServices $services ): SetSitelink {
		return new SetSitelink(
			WbRestApi::getValidatingRequestDeserializer( $services ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services )
		);
	},

	'WbRestApi.SitelinkDeserializer' => function( MediaWikiServices $services ): SitelinkDeserializer {
		return new SitelinkDeserializer(
			MediaWikiTitleCodec::getTitleInvalidRegex(),
			array_keys( WikibaseRepo::getSettings( $services )->getSetting( 'badgeItems' ) ),
			new SiteLinkPageNormalizerSitelinkTargetResolver(
				$services->getSiteLookup(),
				WikibaseRepo::getSiteLinkPageNormalizer( $services )
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) )
		);
	},

	'WbRestApi.StatementDeserializer' => function( MediaWikiServices $services ): StatementDeserializer {
		$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			new DataValuesValueDeserializer(
				new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory( $services ) ),
				WikibaseRepo::getSnakValueDeserializer( $services ),
				WikibaseRepo::getDataTypeValidatorFactory( $services )
			)
		);
		return new StatementDeserializer(
			$propertyValuePairDeserializer,
			new ReferenceDeserializer( $propertyValuePairDeserializer )
		);
	},

	'WbRestApi.StatementRedirectMiddlewareFactory' => function( MediaWikiServices $services ): StatementRedirectMiddlewareFactory {
		return new StatementRedirectMiddlewareFactory(
			WikibaseRepo::getEntityIdParser( $services ),
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) )
		);
	},

	'WbRestApi.StatementRemover' => function( MediaWikiServices $services ): StatementRemover {
		return new EntityUpdaterStatementRemover(
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			WbRestApi::getEntityUpdater( $services ),
		);
	},

	'WbRestApi.StatementRetriever' => function( MediaWikiServices $services ): EntityRevisionLookupStatementRetriever {
		return new EntityRevisionLookupStatementRetriever(
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbRestApi.StatementSerializer' => function( MediaWikiServices $services ): StatementSerializer {
		$propertyValuePairSerializer = new PropertyValuePairSerializer();
		$referenceSerializer = new ReferenceSerializer( $propertyValuePairSerializer );
		return new StatementSerializer( $propertyValuePairSerializer, $referenceSerializer );
	},

	'WbRestApi.StatementUpdater' => function( MediaWikiServices $services ): StatementUpdater {
		return new EntityUpdaterStatementUpdater(
			WikibaseRepo::getStatementGuidParser( $services ),
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			WbRestApi::getEntityUpdater( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbRestApi.TermLookupEntityTermsRetriever' => function( MediaWikiServices $services ): TermLookupEntityTermsRetriever {
		return new TermLookupEntityTermsRetriever(
			WikibaseRepo::getTermLookup( $services ),
			WikibaseRepo::getTermsLanguages( $services )
		);
	},

	'WbRestApi.UnexpectedErrorHandlerMiddleware' => function( MediaWikiServices $services ): UnexpectedErrorHandlerMiddleware {
		return new UnexpectedErrorHandlerMiddleware(
			$services->get( 'WbRestApi.ErrorReporter' )
		);
	},

	'WbRestApi.ValidatingRequestDeserializer' => function( MediaWikiServices $services ): VRD {
		return new VRD( $services );
	},

];
