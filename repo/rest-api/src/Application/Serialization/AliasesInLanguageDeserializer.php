<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidAliasesInLanguageException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;

/**
 * @license GPL-2.0-or-later
 */
class AliasesInLanguageDeserializer {

	/**
	 * @throws InvalidFieldException
	 * @throws InvalidAliasesInLanguageException
	 */
	public function deserialize( array $serialization, string $basePath ): array {
		if ( !count( $serialization ) || !array_is_list( $serialization ) ) {
			throw new InvalidAliasesInLanguageException( $basePath, $serialization, $basePath );
		}

		$aliases = [];
		foreach ( $serialization as $index => $alias ) {
			if ( !is_string( $alias ) ) {
				throw new InvalidFieldException( (string)$index, $alias, "$basePath/$index" );
			}

			$alias = trim( $alias );
			if ( $alias === '' ) {
				throw new InvalidFieldException( (string)$index, $alias, "$basePath/$index" );
			}

			if ( !in_array( $alias, $aliases ) ) {
				$aliases[] = $alias;
			}
		}

		return $aliases;
	}

}
