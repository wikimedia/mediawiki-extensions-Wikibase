<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @license GPL-2.0-or-later
 */
class AliasesDeserializer {

	public function deserialize( array $serialization ): AliasGroupList {
		$aliasGroups = [];

		foreach ( $serialization as $language => $aliasesInLanguage ) {
			if ( !is_array( $aliasesInLanguage ) ) {
				throw new InvalidFieldException( $language, $aliasesInLanguage );
			}

			$aliases = [];
			foreach ( $aliasesInLanguage as $alias ) {
				if ( !is_string( $alias ) ) {
					throw new InvalidFieldException( $language, $alias );
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
