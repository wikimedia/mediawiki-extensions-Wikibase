<?php declare( strict_types = 1 );

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionRetriever;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	'WbRestApi.GetItem' => function ( MediaWikiServices $services ): GetItem {
		return new GetItem(
			new WikibaseEntityRevisionLookupItemRevisionRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new ItemSerializer(
				WikibaseRepo::getBaseDataModelSerializerFactory( $services )
					->newItemSerializer()
			),
			new GetItemValidator()
		);
	},

];
