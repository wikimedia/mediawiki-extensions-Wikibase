<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePairType extends ObjectType {

	// Include the value during property value implementation.
	public function __construct( PredicatePropertyType $predicateType ) {
		$config = [
			'fields' => [
				'property' => [
					'type' => Type::nonNull( $predicateType ),
					'resolve' => fn( PropertyValuePair $rootValue ) => $rootValue->property,
				],
			],
		];
		parent::__construct( $config );
	}

}
