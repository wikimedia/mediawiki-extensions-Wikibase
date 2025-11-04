<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Reference;
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
		private readonly PropertyValuePairType $propertyValuePairType,
		private readonly PropertyIdType $propertyIdType,
		private readonly InterfaceType $labelProviderType,
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
		$labelField = clone $this->labelProviderType->getField( 'label' ); // cloned to not override the resolver in other places
		$labelField->resolveFn = fn( Item $item, array $args ) => $item->labels
			->getLabelInLanguage( $args['languageCode'] )?->text;

		return new ObjectType( [
			'name' => 'Item',
			'fields' => [
				'id' => [
					'type' => Type::nonNull( $this->itemIdType ),
					'resolve' => fn( Item $item ) => $item->id->getSerialization(),
				],
				$labelField,
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
						'propertyId' => Type::nonNull( $this->propertyIdType ),
					],
					'resolve' => fn( Item $item, array $args ) => $item->statements
						->getStatementsByPropertyId( new NumericPropertyId( $args[ 'propertyId' ] ) ),
				],
			],
			'interfaces' => [ $this->labelProviderType ],
		] );
	}

	private function statementType(): ObjectType {
		$qualifierType = new ObjectType( [
			'name' => 'Qualifier',
			'fields' => [
				$this->propertyValuePairType->getField( 'property' ),
				$this->propertyValuePairType->getField( 'value' ),
				$this->propertyValuePairType->getField( 'valueType' ),
			],
			'interfaces' => [ $this->propertyValuePairType ],
		] );

		return new ObjectType( [
			'name' => 'Statement',
			'fields' => [
				$this->propertyValuePairType->getField( 'property' ),
				$this->propertyValuePairType->getField( 'value' ),
				$this->propertyValuePairType->getField( 'valueType' ),
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
					'type' => Type::nonNull( Type::listOf( $qualifierType ) ),
					'args' => [
						'propertyId' => Type::nonNull( $this->propertyIdType ),
					],
					'resolve' => fn( Statement $statement, $args ) => $statement->qualifiers
						->getQualifiersByPropertyId( new NumericPropertyId( $args[ 'propertyId' ] ) ),
				],
				'references' => [
					// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
					'type' => Type::nonNull( Type::listOf( $this->referenceType() ) ),
					'resolve' => fn( Statement $statement ) => $statement->references,
				],
			],
			'interfaces' => [ $this->propertyValuePairType ],
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

	private function referenceType(): ObjectType {
		$referencePartType = new ObjectType( [
			'name' => 'ReferencePart',
			'fields' => [
				$this->propertyValuePairType->getField( 'property' ),
				$this->propertyValuePairType->getField( 'value' ),
				$this->propertyValuePairType->getField( 'valueType' ),
			],
			'interfaces' => [ $this->propertyValuePairType ],
		] );

		return new ObjectType( [
			'name' => 'Reference',
			'fields' => [
				'parts' => [
					// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
					'type' => Type::nonNull( Type::listOf( $referencePartType ) ),
					'resolve' => fn( Reference $reference ) => $reference->parts,
				],
			],
		] );
	}

}
