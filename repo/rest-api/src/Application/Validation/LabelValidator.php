<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

/**
 * @license GPL-2.0-or-later
 */
class LabelValidator {

	public const LABEL_EMPTY = 'label-empty';
	public const LABEL_TOO_LONG = 'label-too-long';
	public const CONTEXT_VALUE = 'value';
	public const CONTEXT_LIMIT = 'character-limit';
	private int $maxLabelLength;

	public function __construct( int $maxLabelLength ) {
		$this->maxLabelLength = $maxLabelLength;
	}

	public function validate( string $label ): ?ValidationError {
		if ( $label === '' ) {
			return new ValidationError( self::LABEL_EMPTY );
		}

		if ( strlen( $label ) > $this->maxLabelLength ) {
			return new ValidationError(
				self::LABEL_TOO_LONG,
				[
					self::CONTEXT_VALUE => $label,
					self::CONTEXT_LIMIT => $this->maxLabelLength,
				]
			);
		}

		return null;
	}
}
