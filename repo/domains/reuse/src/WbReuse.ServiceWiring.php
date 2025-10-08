<?php declare( strict_types=1 );

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\ItemIdType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\LanguageCodeType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\SiteIdType;
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
			new ItemIdType(),
			new SiteIdType(
				WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services ),
				WikibaseRepo::getSettings( $services ),
			),
			new LanguageCodeType( WikibaseRepo::getTermsLanguages( $services )->getLanguages() )
		);
	},
	'WbReuse.GraphQLService' => function( MediaWikiServices $services ): GraphQLService {
		return new GraphQLService(
			WbReuse::getGraphQLSchema( $services ),
			$services->getMainConfig(),
		);
	},
];
