<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\RestApi\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement;

/**
 * @license GPL-2.0-or-later
 */
class WbRestApi {

	public static function getGetItem( ContainerInterface $services = null ): GetItem {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItem' );
	}

	public static function getGetItemAliases( ContainerInterface $services = null ): GetItemAliases {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemAliases' );
	}

	public static function getGetItemDescriptions( ContainerInterface $services = null ): GetItemDescriptions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemDescriptions' );
	}

	public static function getGetItemLabels( ContainerInterface $services = null ): GetItemLabels {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemLabels' );
	}

	public static function getGetItemStatements( ContainerInterface $services = null ): GetItemStatements {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemStatements' );
	}

	public static function getGetItemStatement( ContainerInterface $services = null ): GetItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemStatement' );
	}

	public static function getSerializerFactory( ContainerInterface $services = null ): SerializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.SerializerFactory' );
	}

	public static function getAddItemStatement( ContainerInterface $services = null ): AddItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.AddItemStatement' );
	}

	public static function getReplaceItemStatement( ContainerInterface $services = null ): ReplaceItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.ReplaceItemStatement' );
	}

	public static function getRemoveItemStatement( ContainerInterface $services = null ): RemoveItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.RemoveItemStatement' );
	}

	public static function getPreconditionMiddlewareFactory( ContainerInterface $services = null ): PreconditionMiddlewareFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.PreconditionMiddlewareFactory' );
	}

	public static function getPatchItemStatement( ContainerInterface $services = null ): PatchItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.PatchItemStatement' );
	}

	public static function getItemUpdater( ContainerInterface $services = null ): ItemUpdater {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.ItemUpdater' );
	}

	public static function getItemDataRetriever( ContainerInterface $services = null ): WikibaseEntityLookupItemDataRetriever {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.ItemDataRetriever' );
	}

	public static function getStatementDeserializer( ContainerInterface $services = null ): StatementDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.StatementDeserializer' );
	}

	public static function getUnexpectedErrorHandlerMiddleware( ContainerInterface $services = null ): UnexpectedErrorHandlerMiddleware {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.UnexpectedErrorHandlerMiddleware' );
	}

}
