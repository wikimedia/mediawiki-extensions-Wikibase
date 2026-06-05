<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemByExternalIdResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemBySitelinkResolver;
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
		ItemBySitelinkResolver $itemBySitelinkResolver,
		ItemByExternalIdResolver $itemByExternalIdResolver,
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
				'type' => Type::nonNull( Type::listOf( $this->types->getItemType() ) ),
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'description' => 'Fetch multiple items by their IDs, including labels, descriptions, aliases, sitelinks, and statements.',
				'args' => [
					'ids' => Type::nonNull( Type::listOf( Type::nonNull( $this->types->getItemIdType() ) ) ),
				],
				'resolve' => fn( $rootValue, array $args, $context ) => $itemResolver
					->resolveItems( $args['ids'], $context ),
				'complexity' => fn( int $childrenComplexity, array $args ) => count( $args['ids'] ) *
					GraphQLService::LOAD_ITEM_COMPLEXITY,
			],
			'itemByExternalId' => [
				'type' => $this->types->getItemByExternalIdResultType(),
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'description' => 'Fetch an item by external ID property and value. Returns the item if it was uniquely identified, a list of item IDs if multiple were found, or null if no match is found.',
				'args' => [
					'property' => [
						'type' => Type::nonNull( $this->types->getPropertyIdType() ),
						'description' => 'The property ID of the external identifier.',
					],
					'externalId' => [
						'type' => Type::nonNull( Type::string() ),
						'description' => 'The external identifier value to search for.',
					],
				],
				'resolve' => fn( $rootValue, array $args, $context ) => $itemByExternalIdResolver
					->resolve( $args['property'], $args['externalId'], $context ),
				'complexity' => fn() => GraphQLService::LOOKUP_ITEM_COMPLEXITY,
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
				'resolve' => fn( $rootValue, array $args, $context ) => $searchItemsResolver->resolve(
					$args['query'],
					$args['first'],
					$args['after'] ?? null,
					$context
				),
				'complexity' => fn() => GraphQLService::SEARCH_ITEMS_COMPLEXITY,
			],
			'itemBySitelink' => [
				'type' => $this->types->getItemType(),
				'description' => 'Look up a single item by a sitelink',
				'args' => [
					'siteId' => [
						'type' => Type::nonNull( $this->types->getSiteIdType() ),
						'description' => 'The sitelink\'s siteId',
					],
					'title' => [
						'type' => Type::nonNull( Type::string() ),
						'description' => 'The sitelink\'s title',
					],
				],
				'resolve' => fn( $rootValue, array $args, $context ) => $itemBySitelinkResolver
					->resolve( $args['siteId'], $args['title'], $context, ),
				'complexity' => fn() => GraphQLService::LOOKUP_ITEM_COMPLEXITY,
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
