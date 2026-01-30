<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Reference;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;

/**
 * @license GPL-2.0-or-later
 */
class ItemType extends ObjectType {
	public function __construct( private readonly Types $types ) {
		$labelProviderType = $types->getLabelProviderType();
		$labelField = clone $labelProviderType->getField( 'label' ); // cloned to not override the resolver in other places
		$labelField->resolveFn = fn( Item $item, array $args ) => $item->labels
			->getLabelInLanguage( $args['languageCode'] )?->text;

		$descriptionProviderType = $types->getDescriptionProviderType();
		$descriptionField = clone $descriptionProviderType->getField( 'description' ); // cloned to not override resolver
		$descriptionField->resolveFn = fn( Item $item, array $args ) => $item->descriptions
			->getDescriptionInLanguage( $args['languageCode'] )?->text;

		parent::__construct( [
			'fields' => [
				'id' => [
					'type' => Type::nonNull( $types->getItemIdType() ),
					'resolve' => fn( Item $item ) => $item->id->getSerialization(),
				],
				$labelField,
				$descriptionField,
				'aliases' => [
					// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
					'type' => Type::nonNull( Type::listOf( Type::string() ) ),
					'args' => [
						'languageCode' => Type::nonNull( $types->getLanguageCodeType() ),
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
						'siteId' => Type::nonNull( $types->getSiteIdType() ),
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
						'propertyId' => Type::nonNull( $types->getPropertyIdType() ),
					],
					'resolve' => fn( Item $item, array $args ) => $item->statements
						->getStatementsByPropertyId( new NumericPropertyId( $args[ 'propertyId' ] ) ),
				],
			],
			'interfaces' => [ $labelProviderType, $descriptionProviderType ],
		] );
	}

	private function statementType(): ObjectType {
		$propertyValuePairType = $this->types->getPropertyValuePairType();

		$qualifierType = new ObjectType( [
			'name' => 'Qualifier',
			'fields' => [
				$propertyValuePairType->getField( 'property' ),
				$propertyValuePairType->getField( 'value' ),
				$propertyValuePairType->getField( 'valueType' ),
			],
			'interfaces' => [ $propertyValuePairType ],
		] );

		return new ObjectType( [
			'name' => 'Statement',
			'fields' => [
				$propertyValuePairType->getField( 'property' ),
				$propertyValuePairType->getField( 'value' ),
				$propertyValuePairType->getField( 'valueType' ),
				'id' => [
					'type' => Type::nonNull( Type::string() ),
					'resolve' => fn( Statement $statement ) => $statement->id,
				],
				'rank' => [
					'type' => Type::nonNull( new EnumType( [
						'name' => 'Rank',
						'values' => [ 'PREFERRED', 'NORMAL', 'DEPRECATED' ],
					] ) ),
					'resolve' => fn( Statement $statement ) => match ( $statement->rank->asInt() ) {
						StatementWriteModel::RANK_PREFERRED => 'PREFERRED',
						StatementWriteModel::RANK_DEPRECATED => 'DEPRECATED',
						default => 'NORMAL',
					},
				],
				'qualifiers' => [
					// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
					'type' => Type::nonNull( Type::listOf( $qualifierType ) ),
					'args' => [
						'propertyId' => Type::nonNull( $this->types->getPropertyIdType() ),
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
			'interfaces' => [ $propertyValuePairType ],
		] );
	}

	private function referenceType(): ObjectType {
		$propertyValuePairType = $this->types->getPropertyValuePairType();
		$referencePartType = new ObjectType( [
			'name' => 'ReferencePart',
			'fields' => [
				$propertyValuePairType->getField( 'property' ),
				$propertyValuePairType->getField( 'value' ),
				$propertyValuePairType->getField( 'valueType' ),
			],
			'interfaces' => [ $propertyValuePairType ],
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
