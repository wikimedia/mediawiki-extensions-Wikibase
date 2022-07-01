<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

/**
 * @license GPL-2.0-or-later
 */
class EditMetadataValidator {

	private $maxCommentLength;
	private $allowedTags;

	/**
	 * @param string[] $allowedTags {@see \ChangeTags::listExplicitlyDefinedTags}
	 */
	public function __construct( int $maxCommentLength, array $allowedTags ) {
		$this->maxCommentLength = $maxCommentLength;
		$this->allowedTags = $allowedTags;
	}

	public function validateComment( ?string $comment, string $source ): ?ValidationError {
		if ( $comment !== null && strlen( $comment ) > $this->maxCommentLength ) {
			return new ValidationError( (string)$this->maxCommentLength, $source );
		}

		return null;
	}

	public function validateEditTags( array $tags, string $source ): ?ValidationError {
		foreach ( $tags as $tag ) {
			if ( !in_array( $tag, $this->allowedTags ) ) {
				return new ValidationError( json_encode( $tag ), $source );
			}
		}

		return null;
	}

}
