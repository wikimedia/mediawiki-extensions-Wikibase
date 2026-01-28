<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse;

use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Wikibase\Repo\Domains\Reuse\Domain\Services\FacetedItemSearchEngine;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemDescriptionsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Types;

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

	public static function getGraphQLTypes( ?ContainerInterface $services = null ): Types {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.GraphQLTypes' );
	}

	public static function getItemDescriptionsResolver( ?ContainerInterface $services = null ): ItemDescriptionsResolver {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.ItemDescriptionsResolver' );
	}

	public static function getItemLabelsResolver( ?ContainerInterface $services = null ): ItemLabelsResolver {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.ItemLabelsResolver' );
	}

	public static function getPropertyLabelsResolver( ?ContainerInterface $services = null ): PropertyLabelsResolver {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.PropertyLabelsResolver' );
	}

	public static function getFacetedItemSearchEngine( ?ContainerInterface $services = null ): FacetedItemSearchEngine {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WbReuse.FacetedItemSearchEngine' );
	}
}
