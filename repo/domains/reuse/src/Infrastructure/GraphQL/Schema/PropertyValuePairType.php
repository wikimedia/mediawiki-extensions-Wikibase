<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ValueType as ValueTypeModel;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePairType extends InterfaceType {

	public function __construct(
		PredicatePropertyType $predicateType,
		ValueType $valueType,
	) {
		parent::__construct( [
			'fields' => [
				'property' => [
					'type' => Type::nonNull( $predicateType ),
					'resolve' => fn( PropertyValuePair|Statement $rootValue ) => $rootValue->property,
				],
				'value' => [
					'type' => $valueType,
					// The whole root value is passed down here so that the Value type has access to the property data type.
					'resolve' => fn( PropertyValuePair|Statement $rootValue ) => $rootValue->value ? $rootValue : null,
				],
				'valueType' => [
					'type' => Type::nonNull( new EnumType( [
						'name' => 'ValueType',
						'values' => [ 'VALUE', 'SOME_VALUE', 'NO_VALUE' ],
					] ) ),
					'resolve' => fn( PropertyValuePair|Statement $rootValue ) => match ( $rootValue->valueType ) {
						ValueTypeModel::TYPE_VALUE => 'VALUE',
						ValueTypeModel::TYPE_NO_VALUE => 'NO_VALUE',
						ValueTypeModel::TYPE_SOME_VALUE => 'SOME_VALUE',
					},
				],
			],
		] );
	}

}
