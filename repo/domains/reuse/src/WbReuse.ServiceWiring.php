<?php declare( strict_types=1 );

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\WbReuse;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [
	'WbReuse.GraphQLSchema' => function( MediaWikiServices $services ): Schema {
		return new Schema(
			new ItemResolver(
				new BatchGetItems( new EntityLookupItemsBatchRetriever(
					WikibaseRepo::getEntityLookup( $services ),
					$services->getSiteLookup(),
				) )
			)
		);
	},
	'WbReuse.GraphQLService' => function( MediaWikiServices $services ): GraphQLService {
		return new GraphQLService( WbReuse::getGraphQLSchema( $services ) );
	},
];
