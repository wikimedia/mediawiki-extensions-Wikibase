<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;

/**
 * @license GPL-2.0-or-later
 */
class Schema extends GraphQLSchema {
	public function __construct(
		ItemResolver $itemResolver,
		FacetedItemSearch $searchUseCase,
		private readonly Types $types,
	) {
		parent::__construct( [
			'query' => new ObjectType( [
				'name' => 'Query',
				'fields' => [
					'item' => [
						'type' => $this->types->getItemType(),
						'args' => [
							'id' => Type::nonNull( $this->types->getItemIdType() ),
						],
						'resolve' => fn( $rootValue, array $args, $context ) => $itemResolver
								->resolveItem( $args['id'], $context ),
						'complexity' => fn() => GraphQLService::LOAD_ITEM_COMPLEXITY,
					],
					'itemsById' => [
						// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
						'type' => Type::nonNull( Type::listOf( $this->types->getItemType() ) ),
						'args' => [
							// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
							'ids' => Type::nonNull( Type::listOf( Type::nonNull( $this->types->getItemIdType() ) ) ),
						],
						'resolve' => fn( $rootValue, array $args, $context ) => $itemResolver
								->resolveItems( $args['ids'], $context ),
						'complexity' => fn( int $childrenComplexity, array $args ) => count( $args['ids'] ) *
							GraphQLService::LOAD_ITEM_COMPLEXITY,
					],
					'searchItems' => [
						// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
						'type' => Type::nonNull( Type::listOf( $this->types->getItemSearchResultType() ) ),
						'args' => [
							'query' => Type::nonNull( $this->types->getItemSearchFilterType() ),
						],
						'resolve' => fn( $rootValue, array $args ) => array_map(
							fn( ItemSearchResult $searchResult ) => [ 'id' => $searchResult->itemId->getSerialization() ],
							$searchUseCase->execute(
								new FacetedItemSearchRequest( $args['query'] )
							)->results
						),
						'complexity' => fn() => GraphQLService::SEARCH_ITEMS_COMPLEXITY,
					],
				],
			] ),
		] );
	}
}
