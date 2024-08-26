<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;

/**
 * @license GPL-2.0-or-later
 */
class AliasesDeserializer {

	private AliasesInLanguageDeserializer $aliasesInLanguageDeserializer;

	public function __construct( AliasesInLanguageDeserializer $aliasesInLanguageDeserializer ) {
		$this->aliasesInLanguageDeserializer = $aliasesInLanguageDeserializer;
	}

	/**
	 * @throws InvalidFieldException
	 */
	public function deserialize( array $serialization ): AliasGroupList {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			throw new InvalidFieldException( '', $serialization, '' );
		}

		$aliasGroups = [];
		foreach ( $serialization as $language => $aliasesInLanguage ) {
			// @phan-suppress-next-line PhanRedundantConditionInLoop
			if ( !is_array( $aliasesInLanguage ) ) {
				throw new InvalidFieldException( (string)$language, $aliasesInLanguage, (string)$language );
			}

			$aliases = $this->aliasesInLanguageDeserializer->deserialize( $aliasesInLanguage, (string)$language );
			$aliasGroups[] = new AliasGroup( $language, $aliases );
		}

		return new AliasGroupList( $aliasGroups );
	}

}
