<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PredicateProperty;

/**
 * @license GPL-2.0-or-later
 */
class PredicatePropertyType extends ObjectType {

	public function __construct() {
		$config = [
			'fields' => [
				'id' => [
					'type' => Type::nonNull( Type::string() ),
					'resolve' => fn( PredicateProperty $rootValue ) => $rootValue->id,
				],
				'dataType' => [
					'type' => Type::string(),
					'resolve' => fn( PredicateProperty $rootValue ) => $rootValue->dataType,
				],
			],
		];
		parent::__construct( $config );
	}

}
