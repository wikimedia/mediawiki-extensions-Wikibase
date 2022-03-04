<?php declare( strict_types = 1 );

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemRetriever;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\RouteHandlers\ApiNotEnabledRouteHandler;
use Wikibase\Repo\RestApi\RouteHandlers\RouteHandlerFeatureToggle;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [

	'WbRestApi.GetItem' => function ( MediaWikiServices $services ): GetItem {
		return new GetItem(
			new WikibaseEntityLookupItemRetriever(
				WikibaseRepo::getEntityRevisionLookup( $services )
			),
			new ItemSerializer(
				WikibaseRepo::getBaseDataModelSerializerFactory( $services )
					->newItemSerializer()
			)
		);
	},

	'WbRestApi.RouteHandlerFeatureToggle' => function ( MediaWikiServices $services ): RouteHandlerFeatureToggle {
		return new RouteHandlerFeatureToggle(
			WikibaseRepo::getSettings( $services )->getSetting( 'restApiEnabled' ),
			new ApiNotEnabledRouteHandler()
		);
	},

];
