<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\LanguageCodeType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\StringValueType;

/**
 * @license GPL-2.0-or-later
 */
class WbReuse {
	public static function getGraphQLService( ?ContainerInterface $services = null ): GraphQLService {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.GraphQLService' );
	}

	public static function getGraphQLSchema( ?ContainerInterface $services = null ): Schema {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.GraphQLSchema' );
	}

	public static function getItemLabelsResolver( ?ContainerInterface $services = null ): ItemLabelsResolver {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.ItemLabelsResolver' );
	}

	public static function getLanguageCodeType( ?ContainerInterface $services = null ): LanguageCodeType {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.LanguageCodeType' );
	}

	public static function getStringValueType( ?ContainerInterface $services = null ): StringValueType {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.StringValueType' );
	}
}
