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
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\ItemIdType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\LanguageCodeType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\PredicatePropertyType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\PropertyIdType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\PropertyValuePairType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\SiteIdType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\StringValueType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\ValueType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\ValueTypeType;
use Wikibase\Repo\Domains\Reuse\WbReuse;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [
	'WbReuse.GraphQLSchema' => function( MediaWikiServices $services ): Schema {
		$languageCodeType = WbReuse::getLanguageCodeType( $services );
		$predicatePropertyType = new PredicatePropertyType(
			WbReuse::getPropertyLabelsResolver( $services ),
			$languageCodeType,
		);
		$valueType = new ValueType( WikibaseRepo::getDataTypeDefinitions( $services )->getGraphqlValueTypes() );
		$valueTypeType = new ValueTypeType();

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
			$languageCodeType,
			$predicatePropertyType,
			new PropertyValuePairType( $predicatePropertyType, $valueType, $valueTypeType ),
			$valueType,
			$valueTypeType,
			new PropertyIdType()
		);
	},
	'WbReuse.GraphQLService' => function( MediaWikiServices $services ): GraphQLService {
		return new GraphQLService(
			WbReuse::getGraphQLSchema( $services ),
			$services->getMainConfig(),
		);
	},
	'WbReuse.ItemLabelsResolver' => function( MediaWikiServices $services ): ItemLabelsResolver {
		return new ItemLabelsResolver(
			new BatchGetItemLabels(
				new PrefetchingTermLookupBatchLabelsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) )
			)
		);
	},
	'WbReuse.LanguageCodeType' => function( MediaWikiServices $services ): LanguageCodeType {
		return new LanguageCodeType( WikibaseRepo::getTermsLanguages( $services )->getLanguages() );
	},
	'WbReuse.PropertyLabelsResolver' => function( MediaWikiServices $services ): PropertyLabelsResolver {
		return new PropertyLabelsResolver(
			new BatchGetPropertyLabels(
				new PrefetchingTermLookupBatchLabelsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) )
			)
		);
	},
	'WbReuse.StringValueType' => function( MediaWikiServices $services ): StringValueType {
		return new StringValueType();
	},
];
