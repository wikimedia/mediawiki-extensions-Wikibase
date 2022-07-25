<?php declare( strict_types=1 );

use DataValues\Serializers\DataValueSerializer;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory as LegacySerializerFactory;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Serializers\SerializerFactory;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	'WbRestApi.AddItemStatement' => function( MediaWikiServices $services ): AddItemStatement {
		return new AddItemStatement(
			new AddItemStatementValidator(
				new ItemIdValidator(),
				new SnakValidatorStatementValidator(
					WikibaseRepo::getBaseDataModelDeserializerFactory()->newStatementDeserializer(),
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
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup() ),
			new MediaWikiEditEntityFactoryItemUpdater(
				RequestContext::getMain(),
				WikibaseRepo::getEditEntityFactory(),
				WikibaseRepo::getLogger( $services )
			),
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

	'WbRestApi.ReplaceItemStatement' => function( MediaWikiServices $services ): ReplaceItemStatement {
		return new ReplaceItemStatement(
			new ReplaceItemStatementValidator(
				new SnakValidatorStatementValidator(
					WikibaseRepo::getBaseDataModelDeserializerFactory()->newStatementDeserializer(),
					new SnakValidator(
						WikibaseRepo::getPropertyDataTypeLookup( $services ),
						WikibaseRepo::getDataTypeFactory( $services ),
						WikibaseRepo::getDataTypeValidatorFactory( $services )
					)
				)
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup() ),
			new MediaWikiEditEntityFactoryItemUpdater(
				RequestContext::getMain(),
				WikibaseRepo::getEditEntityFactory(),
				WikibaseRepo::getLogger( $services )
			)
		);
	},

	'WbRestApi.SerializerFactory' => function( MediaWikiServices $services ): SerializerFactory {
		// same as WikibaseRepo.BaseDataModelSerializerFactory but with OPTION_OBJECTS_FOR_MAPS
		$legacySerializerFactory = new LegacySerializerFactory(
			new DataValueSerializer(),
			LegacySerializerFactory::OPTION_OBJECTS_FOR_MAPS
		);

		return new SerializerFactory( $legacySerializerFactory, WikibaseRepo::getPropertyDataTypeLookup( $services ) );
	},

];
