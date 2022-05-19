<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;

/**
 * @license GPL-2.0-or-later
 */
class WbRestApi {

	public static function getGetItem( ContainerInterface $services = null ): GetItem {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItem' );
	}

	public static function getGetItemStatements( ContainerInterface $services = null ): GetItemStatements {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemStatements' );
	}

	public static function getGetItemStatement( ContainerInterface $services = null ): GetItemStatement {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.GetItemStatement' );
	}

	public static function getBaseDataModelSerializerFactory( ContainerInterface $services = null ): SerializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbRestApi.BaseDataModelSerializerFactory' );
	}

}