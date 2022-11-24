<?php declare( strict_types=1 );

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\ConditionalHeaderUtil;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffStatementPatcher;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	'WbRestApi.AddItemStatement' => function( MediaWikiServices $services ): AddItemStatement {
		return new AddItemStatement(
			new AddItemStatementValidator(
				new ItemIdValidator(),
				new SnakValidatorStatementValidator(
					WbRestApi::getStatementDeserializer(),
					new SnakValidator(
						WikibaseRepo::getPropertyDataTypeLookup( $services ),
						WikibaseRepo::getDataTypeFactory( $services ),
						WikibaseRepo::getDataTypeValidatorFactory( $services )
					)
				),
				new EditMetadataValidator(
					CommentStore::COMMENT_CHARACTER_LIMIT,
					ChangeTags::listExplicitlyDefinedTags()
				)
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup( $services ) ),
			WbRestApi::getItemUpdater( $services ),
			new GuidGenerator(),
			new WikibaseEntityPermissionChecker(
				WikibaseRepo::getEntityPermissionChecker( $services ),
				$services->getUserFactory()
			)
		);
	},

	'WbRestApi.GetItem' => function( MediaWikiServices $services ): GetItem {
		return new GetItem(
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup( $services ) ),
			new GetItemValidator( new ItemIdValidator() )
		);
	},

	'WbRestApi.GetItemStatement' => function( MediaWikiServices $services ): GetItemStatement {
		return new GetItemStatement(
			new GetItemStatementValidator(
				new StatementIdValidator( new ItemIdParser() ),
				new ItemIdValidator()
			),
			new WikibaseEntityLookupItemDataRetriever(
				WikibaseRepo::getEntityLookup( $services )
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			)
		);
	},

	'WbRestApi.GetItemStatements' => function( MediaWikiServices $services ): GetItemStatements {
		return new GetItemStatements(
			new GetItemStatementsValidator( new ItemIdValidator() ),
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup( $services ) ),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			)
		);
	},

	'WbRestApi.ItemUpdater' => function( MediaWikiServices $services ): ItemUpdater {
		return new MediaWikiEditEntityFactoryItemUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory( $services ),
			WikibaseRepo::getLogger( $services ),
			new EditSummaryFormatter( WikibaseRepo::getSummaryFormatter( $services ) ),
			$services->getPermissionManager()
		);
	},

	'WbRestApi.PatchItemStatement' => function( MediaWikiServices $services ): PatchItemStatement {
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
			new StatementGuidParser( new ItemIdParser() ),
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup( $services ) ),
			new JsonDiffStatementPatcher(
				WbRestApi::getSerializerFactory( $services )->newStatementSerializer(),
				WbRestApi::getStatementDeserializer( $services ),
				new SnakValidator(
					WikibaseRepo::getPropertyDataTypeLookup( $services ),
					WikibaseRepo::getDataTypeFactory( $services ),
					WikibaseRepo::getDataTypeValidatorFactory( $services )
				)
			),
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
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup( $services ) ),
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
				new SnakValidatorStatementValidator(
					WbRestApi::getStatementDeserializer(),
					new SnakValidator(
						WikibaseRepo::getPropertyDataTypeLookup( $services ),
						WikibaseRepo::getDataTypeFactory( $services ),
						WikibaseRepo::getDataTypeValidatorFactory( $services )
					)
				),
				new EditMetadataValidator(
					CommentStore::COMMENT_CHARACTER_LIMIT,
					ChangeTags::listExplicitlyDefinedTags()
				)
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup( $services ) ),
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

	'WbRestApi.StatementDeserializer' => function( MediaWikiServices $services ): StatementDeserializer {
		$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
			WikibaseRepo::getEntityIdParser( $services ),
			WikibaseRepo::getPropertyDataTypeLookup( $services ),
			WikibaseRepo::getDataTypeDefinitions()->getValueTypes(),
			WikibaseRepo::getDataValueDeserializer( $services )
		);
		return new StatementDeserializer(
			$propertyValuePairDeserializer,
			new ReferenceDeserializer( $propertyValuePairDeserializer )
		);
	},

];
