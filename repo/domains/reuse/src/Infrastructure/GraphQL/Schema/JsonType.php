<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;

/**
 * @license GPL-2.0-or-later
 */
class JsonType extends ScalarType {
	public function serialize( mixed $value ): mixed {
		return $value;
	}

	public function parseValue( mixed $value ): mixed {
		return $value;
	}

	public function parseLiteral( Node $valueNode, ?array $variables = null ): mixed {
		return $valueNode->jsonSerialize();
	}
}
