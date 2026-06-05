<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;
use Wikibase\Repo\Domains\Reuse\Domain\Services\LanguageFallbackLabelSelector;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemDescriptionsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsWithLanguageFallbackResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsWithLanguageFallbackResolver;
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
	private ?InterfaceType $descriptionProviderType = null;
	private ?InterfaceType $stringContentProviderType = null;
	private ?InterfaceType $urlProviderType = null;

	private ?StringValueType $stringValueType = null;
	private ?CommonsMediaValueType $commonsMediaValueType = null;
	private ?ExternalIdValueType $externalIdValueType = null;
	private ?GeoShapeValueType $geoShapeValueType = null;
	private ?TabularValueType $tabularValueType = null;
	private ?UrlValueType $urlValueType = null;
	private ?ObjectType $entityValueType = null;
	private ?ItemType $itemType = null;
	private ?ItemValueType $itemValueType = null;
	private ?ItemSearchFilterType $itemSearchFilterType = null;
	private ?ItemSearchConditionType $itemSearchConditionType = null;
	private ?ObjectType $itemSearchResultConnectionType = null;
	private ?ObjectType $itemSearchResultNodeType = null;
	private ?ObjectType $itemSearchResultEdgeType = null;
	private ?ObjectType $pageInfoType = null;
	private ?UnionType $itemByExternalIdResultType = null;
	private ?ObjectType $externalIdNonUniqueType = null;
	private ?PropertyValueType $propertyValueType = null;
	private ?ObjectType $labelWithLanguageType = null;

	public function __construct(
		private readonly array $validLanguageCodes,
		private readonly SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		private readonly PropertyLabelsResolver $propertyLabelsResolver,
		private readonly PropertyLabelsWithLanguageFallbackResolver $propertyLabelsWithFallbackResolver,
		private readonly DataTypeDefinitions $dataTypeDefinitions,
		private readonly ItemDescriptionsResolver $itemDescriptionsResolver,
		private readonly ItemLabelsResolver $itemLabelsResolver,
		private readonly ItemLabelsWithLanguageFallbackResolver $itemLabelsWithLanguageFallbackResolver,
		private readonly PropertyInfoLookup $propertyInfoLookup,
		private readonly SettingsArray $settings,
		private readonly LanguageFallbackLabelSelector $languageFallbackLabelSelector,
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
			new PredicatePropertyType(
				$this->propertyLabelsResolver,
				$this->propertyLabelsWithFallbackResolver,
				$this,
			),
			new ValueType( $this->dataTypeDefinitions->getGraphqlValueTypes() ),
		);
	}

	public function getPropertyValueType(): PropertyValueType {
		return $this->propertyValueType ??= new PropertyValueType(
			$this->propertyLabelsResolver,
			$this->propertyLabelsWithFallbackResolver,
			$this,
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
				'labelWithLanguageFallback' => [
					'type' => $this->getLabelWithLanguageType(),
					'args' => [
						'languageCode' => Type::nonNull( $this->getLanguageCodeType() ),
					],
				],
			],
		] );
	}

	public function getDescriptionProviderType(): InterfaceType {
		return $this->descriptionProviderType ??= new InterfaceType( [
			'name' => 'DescriptionProvider',
			'fields' => [
				'description' => [
					'type' => Type::string(),
					'args' => [
						'languageCode' => Type::nonNull( $this->getLanguageCodeType() ),
					],
				],
			],
		] );
	}

	public function getStringContentProviderType(): InterfaceType {
		return $this->stringContentProviderType ??= new InterfaceType( [
			'name' => 'StringContentProvider',
			'fields' => [
				'content' => [
					'type' => Type::nonNull( Type::string() ),
				],
			],
		] );
	}

	public function getUrlProviderType(): InterfaceType {
		return $this->urlProviderType ??= new InterfaceType( [
			'name' => 'UrlProvider',
			'fields' => [
				'url' => [
					'type' => Type::string(),
				],
			],
		] );
	}

	public function getStringValueType(): StringValueType {
		return $this->stringValueType ??= new StringValueType(
			$this
		);
	}

	public function getGeoShapeValueType(): GeoShapeValueType {
		return $this->geoShapeValueType ??= new GeoShapeValueType(
			$this->settings->getSetting( 'geoShapeStorageBaseUrl' ),
			$this
		);
	}

	public function getTabularValueType(): TabularValueType {
		return $this->tabularValueType ??= new TabularValueType(
			$this->settings->getSetting( 'tabularDataStorageBaseUrl' ),
			$this
		);
	}

	public function getExternalIdValueType(): ExternalIdValueType {
		return $this->externalIdValueType ??= new ExternalIdValueType(
			$this->propertyInfoLookup,
			$this
		);
	}

	public function getCommonsMediaValueType(): CommonsMediaValueType {
		return $this->commonsMediaValueType ??= new CommonsMediaValueType( $this );
	}

	public function getUrlValueType(): UrlValueType {
		return $this->urlValueType ??= new UrlValueType( $this );
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
		return $this->itemType ??= new ItemType( $this, $this->languageFallbackLabelSelector );
	}

	public function getItemValueType(): ItemValueType {
		return $this->itemValueType ??= new ItemValueType(
			$this->itemLabelsResolver,
			$this->itemLabelsWithLanguageFallbackResolver,
			$this->itemDescriptionsResolver,
			$this,
		);
	}

	public function getLabelWithLanguageType(): ObjectType {
		return $this->labelWithLanguageType ??= new ObjectType( [
			'name' => 'LabelWithLanguage',
			'fields' => [
				'languageCode' => [
					'type' => Type::nonNull( $this->getLanguageCodeType() ),
					'resolve' => fn( Label $label ) => $label->languageCode,
				],
				'value' => [
					'type' => Type::nonNull( Type::string() ),
					'resolve' => fn( Label $label ) => $label->text,
				],
			],
		] );
	}

	public function getItemSearchFilterType(): ItemSearchFilterType {
		return $this->itemSearchFilterType ??= new ItemSearchFilterType( $this );
	}

	public function getItemSearchConditionType(): ItemSearchConditionType {
		return $this->itemSearchConditionType ??= new ItemSearchConditionType( $this );
	}

	public function getItemSearchResultConnectionType(): ObjectType {
		return $this->itemSearchResultConnectionType ??= new ObjectType( [
			'name' => 'ItemSearchResultConnection',
			'description' => 'The result of a search query for items.',
			'fields' => [
				'edges' => [
					'type' => Type::nonNull( Type::listOf( Type::nonNull( $this->getItemSearchResultEdgeType() ) ) ),
					'description' => 'List of search result edges for the current page.',
				],
				'pageInfo' => [
					'type' => Type::nonNull( $this->getPageInfoType() ),
					'description' => 'Pagination information about the current page of result set.',
				],
			],
		] );
	}

	public function getPageInfoType(): ObjectType {
		return $this->pageInfoType ??= new ObjectType( [
			'name' => 'PageInfo',
			'description' => 'Pagination information about the current page of results.',
			'fields' => [
				'endCursor' => [
					'type' => Type::string(),
					'description' => 'Cursor of the last result in the current page.',
				],
				'hasPreviousPage' => [
					'type' => Type::nonNull( Type::boolean() ),
					'description' => 'Indicates whether there are more results available before the current page.',
				],
				'hasNextPage' => [
					'type' => Type::nonNull( Type::boolean() ),
					'description' => 'Indicates whether there are more results available after the current page.',
				],
				'startCursor' => [
					'type' => Type::string(),
					'description' => 'Cursor of the first result in the current page.',
				],
			],
		] );
	}

	public function getItemSearchResultEdgeType(): ObjectType {
		return $this->itemSearchResultEdgeType ??= new ObjectType( [
			'name' => 'ItemSearchResultEdge',
			'description' => 'An edge in the search result containing a matched item and its pagination cursor.',
			'fields' => [
				'node' => [
					'type' => Type::nonNull( $this->getItemType() ),
					'description' => 'The matched result item.',
				],
				'cursor' => [
					'type' => Type::nonNull( Type::string() ),
					'description' => 'Cursor identifying this result within the result set, used for pagination.',
				],
			],
		] );
	}

	public function getItemByExternalIdResultType(): UnionType {
		return $this->itemByExternalIdResultType ??= new UnionType( [
			'name' => 'ItemByExternalIdResult',
			'description' => 'The result of looking up an item by an external identifier.',
			'types' => [ $this->getItemType(), $this->getExternalIdNonUniqueType() ],
			'resolveType' => fn( $value ) => $value instanceof Item
				? $this->getItemType()
				: $this->getExternalIdNonUniqueType(),
		] );
	}

	public function getExternalIdNonUniqueType(): ObjectType {
		return $this->externalIdNonUniqueType ??= new ObjectType( [
			'name' => 'ExternalIdNonUnique',
			'description' => 'Indicates that multiple items match the given external identifier.',
			'fields' => [
				'items' => [
					'type' => Type::nonNull( Type::listOf( Type::nonNull( $this->getItemIdType() ) ) ),
					'description' => 'IDs of the items that match the given external identifier.',
					'resolve' => fn( array $result ) => array_map( fn( ItemId $itemId ) => $itemId->getSerialization(), $result ),
				],
			],
		] );
	}

	public static function copyFieldDefinition( FieldDefinition $definition, callable $resolveFn ): FieldDefinition {
		$newField = clone $definition; // cloned to not override the resolver in other places where the field is used
		$newField->resolveFn = $resolveFn;

		return $newField;
	}
}
