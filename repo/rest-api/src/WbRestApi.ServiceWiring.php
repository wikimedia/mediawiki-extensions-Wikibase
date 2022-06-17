<?php declare( strict_types=1 );

use DataValues\Serializers\DataValueSerializer;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Serializers\StatementSerializer;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator;
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
					WikibaseRepo::getBaseDataModelDeserializerFactory()->newStatementDeserializer(),
					new SnakValidator(
						WikibaseRepo::getPropertyDataTypeLookup( $services ),
						WikibaseRepo::getDataTypeFactory( $services ),
						WikibaseRepo::getDataTypeValidatorFactory( $services )
					)
				),
				new EditMetadataValidator( ChangeTags::listExplicitlyDefinedTags() )
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new WikibaseEntityLookupItemDataRetriever( WikibaseRepo::getEntityLookup() ),
			new MediaWikiEditEntityFactoryItemUpdater(
				RequestContext::getMain(),
				WikibaseRepo::getEditEntityFactory()
			),
			new GuidGenerator()
		);
	},

	'WbRestApi.BaseDataModelSerializerFactory' => function( MediaWikiServices $services ): SerializerFactory {
		// same as WikibaseRepo.BaseDataModelSerializerFactory but with OPTION_OBJECTS_FOR_MAPS
		return new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_OBJECTS_FOR_MAPS );
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

	'WbRestApi.StatementListSerializer' => function( MediaWikiServices $services ): StatementListSerializer {
		return new StatementListSerializer( WbRestApi::getStatementSerializer( $services ), true );
	},

	'WbRestApi.StatementSerializer' => function( MediaWikiServices $services ): StatementSerializer {
		return new StatementSerializer( WbRestApi::getBaseDataModelSerializerFactory( $services )->newStatementSerializer() );
	},

];
