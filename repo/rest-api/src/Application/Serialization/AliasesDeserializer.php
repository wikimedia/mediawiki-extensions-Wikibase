<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidAliasesInLanguageException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;

/**
 * @license GPL-2.0-or-later
 */
class AliasesDeserializer {

	/**
	 * @throws InvalidFieldException
	 * @throws InvalidAliasesInLanguageException
	 * @throws EmptyAliasException
	 */
	public function deserialize( array $serialization ): AliasGroupList {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			throw new InvalidFieldException( '', $serialization, '' );
		}

		$aliasGroups = [];
		foreach ( $serialization as $language => $aliasesInLanguage ) {
			// @phan-suppress-next-line PhanRedundantConditionInLoop
			if ( !is_array( $aliasesInLanguage ) || !array_is_list( $aliasesInLanguage ) ) {
				throw new InvalidAliasesInLanguageException( (string)$language, $aliasesInLanguage, (string)$language );
			}
			if ( count( $aliasesInLanguage ) === 0 ) {
				throw new InvalidAliasesInLanguageException( (string)$language, $aliasesInLanguage, (string)$language );
			}

			$aliases = [];
			foreach ( $aliasesInLanguage as $index => $alias ) {
				if ( !is_string( $alias ) ) {
					throw new InvalidFieldException( (string)$index, $alias, "$language/$index" );
				}

				$alias = trim( $alias );
				if ( $alias === '' ) {
					throw new EmptyAliasException( (string)$language, $index );
				}

				if ( !in_array( $alias, $aliases ) ) {
					$aliases[] = $alias;
				}
			}

			$aliasGroups[] = new AliasGroup( $language, $aliases );
		}

		return new AliasGroupList( $aliasGroups );
	}

}
