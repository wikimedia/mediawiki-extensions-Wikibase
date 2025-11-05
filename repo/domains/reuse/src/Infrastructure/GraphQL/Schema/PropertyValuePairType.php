<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePairType extends ObjectType {

	public function __construct(
		PredicatePropertyType $predicateType,
		ValueType $valueType,
		ValueTypeType $valueTypeType
	) {
		parent::__construct( [
			'fields' => [
				'property' => [
					'type' => Type::nonNull( $predicateType ),
					'resolve' => fn( PropertyValuePair $rootValue ) => $rootValue->property,
				],
				'value' => [
					'type' => $valueType,
					// The whole PropertyValuePair is passed down here so that the Value type has access to the property data type.
					'resolve' => fn( PropertyValuePair $rootValue ) => $rootValue->value ? $rootValue : null,
				],
				'valueType' => [
					'type' => Type::nonNull( $valueTypeType ),
					'resolve' => fn( PropertyValuePair $rootValue ) => $rootValue->valueType,
				],
			],
		] );
	}

}
