<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;

/**
 * @license GPL-2.0-or-later
 */
class Schema extends GraphQLSchema {
	public function __construct(
		ItemResolver $itemResolver,
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
						'resolve' => fn( $rootValue, array $args ) => $itemResolver->resolveItem( $args['id'] ),
						'complexity' => fn() => GraphQLService::LOAD_ITEM_COMPLEXITY,
					],
					'itemsById' => [
						// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
						'type' => Type::nonNull( Type::listOf( $this->types->getItemType() ) ),
						'args' => [
							// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
							'ids' => Type::nonNull( Type::listOf( Type::nonNull( $this->types->getItemIdType() ) ) ),
						],
						'resolve' => fn( $rootValue, array $args ) => array_map(
							$itemResolver->resolveItem( ... ),
							$args[ 'ids' ]
						),
						'complexity' => fn( int $childrenComplexity, array $args ) => count( $args['ids'] ) *
							GraphQLService::LOAD_ITEM_COMPLEXITY,
					],
				],
			] ),
		] );
	}
}
