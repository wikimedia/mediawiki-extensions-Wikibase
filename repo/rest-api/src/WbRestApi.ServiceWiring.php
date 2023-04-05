<?php declare( strict_types=1 );

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Reporter\MWErrorReporter;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementValidator;
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
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchedStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Domain\Services\ItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\PrefetchingTermLookupAliasesRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\TermLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityRevisionLookupItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	'WbRestApi.AddItemStatement' => function( MediaWikiServices $services ): AddItemStatement {
		return new AddItemStatement(
			new AddItemStatementValidator(
				new ItemIdValidator(),
				new StatementValidator( WbRestApi::getStatementDeserializer() ),
				new EditMetadataValidator(
					CommentStore::COMMENT_CHARACTER_LIMIT,
					ChangeTags::listExplicitlyDefinedTags()
				)
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			new GuidGenerator(),
			new WikibaseEntityPermissionChecker(
				WikibaseRepo::getEntityPermissionChecker( $services ),
				$services->getUserFactory()
			)
		);
	},

	'WbRestApi.ErrorReporter' => function( MediaWikiServices $services ): ErrorReporter {
		return new MWErrorReporter();
	},

	'WbRestApi.GetItem' => function( MediaWikiServices $services ): GetItem {
		return new GetItem(
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			WbRestApi::getItemDataRetriever( $services ),
			new GetItemValidator( new ItemIdValidator() )
		);
	},

	'WbRestApi.GetItemAliases' => function( MediaWikiServices $services ): GetItemAliases {
		return new GetItemAliases(
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new PrefetchingTermLookupAliasesRetriever(
				WikibaseRepo::getPrefetchingTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemAliasesValidator( new ItemIdValidator() )
		);
	},

	'WbRestApi.GetItemAliasesInLanguage' => function( MediaWikiServices $services ): GetItemAliasesInLanguage {
		return new GetItemAliasesInLanguage(
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
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
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new TermLookupItemDataRetriever(
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
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new TermLookupItemDataRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemDescriptionsValidator( new ItemIdValidator() )
		);
	},

	'WbRestApi.GetItemLabel' => function( MediaWikiServices $services ): GetItemLabel {
		return new GetItemLabel(
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new TermLookupItemDataRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemLabelValidator(
				new ItemIdValidator(),
				new LanguageCodeValidator( WikibaseRepo::getTermsLanguages( $services )->getLanguages() )
			)
		);
	},

	'WbRestApi.GetItemLabels' => function( MediaWikiServices $services ): GetItemLabels {
		return new GetItemLabels(
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new TermLookupItemDataRetriever(
				WikibaseRepo::getTermLookup( $services ),
				WikibaseRepo::getTermsLanguages( $services )
			),
			new GetItemLabelsValidator( new ItemIdValidator() )
		);
	},

	'WbRestApi.GetItemStatement' => function( MediaWikiServices $services ): GetItemStatement {
		return new GetItemStatement(
			new GetItemStatementValidator(
				new StatementIdValidator( new ItemIdParser() ),
				new ItemIdValidator()
			),
			WbRestApi::getItemDataRetriever( $services ),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			)
		);
	},

	'WbRestApi.GetItemStatements' => function( MediaWikiServices $services ): GetItemStatements {
		return new GetItemStatements(
			new GetItemStatementsValidator( new ItemIdValidator() ),
			WbRestApi::getItemDataRetriever( $services ),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			)
		);
	},

	'WbRestApi.ItemDataRetriever' => function( MediaWikiServices $services ): ItemDataRetriever {
		return new WikibaseEntityLookupItemDataRetriever(
			WikibaseRepo::getEntityLookup( $services ),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup()
			),
			new SiteLinksReadModelConverter( $services->getSiteLookup() )
		);
	},

	'WbRestApi.ItemUpdater' => function( MediaWikiServices $services ): ItemUpdater {
		return new MediaWikiEditEntityFactoryItemUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory( $services ),
			WikibaseRepo::getLogger( $services ),
			new EditSummaryFormatter( WikibaseRepo::getSummaryFormatter( $services ) ),
			$services->getPermissionManager(),
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser( $services ),
				WikibaseRepo::getPropertyDataTypeLookup()
			)
		);
	},

	'WbRestApi.PatchItemStatement' => function( MediaWikiServices $services ): PatchItemStatement {
		$itemDataRetriever = WbRestApi::getItemDataRetriever( $services );

		return new PatchItemStatement(
			new PatchItemStatementValidator(
				new ItemIdValidator(),
				new StatementIdValidator( new ItemIdParser() ),
				new JsonDiffJsonPatchValidator(),
				new EditMetadataValidator(
					CommentStore::COMMENT_CHARACTER_LIMIT,
					ChangeTags::listExplicitlyDefinedTags()
				)
			),
			new PatchedStatementValidator( new StatementValidator( WbRestApi::getStatementDeserializer( $services ) ) ),
			new JsonDiffJsonPatcher(),
			WbRestApi::getSerializerFactory( $services )->newStatementSerializer(),
			new StatementGuidParser( new ItemIdParser() ),
			$itemDataRetriever,
			$itemDataRetriever,
			WbRestApi::getItemUpdater( $services ),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new WikibaseEntityPermissionChecker(
				WikibaseRepo::getEntityPermissionChecker( $services ),
				$services->getUserFactory()
			)
		);
	},

	'WbRestApi.PreconditionMiddlewareFactory' => function( MediaWikiServices $services ): PreconditionMiddlewareFactory {
		return new PreconditionMiddlewareFactory(
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new ConditionalHeaderUtil()
		);
	},

	'WbRestApi.RemoveItemStatement' => function( MediaWikiServices $services ): RemoveItemStatement {
		return new RemoveItemStatement(
			new RemoveItemStatementValidator(
				new ItemIdValidator(),
				new StatementIdValidator( new ItemIdParser() ),
				new EditMetadataValidator(
					CommentStore::COMMENT_CHARACTER_LIMIT,
					ChangeTags::listExplicitlyDefinedTags()
				)
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new StatementGuidParser( new ItemIdParser() ),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			new WikibaseEntityPermissionChecker(
				WikibaseRepo::getEntityPermissionChecker( $services ),
				$services->getUserFactory()
			)
		);
	},

	'WbRestApi.ReplaceItemStatement' => function( MediaWikiServices $services ): ReplaceItemStatement {
		return new ReplaceItemStatement(
			new ReplaceItemStatementValidator(
				new ItemIdValidator(),
				new StatementIdValidator( new ItemIdParser() ),
				new StatementValidator( WbRestApi::getStatementDeserializer() ),
				new EditMetadataValidator(
					CommentStore::COMMENT_CHARACTER_LIMIT,
					ChangeTags::listExplicitlyDefinedTags()
				)
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services ),
			new WikibaseEntityPermissionChecker(
				WikibaseRepo::getEntityPermissionChecker( $services ),
				$services->getUserFactory()
			)
		);
	},

	'WbRestApi.SerializerFactory' => function( MediaWikiServices $services ): SerializerFactory {
		return new SerializerFactory(
			WikibaseRepo::getPropertyDataTypeLookup( $services )
		);
	},

	'WbRestApi.SetItemLabel' => function( MediaWikiServices $services ): SetItemLabel {
		return new SetItemLabel(
			WbRestApi::getItemDataRetriever( $services ),
			WbRestApi::getItemUpdater( $services )
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

	'WbRestApi.UnexpectedErrorHandlerMiddleware' => function( MediaWikiServices $services ): UnexpectedErrorHandlerMiddleware {
		return new UnexpectedErrorHandlerMiddleware(
			new ResponseFactory(),
			$services->get( 'WbRestApi.ErrorReporter' ),
			WikibaseRepo::getLogger( $services )
		);
	},

];
