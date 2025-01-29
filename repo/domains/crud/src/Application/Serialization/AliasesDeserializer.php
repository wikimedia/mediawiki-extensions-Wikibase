<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\InvalidFieldException;

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
	public function deserialize( array $serialization, string $basePath ): AliasGroupList {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			$parts = explode( '/', $basePath );
			throw new InvalidFieldException( $parts[array_key_last( $parts )], $serialization, $basePath );
		}

		$aliasGroups = [];
		foreach ( $serialization as $language => $aliasesInLanguage ) {
			// @phan-suppress-next-line PhanRedundantConditionInLoop
			if ( !is_array( $aliasesInLanguage ) ) {
				throw new InvalidFieldException( (string)$language, $aliasesInLanguage, "$basePath/$language" );
			}

			$aliases = $this->aliasesInLanguageDeserializer->deserialize( $aliasesInLanguage, "$basePath/$language" );
			$aliasGroups[] = new AliasGroup( $language, $aliases );
		}

		return new AliasGroupList( $aliasGroups );
	}

}
