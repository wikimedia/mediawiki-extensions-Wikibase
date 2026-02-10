<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\SearchItemsResolver;

/**
 * @license GPL-2.0-or-later
 */
class Schema extends GraphQLSchema {
	public readonly array $fieldNames;

	public function __construct(
		ItemResolver $itemResolver,
		SearchItemsResolver $searchItemsResolver,
		private readonly Types $types,
	) {
		$fieldDefinitions = [
			'item' => [
				'type' => $this->types->getItemType(),
				'description' => 'Fetch a single item by its ID, including labels, descriptions, aliases, sitelinks, and statements.',
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
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'description' => 'Fetch multiple items by their IDs, including labels, descriptions, aliases, sitelinks, and statements.',
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
				'type' => Type::nonNull( $this->types->getItemSearchResultConnectionType() ),
				'description' => 'Search for items that match one or more statements-based filters',
				'args' => [
					'query' => [
						'type' => Type::nonNull( $this->types->getItemSearchFilterType() ),
						'description' => 'Filter describing which statement properties and values must match.',
					],
					'first' => [
						'type' => Type::nonNull( Type::int() ),
						// phpcs:ignore Generic.Files.LineLength.TooLong
						'description' => 'The maximum number of items to return (up to ' . FacetedItemSearchRequest::MAX_LIMIT . ')',
						'defaultValue' => FacetedItemSearchRequest::DEFAULT_LIMIT,
					],
					'after' => [
						'type' => Type::string(),
						// phpcs:ignore Generic.Files.LineLength.TooLong
						'description' => 'Cursor that defines the position after which results should be returned. Usually the value of endCursor from the previous request',
					],
				],
				'resolve' => fn( $rootValue, array $args ) => $searchItemsResolver->resolve(
					$args['query'],
					$args['first'],
					$args['after'] ?? null,
				),
				'complexity' => fn() => GraphQLService::SEARCH_ITEMS_COMPLEXITY,
			],
		];
		$this->fieldNames = array_keys( $fieldDefinitions );

		parent::__construct( [
			'query' => new ObjectType( [
				'name' => 'Query',
				'fields' => $fieldDefinitions,
			] ),
		] );
	}
}
