<?php declare( strict_types=1 );

use DataValues\Serializers\DataValueSerializer;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemStatementRetriever;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemStatementsRetriever;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	'WbRestApi.BaseDataModelSerializerFactory' => function ( MediaWikiServices $services ): SerializerFactory {
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
			new GetItemStatementValidator( new StatementIdValidator( new ItemIdParser() ) ),
			new WikibaseEntityLookupItemStatementRetriever(
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
			new WikibaseEntityLookupItemStatementsRetriever(
				WikibaseRepo::getEntityLookup( $services )
			),
			new WikibaseEntityRevisionLookupItemRevisionMetadataRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			WikibaseRepo::getBaseDataModelSerializerFactory( $services )
				->newStatementListSerializer()
		);
	},

];
