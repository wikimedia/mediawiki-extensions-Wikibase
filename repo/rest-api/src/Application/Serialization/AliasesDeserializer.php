<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @license GPL-2.0-or-later
 */
class AliasesDeserializer {

	/**
	 * @throws InvalidFieldException
	 * @throws EmptyAliasException
	 * @throws DuplicateAliasException
	 */
	public function deserialize( array $serialization ): AliasGroupList {
		$aliasGroups = [];

		foreach ( $serialization as $language => $aliasesInLanguage ) {
			if ( !is_array( $aliasesInLanguage ) || !array_is_list( $aliasesInLanguage ) ) {
				throw new InvalidFieldException( $language, $aliasesInLanguage, $language );
			}

			$aliases = [];
			foreach ( $aliasesInLanguage as $index => $alias ) {

				if ( !is_string( $alias ) ) {
					$path = $language . '/' . $index;
					throw new InvalidFieldException( $language, $alias, $path );
				}

				$alias = trim( $alias );
				if ( $alias === '' ) {
					throw new EmptyAliasException( $language, '' );
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
