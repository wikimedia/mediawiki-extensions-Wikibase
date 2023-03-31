<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\Repo\RestApi\Application\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\GetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddlewareFactory;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;

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

	public static function getGetItemAliasesInLanguage( ContainerInterface $services = null ): GetItemAliasesInLanguage {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemAliasesInLanguage' );
	}

	public static function getGetItemDescription( ContainerInterface $services = null ): GetItemDescription {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemDescription' );
	}

	public static function getGetItemDescriptions( ContainerInterface $services = null ): GetItemDescriptions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemDescriptions' );
	}

	public static function getGetItemLabel( ContainerInterface $services = null ): GetItemLabel {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemLabel' );
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
