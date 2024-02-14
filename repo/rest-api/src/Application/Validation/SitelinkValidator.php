<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkValidator {

	public const CODE_TITLE_MISSING = 'title-missing';

	public const CODE_EMPTY_TITLE = 'empty-title';

	public const CODE_INVALID_TITLE = 'invalid-title';

	private string $invalidTitleRegex;

	public function __construct( string $invalidTitleRegex ) {
		$this->invalidTitleRegex = $invalidTitleRegex;
	}

	public function validate( array $sitelink ): ?ValidationError {
		if ( !array_key_exists( 'title', $sitelink ) ) {
			return new ValidationError( self::CODE_TITLE_MISSING );
		} elseif ( empty( $sitelink[ 'title' ] ) ) {
			return new ValidationError( ( self::CODE_EMPTY_TITLE ) );
		} elseif ( preg_match( $this->invalidTitleRegex, $sitelink[ 'title' ] ) === 1 ) {
			return new ValidationError( self::CODE_INVALID_TITLE );
		}

		return null;
	}
}
