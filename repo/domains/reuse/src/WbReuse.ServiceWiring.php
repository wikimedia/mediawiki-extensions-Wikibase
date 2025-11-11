<?php declare( strict_types=1 );

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabels;
use Wikibase\Repo\Domains\Reuse\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsRetriever;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Types;
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
					new StatementReadModelConverter(
						WikibaseRepo::getStatementGuidParser( $services ),
						WikibaseRepo::getPropertyDataTypeLookup( $services )
					)
				) )
			),
			WbReuse::getGraphQLTypes( $services ),
		);
	},
	'WbReuse.GraphQLService' => function( MediaWikiServices $services ): GraphQLService {
		return new GraphQLService(
			WbReuse::getGraphQLSchema( $services ),
			$services->getMainConfig(),
		);
	},
	'WbReuse.GraphQLTypes' => function( MediaWikiServices $services ): Types {
		return new Types(
			WikibaseRepo::getTermsLanguages( $services )->getLanguages(),
			WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services ),
			WikibaseRepo::getSettings( $services ),
			WbReuse::getPropertyLabelsResolver( $services ),
			WikibaseRepo::getDataTypeDefinitions( $services ),
		);
	},
	'WbReuse.ItemLabelsResolver' => function( MediaWikiServices $services ): ItemLabelsResolver {
		return new ItemLabelsResolver(
			new BatchGetItemLabels(
				new PrefetchingTermLookupBatchLabelsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) )
			)
		);
	},
	'WbReuse.PropertyLabelsResolver' => function( MediaWikiServices $services ): PropertyLabelsResolver {
		return new PropertyLabelsResolver(
			new BatchGetPropertyLabels(
				new PrefetchingTermLookupBatchLabelsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) )
			)
		);
	},
];
