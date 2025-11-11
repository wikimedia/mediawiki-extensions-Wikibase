<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StringValueType extends ObjectType {
	public function __construct() {
		parent::__construct( [
			'name' => 'StringValue',
			'fields' => [
				'content' => [
					'type' => Type::nonNull( Type::string() ),
					'resolve' => fn( Statement|PropertyValuePair $valueProvider ) => $valueProvider->value->getValue(),
				],
			],
		] );
	}
}
