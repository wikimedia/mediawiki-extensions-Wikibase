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
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	VRD::ALIAS_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): LanguageCodeRequestValidatingDeserializer {
			return new LanguageCodeRequestValidatingDeserializer( WbCrud::getAliasLanguageCodeValidator( $services ) );
		},

	VRD::DESCRIPTION_LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER =>
		function ( MediaWikiServices $services ): LanguageCodeRequestValidatingDeserializer {
			return new LanguageCodeRequestValidatingDeserializer( WbCrud::getDescriptionLanguageCodeValidator( $services ) );
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
				WbCrud::getItemDataRetriever( $services )
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
				WbCrud::getItemDataRetriever( $services )
			);
		},

	VRD::ITEM_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER =>
		function( MediaWikiServices $services ): ItemSerializationRequestValidatingDeserializer {
			return new ItemSerializationRequestValidatingDeserializer(
				new ItemValidator(
					new LabelsSyntaxValidator(
						new LabelsDeserializer(),
						WbCrud::getLabelLanguageCodeValidator( $services )
					),
					new ItemLabelsContentsValidator(
						new TermValidatorFactoryItemLabelValidator(
							WikibaseRepo::getTermValidatorFactory( $services ),
							WikibaseRepo::getItemTermsCollisionDetector( $services )
						)
					),
					new DescriptionsSyntaxValidator(
						new DescriptionsDeserializer(),
						WbCrud::getDescriptionLanguageCodeValidator( $services )
					),
					new ItemDescriptionsContentsValidator(
						new TermValidatorFactoryItemDescriptionValidator(
							WikibaseRepo::getTermValidatorFactory( $services ),
							WikibaseRepo::getItemTermsCollisionDetector( $services )
						)
					),
					new AliasesValidator(
						new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
						WbCrud::getAliasLanguageCodeValidator( $services ),
						new AliasesDeserializer( new AliasesInLanguageDeserializer() )
					),
					new StatementsValidator( new StatementValidator( WbCrud::getStatementDeserializer( $services ) ) ),
					new SitelinksValidator(
						new SiteIdValidator( WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services )->getList(
							WikibaseRepo::getSettings( $services )->getSetting( 'siteLinkGroups' )
						) ),
						new SiteLinkLookupSitelinkValidator(
							WbCrud::getSitelinkDeserializer( $services ),
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
			return new LanguageCodeRequestValidatingDeserializer( WbCrud::getLabelLanguageCodeValidator( $services ) );
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
				WbCrud::getPropertyDataRetriever( $services )
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
				WbCrud::getPropertyDataRetriever( $services )
			);
		},

	VRD::PROPERTY_STATEMENT_ID_REQUEST_VALIDATOR => function (): PropertyStatementIdRequestValidator {
		return new PropertyStatementIdRequestValidator();
	},

	VRD::SITELINK_EDIT_REQUEST_VALIDATING_DESERIALIZER =>
		function( MediaWikiServices $services ): SitelinkEditRequestValidatingDeserializer {
			return new SitelinkEditRequestValidatingDeserializer(
				new SiteLinkLookupSitelinkValidator(
					WbCrud::getSitelinkDeserializer( $services ),
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
				new StatementValidator( WbCrud::getStatementDeserializer( $services ) )
			);
		},

	'WbCrud.AddItemAliasesInLanguage' => function( MediaWikiServices $services ): AddItemAliasesInLanguage {
		return new AddItemAliasesInLanguage(
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getItemUpdater( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.AddItemStatement' => function( MediaWikiServices $services ): AddItemStatement {
		return new AddItemStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services ),
			new GuidGenerator(),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.AddPropertyAliasesInLanguage' => function( MediaWikiServices $services ): AddPropertyAliasesInLanguage {
		return new AddPropertyAliasesInLanguage(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getPropertyUpdater( $services ),
		);
	},

	'WbCrud.AddPropertyStatement' => function( MediaWikiServices $services ): AddPropertyStatement {
		$statementReadModelConverter = new StatementReadModelConverter(
			WikibaseRepo::getStatementGuidParser( $services ),
			WikibaseRepo::getPropertyDataTypeLookup( $services )
		);
		return new AddPropertyStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			new EntityRevisionLookupPropertyDataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services ),
				$statementReadModelConverter
			),
			new GuidGenerator(),
			WbCrud::getPropertyUpdater( $services ),
			new AssertUserIsAuthorized(
				new WikibaseEntityPermissionChecker(
					WikibaseRepo::getEntityPermissionChecker( $services ),
					$services->getUserFactory()
				)
			)
		);
	},

	'WbCrud.AliasLanguageCodeValidator' => function( MediaWikiServices $services ): AliasLanguageCodeValidator {
		return new ValueValidatorLanguageCodeValidator( WikibaseRepo::getTermValidatorFactory( $services )->getAliasLanguageValidator() );
	},

	'WbCrud.AssertItemExists' => function( MediaWikiServices $services ): AssertItemExists {
		return new AssertItemExists( WbCrud::getGetLatestItemRevisionMetadata( $services ) );
	},

	'WbCrud.AssertPropertyExists' => function( MediaWikiServices $services ): AssertPropertyExists {
		return new AssertPropertyExists( WbCrud::getGetLatestPropertyRevisionMetadata( $services ) );
	},

	'WbCrud.AssertStatementSubjectExists' => function( MediaWikiServices $services ): AssertStatementSubjectExists {
		return new AssertStatementSubjectExists( WbCrud::getGetLatestStatementSubjectRevisionMetadata( $services ) );
	},

	'WbCrud.AssertUserIsAuthorized' => function( MediaWikiServices $services ): AssertUserIsAuthorized {
		return new AssertUserIsAuthorized(
			new WikibaseEntityPermissionChecker(
				WikibaseRepo::getEntityPermissionChecker( $services ),
				$services->getUserFactory()
			)
		);
	},

	'WbCrud.CreateItem' => function( MediaWikiServices $services ): CreateItem {
		return new CreateItem(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getItemUpdater( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.CreateProperty' => function( MediaWikiServices $services ): CreateProperty {
		return new CreateProperty(
			new CreatePropertyValidator(
				WbCrud::getEditMetadataRequestValidatingDeserializer( $services ),
				WikibaseRepo::getDataTypeDefinitions()->getTypeIds(),
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbCrud::getLabelLanguageCodeValidator( $services )
				),
				new PropertyLabelsContentsValidator(
					new TermValidatorFactoryPropertyLabelValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getPropertyTermsCollisionDetector( $services )
					)
				),
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbCrud::getDescriptionLanguageCodeValidator( $services )
				),
				new PropertyDescriptionsContentsValidator(
					new TermValidatorFactoryPropertyDescriptionValidator( WikibaseRepo::getTermValidatorFactory( $services ) )
				),
				new AliasesValidator(
					new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
					WbCrud::getAliasLanguageCodeValidator( $services ),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() )
				),
				new StatementsValidator( new StatementValidator( WbCrud::getStatementDeserializer() ) )
			),
			WbCrud::getPropertyUpdater( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.DescriptionLanguageCodeValidator' => function( MediaWikiServices $services ): DescriptionLanguageCodeValidator {
		return new ValueValidatorLanguageCodeValidator(
			WikibaseRepo::getTermValidatorFactory( $services )->getDescriptionLanguageValidator()
		);
	},

	'WbCrud.EntityUpdater' => function( MediaWikiServices $services ): EntityUpdater {
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

	'WbCrud.ErrorReporter' => function( MediaWikiServices $services ): ErrorReporter {
		return new MWErrorReporter();
	},

	'WbCrud.GetItem' => function( MediaWikiServices $services ): GetItem {
		return new GetItem(
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetItemAliases' => function( MediaWikiServices $services ): GetItemAliases {
		return new GetItemAliases(
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetItemAliasesInLanguage' => function( MediaWikiServices $services ): GetItemAliasesInLanguage {
		return new GetItemAliasesInLanguage(
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetItemDescription' => function( MediaWikiServices $services ): GetItemDescription {
		return new GetItemDescription(
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetItemDescriptions' => function( MediaWikiServices $services ): GetItemDescriptions {
		return new GetItemDescriptions(
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetItemDescriptionWithFallback' => function( MediaWikiServices $services ): GetItemDescriptionWithFallback {
		return new GetItemDescriptionWithFallback(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			new FallbackLookupFactoryTermsRetriever(
				$services->getLanguageFactory(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
			)
		);
	},

	'WbCrud.GetItemLabel' => function( MediaWikiServices $services ): GetItemLabel {
		return new GetItemLabel(
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetItemLabels' => function( MediaWikiServices $services ): GetItemLabels {
		return new GetItemLabels(
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetItemLabelWithFallback' => function( MediaWikiServices $services ): GetItemLabelWithFallback {
		return new GetItemLabelWithFallback(
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			new FallbackLookupFactoryTermsRetriever(
				$services->getLanguageFactory(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
			),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetItemStatement' => function( MediaWikiServices $services ): GetItemStatement {
		return new GetItemStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getGetStatement( $services )
		);
	},

	'WbCrud.GetItemStatements' => function( MediaWikiServices $services ): GetItemStatements {
		return new GetItemStatements(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getGetLatestItemRevisionMetadata( $services )
		);
	},

	'WbCrud.GetLatestItemRevisionMetadata' => function( MediaWikiServices $services ): GetLatestItemRevisionMetadata {
		return new GetLatestItemRevisionMetadata( new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services )
		) );
	},

	'WbCrud.GetLatestPropertyRevisionMetadata' => function( MediaWikiServices $services ): GetLatestPropertyRevisionMetadata {
		return new GetLatestPropertyRevisionMetadata( new WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services )
		) );
	},

	'WbCrud.GetLatestStatementSubjectRevisionMetadata' => function(
		MediaWikiServices $services
	): GetLatestStatementSubjectRevisionMetadata {
		return new GetLatestStatementSubjectRevisionMetadata( new WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services )
		) );
	},

	'WbCrud.GetProperty' => function( MediaWikiServices $services ): GetProperty {
		return new GetProperty(
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetPropertyAliases' => function( MediaWikiServices $services ): GetPropertyAliases {
		return new GetPropertyAliases(
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetPropertyAliasesInLanguage' => function( MediaWikiServices $services ): GetPropertyAliasesInLanguage {
		return new GetPropertyAliasesInLanguage(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			)
		);
	},

	'WbCrud.GetPropertyDescription' => function( MediaWikiServices $services ): GetPropertyDescription {
		return new GetPropertyDescription(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services )
		);
	},

	'WbCrud.GetPropertyDescriptions' => function( MediaWikiServices $services ): GetPropertyDescriptions {
		return new GetPropertyDescriptions(
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetPropertyDescriptionWithFallback' => function( MediaWikiServices $services ): GetPropertyDescriptionWithFallback {
		return new GetPropertyDescriptionWithFallback(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			new FallbackLookupFactoryTermsRetriever(
				$services->getLanguageFactory(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
			)
		);
	},

	'WbCrud.GetPropertyLabel' => function( MediaWikiServices $services ): GetPropertyLabel {
		return new GetPropertyLabel(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services )
		);
	},

	'WbCrud.GetPropertyLabels' => function( MediaWikiServices $services ): GetPropertyLabels {
		return new GetPropertyLabels(
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.GetPropertyLabelWithFallback' => function( MediaWikiServices $services ): GetPropertyLabelWithFallback {
		return new GetPropertyLabelWithFallback(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getGetLatestPropertyRevisionMetadata( $services ),
			new FallbackLookupFactoryTermsRetriever(
				$services->getLanguageFactory(),
				WikibaseRepo::getFallbackLabelDescriptionLookupFactory( $services )
			)
		);
	},

	'WbCrud.GetPropertyStatement' => function( MediaWikiServices $services ): GetPropertyStatement {
		return new GetPropertyStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getGetStatement( $services )
		);
	},

	'WbCrud.GetPropertyStatements' => function( MediaWikiServices $services ): GetPropertyStatements {
		return new GetPropertyStatements(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getGetLatestPropertyRevisionMetadata( $services )
		);
	},

	'WbCrud.GetSitelink' => function( MediaWikiServices $services ): GetSitelink {
		return new GetSitelink(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			WbCrud::getItemDataRetriever( $services ),
		);
	},

	'WbCrud.GetSitelinks' => function( MediaWikiServices $services ): GetSitelinks {
		return new GetSitelinks(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getGetLatestItemRevisionMetadata( $services ),
			WbCrud::getItemDataRetriever( $services ),
		);
	},

	'WbCrud.GetStatement' => function( MediaWikiServices $services ): GetStatement {
		return new GetStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getStatementRetriever( $services ),
			WbCrud::getGetLatestStatementSubjectRevisionMetadata( $services )
		);
	},

	'WbCrud.ItemDataRetriever' => function( MediaWikiServices $services ): EntityRevisionLookupItemDataRetriever {
		return new EntityRevisionLookupItemDataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			),
			new SitelinksReadModelConverter( $services->getSiteLookup() )
		);
	},

	'WbCrud.ItemUpdater' => function( MediaWikiServices $services ): EntityUpdaterItemUpdater {
		return new EntityUpdaterItemUpdater(
			WbCrud::getEntityUpdater( $services ),
			new SitelinksReadModelConverter( $services->getSiteLookup() ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbCrud.LabelLanguageCodeValidator' => function( MediaWikiServices $services ): LabelLanguageCodeValidator {
		return new ValueValidatorLanguageCodeValidator( WikibaseRepo::getTermValidatorFactory( $services )->getLabelLanguageValidator() );
	},

	'WbCrud.PatchItem' => function( MediaWikiServices $services ): PatchItem {
		return new PatchItem(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists(),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getItemDataRetriever(),
			new ItemSerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				new StatementListSerializer( WbCrud::getStatementSerializer( $services ) ),
				new SitelinksSerializer( new SitelinkSerializer() )
			),
			new PatchJson( new JsonDiffJsonPatcher() ),
			new PatchedItemValidator(
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbCrud::getLabelLanguageCodeValidator( $services )
				),
				new ItemLabelsContentsValidator(
					new TermValidatorFactoryItemLabelValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getItemTermsCollisionDetector()
					)
				),
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbCrud::getDescriptionLanguageCodeValidator( $services )
				),
				new ItemDescriptionsContentsValidator(
					new TermValidatorFactoryItemDescriptionValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getItemTermsCollisionDetector()
					)
				),
				new AliasesValidator(
					new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
					WbCrud::getAliasLanguageCodeValidator( $services ),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() )
				),
				new SitelinksValidator(
					new SiteIdValidator( WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services )->getList(
						WikibaseRepo::getSettings( $services )->getSetting( 'siteLinkGroups' )
					) ),
					new SiteLinkLookupSitelinkValidator(
						WbCrud::getSitelinkDeserializer( $services ),
						WikibaseRepo::getStore( $services )->newSiteLinkStore()
					),
				),
				new StatementsValidator( new StatementValidator( WbCrud::getStatementDeserializer() ) )
			),
			WbCrud::getItemDataRetriever(),
			WbCrud::getItemUpdater()
		);
	},

	'WbCrud.PatchItemAliases' => function( MediaWikiServices $services ): PatchItemAliases {
		return new PatchItemAliases(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new AliasesSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			new PatchedItemAliasesValidator(
				new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
				new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
				WbCrud::getAliasLanguageCodeValidator( $services )
			),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services )
		);
	},

	'WbCrud.PatchItemDescriptions' => function( MediaWikiServices $services ): PatchItemDescriptions {
		return new PatchItemDescriptions(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services ),
			new DescriptionsSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbCrud::getItemDataRetriever( $services ),
			new PatchedItemDescriptionsValidator(
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbCrud::getDescriptionLanguageCodeValidator( $services )
				),
				new ItemDescriptionsContentsValidator( new TermValidatorFactoryItemDescriptionValidator(
					WikibaseRepo::getTermValidatorFactory( $services ),
					WikibaseRepo::getItemTermsCollisionDetector( $services )
				) )
			),
			WbCrud::getItemUpdater( $services )
		);
	},

	'WbCrud.PatchItemLabels' => function( MediaWikiServices $services ): PatchItemLabels {
		return new PatchItemLabels(
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getTermLookupEntityTermsRetriever( $services ),
			new LabelsSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			new PatchedItemLabelsValidator(
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbCrud::getLabelLanguageCodeValidator( $services )
				),
				new ItemLabelsContentsValidator(
					new TermValidatorFactoryItemLabelValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getItemTermsCollisionDetector( $services )
					)
				)
			),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services ),
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.PatchItemStatement' => function( MediaWikiServices $services ): PatchItemStatement {
		return new PatchItemStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getPatchStatement( $services )
		);
	},

	'WbCrud.PatchProperty' => function( MediaWikiServices $services ): PatchProperty {
		return new PatchProperty(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			new PropertySerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				new StatementListSerializer( WbCrud::getStatementSerializer( $services ) )
			),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbCrud::getPropertyUpdater( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			new PatchedPropertyValidator(
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbCrud::getLabelLanguageCodeValidator( $services )
				),
				new PropertyLabelsContentsValidator(
					new TermValidatorFactoryPropertyLabelValidator(
						WikibaseRepo::getTermValidatorFactory( $services ),
						WikibaseRepo::getPropertyTermsCollisionDetector( $services )
					)
				),
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbCrud::getDescriptionLanguageCodeValidator( $services )
				),
				new PropertyDescriptionsContentsValidator(
					new TermValidatorFactoryPropertyDescriptionValidator( WikibaseRepo::getTermValidatorFactory( $services ) )
				),
				new AliasesValidator(
					new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
					WbCrud::getAliasLanguageCodeValidator( $services ),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() )
				),
				new StatementsValidator( new StatementValidator( WbCrud::getStatementDeserializer( $services ) ) )
			)
		);
	},

	'WbCrud.PatchPropertyAliases' => function( MediaWikiServices $services ): PatchPropertyAliases {
		$termLanguages = WikibaseRepo::getTermsLanguages( $services );

		return new PatchPropertyAliases(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				$termLanguages
			),
			new AliasesSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			new PatchedPropertyAliasesValidator(
				new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
				new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
				WbCrud::getAliasLanguageCodeValidator( $services )
			),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getPropertyUpdater( $services )
		);
	},

	'WbCrud.PatchPropertyDescriptions' => function( MediaWikiServices $services ): PatchPropertyDescriptions {
		return new PatchPropertyDescriptions(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new DescriptionsSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbCrud::getPropertyDataRetriever( $services ),
			new PatchedPropertyDescriptionsValidator(
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					WbCrud::getDescriptionLanguageCodeValidator( $services )
				),
				new PropertyDescriptionsContentsValidator(
					new TermValidatorFactoryPropertyDescriptionValidator( WikibaseRepo::getTermValidatorFactory( $services ) )
				)
			),
			WbCrud::getPropertyUpdater( $services )
		);
	},

	'WbCrud.PatchPropertyLabels' => function( MediaWikiServices $services ): PatchPropertyLabels {
		return new PatchPropertyLabels(
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new LabelsSerializer(),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getPropertyUpdater( $services ),
			WbCrud::getValidatingRequestDeserializer( $services ),
			new PatchedPropertyLabelsValidator(
				new LabelsSyntaxValidator(
					new LabelsDeserializer(),
					WbCrud::getLabelLanguageCodeValidator( $services )
				),
				new PropertyLabelsContentsValidator( new TermValidatorFactoryPropertyLabelValidator(
					WikibaseRepo::getTermValidatorFactory( $services ),
					WikibaseRepo::getPropertyTermsCollisionDetector( $services )
				) )
			),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.PatchPropertyStatement' => function( MediaWikiServices $services ): PatchPropertyStatement {
		return new PatchPropertyStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getPatchStatement( $services )
		);
	},

	'WbCrud.PatchSitelinks' => function( MediaWikiServices $services ): PatchSitelinks {
		return new PatchSitelinks(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getItemDataRetriever( $services ),
			new SitelinksSerializer( new SitelinkSerializer() ),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbCrud::getItemDataRetriever( $services ),
			new PatchedSitelinksValidator( new SitelinksValidator(
				new SiteIdValidator( WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services )->getList(
					WikibaseRepo::getSettings( $services )->getSetting( 'siteLinkGroups' )
				) ),
				new SiteLinkLookupSitelinkValidator(
					WbCrud::getSitelinkDeserializer( $services ),
					WikibaseRepo::getStore( $services )->newSiteLinkStore()
				),
			) ),
			WbCrud::getItemUpdater( $services )
		);
	},

	'WbCrud.PatchStatement' => function( MediaWikiServices $services ): PatchStatement {
		return new PatchStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			new PatchedStatementValidator( new StatementValidator( WbCrud::getStatementDeserializer( $services ) ) ),
			new PatchJson( new JsonDiffJsonPatcher() ),
			WbCrud::getStatementSerializer( $services ),
			WbCrud::getAssertStatementSubjectExists( $services ),
			WbCrud::getStatementRetriever( $services ),
			WbCrud::getStatementUpdater( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.PreconditionMiddlewareFactory' => function( MediaWikiServices $services ): PreconditionMiddlewareFactory {
		return new PreconditionMiddlewareFactory(
			WikibaseRepo::getEntityRevisionLookup( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			new ConditionalHeaderUtil()
		);
	},

	'WbCrud.PropertyDataRetriever' => function( MediaWikiServices $services ): EntityRevisionLookupPropertyDataRetriever {
		return new EntityRevisionLookupPropertyDataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			),
		);
	},

	'WbCrud.PropertyUpdater' => function( MediaWikiServices $services ): EntityUpdaterPropertyUpdater {
		return new EntityUpdaterPropertyUpdater(
			WbCrud::getEntityUpdater( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbCrud.RemoveItemDescription' => function( MediaWikiServices $services ): RemoveItemDescription {
		return new RemoveItemDescription(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services )
		);
	},

	'WbCrud.RemoveItemLabel' => function( MediaWikiServices $services ): RemoveItemLabel {
		return new RemoveItemLabel(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services )
		);
	},

	'WbCrud.RemoveItemStatement' => function( MediaWikiServices $services ): RemoveItemStatement {
		return new RemoveItemStatement(
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getRemoveStatement( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.RemovePropertyDescription' => function( MediaWikiServices $services ): RemovePropertyDescription {
		return new RemovePropertyDescription(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getPropertyUpdater( $services )
		);
	},

	'WbCrud.RemovePropertyLabel' => function( MediaWikiServices $services ): RemovePropertyLabel {
		return new RemovePropertyLabel(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getPropertyUpdater( $services )
		);
	},

	'WbCrud.RemovePropertyStatement' => function( MediaWikiServices $services ): RemovePropertyStatement {
		return new RemovePropertyStatement(
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getRemoveStatement( $services ),
			WbCrud::getValidatingRequestDeserializer( $services )
		);
	},

	'WbCrud.RemoveSitelink' => function( MediaWikiServices $services ): RemoveSitelink {
		return new RemoveSitelink(
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.RemoveStatement' => function( MediaWikiServices $services ): RemoveStatement {
		return new RemoveStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getAssertStatementSubjectExists( $services ),
			WbCrud::getStatementRetriever( $services ),
			WbCrud::getStatementRemover( $services )
		);
	},

	'WbCrud.ReplaceItemStatement' => function( MediaWikiServices $services ): ReplaceItemStatement {
		return new ReplaceItemStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getReplaceStatement( $services )
		);
	},

	'WbCrud.ReplacePropertyStatement' => function( MediaWikiServices $services ): ReplacePropertyStatement {
		return new ReplacePropertyStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getReplaceStatement( $services )
		);
	},

	'WbCrud.ReplaceStatement' => function( MediaWikiServices $services ): ReplaceStatement {
		return new ReplaceStatement(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertStatementSubjectExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getStatementUpdater( $services )
		);
	},

	'WbCrud.SetItemDescription' => function( MediaWikiServices $services ): SetItemDescription {
		return new SetItemDescription(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.SetItemLabel' => function( MediaWikiServices $services ): SetItemLabel {
		return new SetItemLabel(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.SetPropertyDescription' => function( MediaWikiServices $services ): SetPropertyDescription {
		return new SetPropertyDescription(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getPropertyUpdater( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.SetPropertyLabel' => function( MediaWikiServices $services ): SetPropertyLabel {
		return new SetPropertyLabel(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getPropertyDataRetriever( $services ),
			WbCrud::getPropertyUpdater( $services ),
			WbCrud::getAssertPropertyExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services )
		);
	},

	'WbCrud.SetSitelink' => function( MediaWikiServices $services ): SetSitelink {
		return new SetSitelink(
			WbCrud::getValidatingRequestDeserializer( $services ),
			WbCrud::getAssertItemExists( $services ),
			WbCrud::getAssertUserIsAuthorized( $services ),
			WbCrud::getItemDataRetriever( $services ),
			WbCrud::getItemUpdater( $services )
		);
	},

	'WbCrud.SitelinkDeserializer' => function( MediaWikiServices $services ): SitelinkDeserializer {
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

	'WbCrud.StatementDeserializer' => function( MediaWikiServices $services ): StatementDeserializer {
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

	'WbCrud.StatementRedirectMiddlewareFactory' => function( MediaWikiServices $services ): StatementRedirectMiddlewareFactory {
		return new StatementRedirectMiddlewareFactory(
			WikibaseRepo::getEntityIdParser( $services ),
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) )
		);
	},

	'WbCrud.StatementRemover' => function( MediaWikiServices $services ): StatementRemover {
		return new EntityUpdaterStatementRemover(
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			WbCrud::getEntityUpdater( $services ),
		);
	},

	'WbCrud.StatementRetriever' => function( MediaWikiServices $services ): EntityRevisionLookupStatementRetriever {
		return new EntityRevisionLookupStatementRetriever(
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbCrud.StatementSerializer' => function( MediaWikiServices $services ): StatementSerializer {
		$propertyValuePairSerializer = new PropertyValuePairSerializer();
		$referenceSerializer = new ReferenceSerializer( $propertyValuePairSerializer );
		return new StatementSerializer( $propertyValuePairSerializer, $referenceSerializer );
	},

	'WbCrud.StatementUpdater' => function( MediaWikiServices $services ): StatementUpdater {
		return new EntityUpdaterStatementUpdater(
			WikibaseRepo::getStatementGuidParser( $services ),
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			WbCrud::getEntityUpdater( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbCrud.TermLookupEntityTermsRetriever' => function( MediaWikiServices $services ): TermLookupEntityTermsRetriever {
		return new TermLookupEntityTermsRetriever(
			WikibaseRepo::getTermLookup( $services ),
			WikibaseRepo::getTermsLanguages( $services )
		);
	},

	'WbCrud.UnexpectedErrorHandlerMiddleware' => function( MediaWikiServices $services ): UnexpectedErrorHandlerMiddleware {
		return new UnexpectedErrorHandlerMiddleware(
			$services->get( 'WbCrud.ErrorReporter' )
		);
	},

	'WbCrud.ValidatingRequestDeserializer' => function( MediaWikiServices $services ): VRD {
		return new VRD( $services );
	},

];
