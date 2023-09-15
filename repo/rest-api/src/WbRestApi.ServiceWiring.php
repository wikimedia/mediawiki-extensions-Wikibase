<?php declare( strict_types=1 );

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Reporter\MWErrorReporter;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetProperty;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\GetPropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchedLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchedStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Domain\Services\StatementRemover;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupPropertyDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupStatementRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterStatementRemover;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterStatementUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\PrefetchingTermLookupAliasesRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\TermLookupEntityTermsRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityRevisionLookupItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityRevisionLookupStatementSubjectRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator;
use Wikibase\Repo\RestApi\Infrastructure\LabelsEditSummaryToFormattableSummaryConverter;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryLabelTextValidator;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoItemDescriptionValidator;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoItemLabelValidator;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	'WbRestApi.AddItemStatement' => function( MediaWikiServices $services ): AddItemStatement {
		return new AddItemStatement(
			new AddItemStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			new GuidGenerator(),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.AddPropertyStatement' => function( MediaWikiServices $services ): AddPropertyStatement {
		$statementReadModelConverter = new StatementReadModelConverter(
			WikibaseRepo::getStatementGuidParser( $services ),
			WikibaseRepo::getPropertyDataTypeLookup( $services )
		);
		return new AddPropertyStatement(
			new AddPropertyStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertPropertyExists(),
			new EntityRevisionLookupPropertyDataRetriever(
				WikibaseRepo::getEntityRevisionLookup(),
				$statementReadModelConverter
			),
			new GuidGenerator(),
			new EntityUpdaterPropertyUpdater( WbRestApi::getEntityUpdater(), $statementReadModelConverter ),
			new AssertUserIsAuthorized(
				new WikibaseEntityPermissionChecker(
					WikibaseRepo::getEntityPermissionChecker( $services ),
					$services->getUserFactory()
				)
			)
		);
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

	'WbRestApi.EditMetadataValidator' => fn( MediaWikiServices $services ) => new EditMetadataValidator(
		CommentStore::COMMENT_CHARACTER_LIMIT,
		ChangeTags::listExplicitlyDefinedTags()
	),

	'WbRestApi.EntityUpdater' => function( MediaWikiServices $services ): EntityUpdater {
		return new EntityUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory( $services ),
			WikibaseRepo::getLogger( $services ),
			new EditSummaryFormatter(
				WikibaseRepo::getSummaryFormatter( $services ),
				new LabelsEditSummaryToFormattableSummaryConverter()
			),
			$services->getPermissionManager(),
		);
	},

	'WbRestApi.ErrorReporter' => function( MediaWikiServices $services ): ErrorReporter {
		return new MWErrorReporter();
	},

	'WbRestApi.GetItem' => function( MediaWikiServices $services ): GetItem {
		return new GetItem(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			new GetItemValidator( WbRestApi::getValidatingRequestDeserializer( $services ) )
		);
	},

	'WbRestApi.GetItemAliases' => function( MediaWikiServices $services ): GetItemAliases {
		return new GetItemAliases(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemAliasesValidator( WbRestApi::getValidatingRequestDeserializer( $services ) )
		);
	},

	'WbRestApi.GetItemAliasesInLanguage' => function( MediaWikiServices $services ): GetItemAliasesInLanguage {
		return new GetItemAliasesInLanguage(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemAliasesInLanguageValidator(
				new ItemIdValidator(),
				new LanguageCodeValidator( WikibaseRepo::getTermsLanguages( $services )->getLanguages() )
			)
		);
	},

	'WbRestApi.GetItemDescription' => function( MediaWikiServices $services ): GetItemDescription {
		return new GetItemDescription(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemDescriptionValidator(
				new ItemIdValidator(),
				new LanguageCodeValidator( WikibaseRepo::getTermsLanguages( $services )->getLanguages() )
			)
		);
	},

	'WbRestApi.GetItemDescriptions' => function( MediaWikiServices $services ): GetItemDescriptions {
		return new GetItemDescriptions(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemDescriptionsValidator( WbRestApi::getValidatingRequestDeserializer( $services ) )
		);
	},

	'WbRestApi.GetItemLabel' => function( MediaWikiServices $services ): GetItemLabel {
		return new GetItemLabel(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemLabelValidator( WbRestApi::getValidatingRequestDeserializer( $services ) )
		);
	},

	'WbRestApi.GetItemLabels' => function( MediaWikiServices $services ): GetItemLabels {
		return new GetItemLabels(
			WbRestApi::getGetLatestItemRevisionMetadata( $services ),
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemLabelsValidator( WbRestApi::getValidatingRequestDeserializer( $services ) )
		);
	},

	'WbRestApi.GetItemStatement' => function( MediaWikiServices $services ): GetItemStatement {
		return new GetItemStatement(
			new GetItemStatementValidator( new ItemIdValidator() ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getGetStatement( $services )
		);
	},

	'WbRestApi.GetItemStatements' => function( MediaWikiServices $services ): GetItemStatements {
		return new GetItemStatements(
			new GetItemStatementsValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
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
			new GetPropertyValidator( new PropertyIdValidator() )
		);
	},

	'WbRestApi.GetPropertyLabels' => function( MediaWikiServices $services ): GetPropertyLabels {
		return new GetPropertyLabels(
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services ),
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetPropertyLabelsValidator( WbRestApi::getValidatingRequestDeserializer( $services ) )
		);
	},

	'WbRestApi.GetPropertyStatement' => function( MediaWikiServices $services ): GetPropertyStatement {
		return new GetPropertyStatement(
			new GetPropertyStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getGetStatement( $services )
		);
	},

	'WbRestApi.GetPropertyStatements' => function( MediaWikiServices $services ): GetPropertyStatements {
		return new GetPropertyStatements(
			new GetPropertyStatementsValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getPropertyDataRetriever( $services ),
			WbRestApi::getGetLatestPropertyRevisionMetadata( $services )
		);
	},

	'WbRestApi.GetStatement' => function( MediaWikiServices $services ): GetStatement {
		return new GetStatement(
			new GetStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getStatementRetriever( $services ),
			WbRestApi::getGetLatestStatementSubjectRevisionMetadata( $services )
		);
	},

	'WbRestApi.ItemDataRetriever' => function( MediaWikiServices $services ): EntityRevisionLookupItemDataRetriever {
		return new EntityRevisionLookupItemDataRetriever(
			WikibaseRepo::getEntityRevisionLookup( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup()
			),
			new SiteLinksReadModelConverter( $services->getSiteLookup() )
		);
	},

	'WbRestApi.ItemUpdater' => function( MediaWikiServices $services ): ItemUpdater {
		return new EntityUpdaterItemUpdater(
			WbRestApi::getEntityUpdater( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup( $services )
			)
		);
	},

	'WbRestApi.PatchItemLabels' => function( MediaWikiServices $services ): PatchItemLabels {
		return new PatchItemLabels(
			WbRestApi::getAssertItemExists( $services ),
			new TermLookupEntityTermsRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new LabelsSerializer(),
			new JsonDiffJsonPatcher(),
			new PatchedLabelsValidator(
				new LabelsDeserializer(),
				new WikibaseRepoItemLabelValidator(
					new TermValidatorFactoryLabelTextValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
					WikibaseRepo::getItemTermsCollisionDetector( $services ),
					WbRestApi::getItemDataRetriever( $services )
				),
				new LanguageCodeValidator( WikibaseRepo::getTermsLanguages( $services )->getLanguages() )
			),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			new PatchItemLabelsValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.PatchItemStatement' => function( MediaWikiServices $services ): PatchItemStatement {
		return new PatchItemStatement(
			new PatchItemStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getPatchStatement( $services )
		);
	},

	'WbRestApi.PatchPropertyStatement' => function( MediaWikiServices $services ): PatchPropertyStatement {
		return new PatchPropertyStatement(
			new PatchPropertyStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getPatchStatement( $services )
		);
	},

	'WbRestApi.PatchStatement' => function( MediaWikiServices $services ): PatchStatement {
		return new PatchStatement(
			new PatchStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			new PatchedStatementValidator( new StatementValidator( WbRestApi::getStatementDeserializer( $services ) ) ),
			new JsonDiffJsonPatcher(),
			WbRestApi::getSerializerFactory( $services )->newStatementSerializer(),
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
				WikibaseRepo::getPropertyDataTypeLookup()
			),
		);
	},

	'WbRestApi.RemoveItemStatement' => function( MediaWikiServices $services ): RemoveItemStatement {
		return new RemoveItemStatement(
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getRemoveStatement( $services ),
			new RemoveItemStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) )
		);
	},

	'WbRestApi.RemoveStatement' => function( MediaWikiServices $services ): RemoveStatement {
		return new RemoveStatement(
			new RemoveStatementValidator(
				WbRestApi::getValidatingRequestDeserializer( $services )
			),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getAssertStatementSubjectExists( $services ),
			WbRestApi::getStatementRetriever( $services ),
			WbRestApi::getStatementRemover( $services )
		);
	},

	'WbRestApi.ReplaceItemStatement' => function( MediaWikiServices $services ): ReplaceItemStatement {
		return new ReplaceItemStatement(
			new ReplaceItemStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getReplaceStatement( $services )
		);
	},

	'WbRestApi.ReplacePropertyStatement' => function( MediaWikiServices $services ): ReplacePropertyStatement {
		return new ReplacePropertyStatement(
			new ReplacePropertyStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertPropertyExists( $services ),
			WbRestApi::getReplaceStatement( $services )
		);
	},

	'WbRestApi.ReplaceStatement' => function( MediaWikiServices $services ): ReplaceStatement {
		return new ReplaceStatement(
			new ReplaceStatementValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertStatementSubjectExists( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services ),
			WbRestApi::getStatementUpdater( $services )
		);
	},

	'WbRestApi.SerializerFactory' => function( MediaWikiServices $services ): SerializerFactory {
		return new SerializerFactory(
			WikibaseRepo::getPropertyDataTypeLookup( $services )
		);
	},

	'WbRestApi.SetItemDescription' => function( MediaWikiServices $services ): SetItemDescription {
		return new SetItemDescription(
			new SetItemDescriptionValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.SetItemLabel' => function( MediaWikiServices $services ): SetItemLabel {
		return new SetItemLabel(
			new SetItemLabelValidator( WbRestApi::getValidatingRequestDeserializer( $services ) ),
			WbRestApi::getAssertItemExists( $services ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			WbRestApi::getAssertUserIsAuthorized( $services )
		);
	},

	'WbRestApi.StatementDeserializer' => function( MediaWikiServices $services ): StatementDeserializer {
		$entityIdParser = WikibaseRepo::getEntityIdParser( $services );
		$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
			$entityIdParser,
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			new DataValuesValueDeserializer(
				new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory( $services ) ),
				$entityIdParser,
				WikibaseRepo::getDataValueDeserializer( $services ),
				WikibaseRepo::getDataTypeValidatorFactory( $services )
			)
		);
		return new StatementDeserializer(
			$propertyValuePairDeserializer,
			new ReferenceDeserializer( $propertyValuePairDeserializer )
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
				WikibaseRepo::getPropertyDataTypeLookup()
			)
		);
	},

	'WbRestApi.StatementUpdater' => function( MediaWikiServices $services ): StatementUpdater {
		return new EntityUpdaterStatementUpdater(
			WikibaseRepo::getStatementGuidParser( $services ),
			new StatementSubjectRetriever( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			WbRestApi::getEntityUpdater( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup()
			)
		);
	},

	'WbRestApi.UnexpectedErrorHandlerMiddleware' => function( MediaWikiServices $services ): UnexpectedErrorHandlerMiddleware {
		return new UnexpectedErrorHandlerMiddleware(
			new ResponseFactory(),
			$services->get( 'WbRestApi.ErrorReporter' ),
			WikibaseRepo::getLogger( $services )
		);
	},

	'WbRestApi.ValidatingRequestDeserializer' => function( MediaWikiServices $services ): ValidatingRequestDeserializer {
		return new ValidatingRequestDeserializer( new ValidatingRequestFieldDeserializerFactory(
			new LanguageCodeValidator( WikibaseRepo::getTermsLanguages( $services )->getLanguages() ),
			WbRestApi::getStatementDeserializer( $services ),
			new JsonDiffJsonPatchValidator(),
			new WikibaseRepoItemLabelValidator(
				new TermValidatorFactoryLabelTextValidator( WikibaseRepo::getTermValidatorFactory( $services ) ),
				WikibaseRepo::getItemTermsCollisionDetector( $services ),
				WbRestApi::getItemDataRetriever( $services )
			),
			new WikibaseRepoItemDescriptionValidator(
				WikibaseRepo::getTermValidatorFactory( $services ),
				WikibaseRepo::getItemTermsCollisionDetector( $services ),
				WbRestApi::getItemDataRetriever( $services )
			),
			CommentStore::COMMENT_CHARACTER_LIMIT,
			ChangeTags::listExplicitlyDefinedTags()
		) );
	},

];
