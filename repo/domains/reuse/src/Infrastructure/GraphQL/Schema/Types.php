<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;
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

	public function __construct(
		private readonly array $validLanguageCodes,
		private readonly SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		private readonly PropertyLabelsResolver $propertyLabelsResolver,
		private readonly DataTypeDefinitions $dataTypeDefinitions,
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
			new ValueTypeType(),
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
}
