<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemDescriptionsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;

// The `return ... ??= ...;` shorthand syntax is too convenient in this file to disallow it.
// phpcs:disable MediaWiki.Usage.AssignmentInReturn.AssignmentInReturn

/**
 * @license GPL-2.0-or-later
 */
class Types {
	private ?ItemIdType $itemIdType = null;
	private ?PropertyIdType $propertyIdType = null;
	private ?LanguageCodeType $languageCodeType = null;
	private ?SiteIdType $siteIdType = null;
	private ?PropertyValuePairType $propertyValuePairType = null;
	private ?InterfaceType $labelProviderType = null;
	private ?StringValueType $stringValueType = null;
	private ?ObjectType $entityValueType = null;
	private ?ItemType $itemType = null;
	private ?ItemSearchFilterType $itemSearchFilterType = null;
	private ?ObjectType $itemSearchResultType = null;

	public function __construct(
		private readonly array $validLanguageCodes,
		private readonly SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		private readonly PropertyLabelsResolver $propertyLabelsResolver,
		private readonly DataTypeDefinitions $dataTypeDefinitions,
		private readonly ItemDescriptionsResolver $itemDescriptionsResolver,
		private readonly ItemLabelsResolver $itemLabelsResolver,

	) {
	}

	public function getItemIdType(): ItemIdType {
		return $this->itemIdType ??= new ItemIdType();
	}

	public function getPropertyIdType(): PropertyIdType {
		return $this->propertyIdType ??= new PropertyIdType();
	}

	public function getLanguageCodeType(): LanguageCodeType {
		return $this->languageCodeType ??= new LanguageCodeType( $this->validLanguageCodes );
	}

	public function getSiteIdType(): SiteIdType {
		return $this->siteIdType ??= new SiteIdType( $this->siteLinkGlobalIdentifiersProvider );
	}

	public function getPropertyValuePairType(): PropertyValuePairType {
		return $this->propertyValuePairType ??= new PropertyValuePairType(
			new PredicatePropertyType( $this->propertyLabelsResolver, $this->getLabelProviderType() ),
			new ValueType( $this->dataTypeDefinitions->getGraphqlValueTypes() ),
		);
	}

	public function getLabelProviderType(): InterfaceType {
		return $this->labelProviderType ??= new InterfaceType( [
			'name' => 'LabelProvider',
			'fields' => [
				'label' => [
					'type' => Type::string(),
					'args' => [
						'languageCode' => Type::nonNull( $this->getLanguageCodeType() ),
					],
				],
			],
		] );
	}

	public function getStringValueType(): StringValueType {
		return $this->stringValueType ??= new StringValueType();
	}

	public function getEntityValueType(): ObjectType {
		return $this->entityValueType ??= new ObjectType( [
			'name' => 'EntityValue',
			'fields' => [ 'id' => Type::nonNull( Type::string() ) ],
			'resolveField' => fn( Statement|PropertyValuePair $valueProvider, $args, $context, ResolveInfo $info ) => $valueProvider->value
				->getArrayValue()[ $info->fieldName ] ?? null,
		] );
	}

	public function getItemType(): ItemType {
		return $this->itemType ??= new ItemType( $this );
	}

	public function getItemSearchFilterType(): ItemSearchFilterType {
		return $this->itemSearchFilterType ??= new ItemSearchFilterType( $this );
	}

	public function getItemSearchResultType(): ObjectType {
		$labelProviderType = $this->getLabelProviderType();
		$labelField = clone $labelProviderType->getField( 'label' ); // cloned to not override the resolver in other places
		$labelField->resolveFn = fn( array $rootValue, array $args ) => $this->itemLabelsResolver
				->resolve( new ItemId( $rootValue['id'] ), $args[ 'languageCode' ] );

		return $this->itemSearchResultType ??= new ObjectType( [
			'name' => 'ItemSearchResult',
			'fields' => [
				'id' => Type::nonNull( $this->getItemIdType() ),
				$labelField,
				'description' => [
					'type' => Type::string(),
					'args' => [
						'languageCode' => Type::nonNull( $this->getLanguageCodeType() ),
					],
					'resolve' => fn( array $rootValue, array $args ) => $this->itemDescriptionsResolver
						->resolve( new ItemId( $rootValue['id'] ), $args['languageCode'] ),
				],
			],
		] );
	}
}
