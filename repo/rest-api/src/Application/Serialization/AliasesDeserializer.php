<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\DuplicateAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidAliasesInLanguageException;

/**
 * @license GPL-2.0-or-later
 */
class AliasesDeserializer {

	/**
	 * @throws InvalidAliasesInLanguageException
	 * @throws EmptyAliasException
	 * @throws DuplicateAliasException
	 */
	public function deserialize( array $serialization ): AliasGroupList {
		$aliasGroups = [];
		foreach ( $serialization as $language => $aliasesInLanguage ) {
			if ( !is_array( $aliasesInLanguage ) || !array_is_list( $aliasesInLanguage ) ) {
				throw new InvalidAliasesInLanguageException( $language, $aliasesInLanguage, $language );
			}

			$aliases = [];
			foreach ( $aliasesInLanguage as $index => $alias ) {
				if ( !is_string( $alias ) ) {
					throw new InvalidAliasesInLanguageException( $language, $alias, "$language/$index" );
				}

				$alias = trim( $alias );
				if ( $alias === '' ) {
					throw new EmptyAliasException( $language, $index );
				}

				if ( in_array( $alias, $aliases ) ) {
					throw new DuplicateAliasException( $language, $alias );
				}

				$aliases[] = $alias;
			}

			$aliasGroups[] = new AliasGroup( $language, $aliases );
		}

		return new AliasGroupList( $aliasGroups );
	}

}
