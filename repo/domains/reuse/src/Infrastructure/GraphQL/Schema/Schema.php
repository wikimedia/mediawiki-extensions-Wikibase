<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;

/**
 * @license GPL-2.0-or-later
 */
class Schema extends GraphQLSchema {
	public function __construct(
		ItemResolver $itemResolver,
		private readonly ItemIdType $itemIdType,
		private readonly SiteIdType $siteIdType,
		private readonly LanguageCodeType $languageCodeType,
		private readonly PredicatePropertyType $predicatePropertyType,
		private readonly PropertyValuePairType $propertyValuePairType,
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
						'languageCode' => Type::nonNull( $this->languageCodeType ),
					],
					'resolve' => fn( Item $item, array $args ) => $item->labels
						->getLabelInLanguage( $args['languageCode'] )?->text,
				],
				'description' => [
					'type' => Type::string(),
					'args' => [
						'languageCode' => Type::nonNull( $this->languageCodeType ),
					],
					'resolve' => fn( Item $item, array $args ) => $item->descriptions
						->getDescriptionInLanguage( $args['languageCode'] )?->text,
				],
				'aliases' => [
					// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
					'type' => Type::nonNull( Type::listOf( Type::string() ) ),
					'args' => [
						'languageCode' => Type::nonNull( $this->languageCodeType ),
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
						'siteId' => Type::nonNull( $this->siteIdType ),
					],
					'resolve' => function( Item $item, array $args ) {
						$sitelink = $item->sitelinks->getSitelinkForSite( $args['siteId'] );
						return $sitelink ? [
							'title' => $sitelink->title,
							'url' => $sitelink->url,
						] : null;
					},
				],
				'statements' => [
					// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
					'type' => Type::nonNull( Type::listOf( $this->statementType() ) ),
					'args' => [
						'propertyId' => Type::nonNull( Type::string() ),
					],
					'resolve' => fn( Item $item, array $args ) => $item->statements
						->getStatementsByPropertyId( new NumericPropertyId( $args[ 'propertyId' ] ) ),
				],
			],
		] );
	}

	private function statementType(): ObjectType {
		return new ObjectType( [
			'name' => 'Statement',
			'fields' => [
				'id' => [
					'type' => Type::nonNull( Type::string() ),
					'resolve' => fn( Statement $statement ) => $statement->id,
				],
				'rank' => [
					'type' => Type::nonNull( $this->rankType() ),
					'resolve' => fn( Statement $statement ) => $statement->rank->asInt(),
				],
				'qualifiers' => [
					// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
					'type' => Type::nonNull( Type::listOf( $this->propertyValuePairType ) ),
					'args' => [
						'propertyId' => Type::nonNull( Type::string() ),
					],
					'resolve' => fn( Statement $statement, $args ) => $statement->qualifiers
						->getQualifiersByPropertyId( new NumericPropertyId( $args[ 'propertyId' ] ) ),
				],
				'property' => [
					'type' => Type::nonNull( $this->predicatePropertyType ),
					'resolve' => fn( Statement $statement ) => $statement->property,
				],
			],
		] );
	}

	private function rankType(): EnumType {
		return new EnumType( [
			'name' => 'Rank',
			'values' => [
				'deprecated' => [
					'value' => StatementWriteModel::RANK_DEPRECATED,
				],
				'normal' => [
					'value' => StatementWriteModel::RANK_NORMAL,
				],
				'preferred' => [
					'value' => StatementWriteModel::RANK_PREFERRED,
				],
			],
		] );
	}

}
