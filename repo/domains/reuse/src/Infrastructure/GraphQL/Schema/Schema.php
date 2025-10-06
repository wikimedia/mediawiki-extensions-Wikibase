<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;

/**
 * @license GPL-2.0-or-later
 */
class Schema extends GraphQLSchema {
	public function __construct(
		ItemResolver $itemResolver,
		private readonly ItemIdType $itemIdType,
	) {
		parent::__construct( [
			'query' => new ObjectType( [
				'name' => 'Query',
				'fields' => [
					'item' => [
						'type' => $this->itemType(),
						'args' => [
							'id' => Type::nonNull( $this->itemIdType ),
						],
						'resolve' => fn( $rootValue, array $args ) => $itemResolver->resolveItem( $args['id'] ),
					],
				],
			] ),
		] );
	}

	private function itemType(): ObjectType {
		return new ObjectType( [
			'name' => 'Item',
			'fields' => [
				'id' => [
					'type' => Type::nonNull( $this->itemIdType ),
					'resolve' => fn( Item $item ) => $item->id->getSerialization(),
				],
				'label' => [
					'type' => Type::string(),
					'args' => [
						'languageCode' => Type::nonNull( Type::string() ),
					],
					'resolve' => fn( Item $item, array $args ) => $item->labels
						->getLabelInLanguage( $args['languageCode'] )?->text,
				],
				'description' => [
					'type' => Type::string(),
					'args' => [
						'languageCode' => Type::nonNull( Type::string() ),
					],
					'resolve' => fn( Item $item, array $args ) => $item->descriptions
						->getDescriptionInLanguage( $args['languageCode'] )?->text,
				],
				'aliases' => [
					// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
					'type' => Type::nonNull( Type::listOf( Type::string() ) ),
					'args' => [
						'languageCode' => Type::nonNull( Type::string() ),
					],
					'resolve' => fn( Item $item, array $args ) => $item->aliases
						->getAliasesInLanguageInLanguage( $args['languageCode'] )?->aliases ?? [],
				],
				'sitelink' => [
					'type' => new ObjectType( [
						'name' => 'Sitelink',
						'fields' => [
							'title' => Type::nonNull( Type::string() ),
							'url' => Type::nonNull( Type::string() ),
						],
					] ),
					'args' => [
						'siteId' => Type::nonNull( Type::string() ),
					],
					'resolve' => function( Item $item, array $args ) {
						$sitelink = $item->sitelinks->getSitelinkForSite( $args['siteId'] );
						return $sitelink ? [
							'title' => $sitelink->title,
							'url' => $sitelink->url,
						] : null;
					},
				],
			],
		] );
	}

}
