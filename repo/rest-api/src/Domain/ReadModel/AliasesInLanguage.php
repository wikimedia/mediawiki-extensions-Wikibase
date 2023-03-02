<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 */
class AliasesInLanguage {

	private string $languageCode;
	private array $aliases;

	/**
	 * @param string[] $aliases
	 */
	public function __construct( string $languageCode, array $aliases ) {
		foreach ( $aliases as $alias ) {
			if ( !is_string( $alias ) ) {
				throw new InvalidArgumentException( "{$alias} must be a string!" );
			}
		}

		$this->languageCode = $languageCode;
		$this->aliases = $aliases;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	/**
	 * @return string[]
	 */
	public function getAliases(): array {
		return $this->aliases;
	}

}
