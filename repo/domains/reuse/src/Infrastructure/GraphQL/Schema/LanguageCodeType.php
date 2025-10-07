<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

/**
 * @license GPL-2.0-or-later
 */
class LanguageCodeType extends ScalarType {

	public function __construct( private readonly array $validLanguageCodes ) {
		parent::__construct();
	}

	public function serialize( mixed $value ): string {
		if ( !$this->isValidLanguageCode( $value ) ) {
			throw new InvariantViolation( 'Could not serialize the following value as language code: ' . Utils::printSafe( $value ) );
		}

		return $value;
	}

	public function parseValue( mixed $value ): string {
		if ( !$this->isValidLanguageCode( $value ) ) {
			throw new Error( 'Cannot represent the following value as language code: ' . Utils::printSafeJson( $value ) );
		}

		return $value;
	}

	public function parseLiteral( Node $valueNode, ?array $variables = null ): string {
		if ( !$valueNode instanceof StringValueNode ) {
			throw new Error( 'Query error: Can only parse strings got: ' . $valueNode->kind, [ $valueNode ] );
		}

		if ( !$this->isValidLanguageCode( $valueNode->value ) ) {
			throw new Error( 'Not a valid language code: ' . Utils::printSafeJson( $valueNode->value ), [ $valueNode ] );
		}

		return $valueNode->value;
	}

	private function isValidLanguageCode( mixed $id ): bool {
		return in_array( $id, $this->validLanguageCodes );
	}
}
