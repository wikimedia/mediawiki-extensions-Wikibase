<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemIdType extends ScalarType {
	public function serialize( mixed $value ): string {
		if ( !$this->isValidItemId( $value ) ) {
			throw new InvariantViolation( 'Could not serialize the following value as Item ID: ' . Utils::printSafe( $value ) );
		}

		return $value;
	}

	public function parseValue( mixed $value ): string {
		if ( !$this->isValidItemId( $value ) ) {
			throw new Error( 'Cannot represent the following value as Item ID: ' . Utils::printSafeJson( $value ) );
		}

		return $value;
	}

	public function parseLiteral( Node $valueNode, ?array $variables = null ): string {
		if ( !$valueNode instanceof StringValueNode ) {
			throw new Error( 'Query error: Can only parse strings got: ' . $valueNode->kind, [ $valueNode ] );
		}

		if ( !$this->isValidItemId( $valueNode->value ) ) {
			throw new Error( 'Not a valid Item ID: ' . Utils::printSafeJson( $valueNode->value ), [ $valueNode ] );
		}

		return $valueNode->value;
	}

	private function isValidItemId( mixed $id ): bool {
		if ( !is_string( $id ) ) {
			return false;
		}

		try {
			new ItemId( $id ); // @phan-suppress-current-line PhanNoopNew
			return true;
		} catch ( InvalidArgumentException ) {
			return false;
		}
	}
}
