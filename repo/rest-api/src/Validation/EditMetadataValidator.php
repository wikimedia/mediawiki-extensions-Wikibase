<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

/**
 * @license GPL-2.0-or-later
 */
class EditMetadataValidator {

	public const CODE_INVALID_TAG = 'invalid-edit-tag';
	public const CODE_COMMENT_TOO_LONG = 'comment-too-long';

	public const CONTEXT_COMMENT_MAX_LENGTH = 'comment-max-length';
	public const CONTEXT_TAG_VALUE = 'tag-value';

	private int $maxCommentLength;
	private array $allowedTags;

	/**
	 * @param string[] $allowedTags {@see \ChangeTags::listExplicitlyDefinedTags}
	 */
	public function __construct( int $maxCommentLength, array $allowedTags ) {
		$this->maxCommentLength = $maxCommentLength;
		$this->allowedTags = $allowedTags;
	}

	public function validateComment( ?string $comment ): ?ValidationError {
		if ( $comment !== null && strlen( $comment ) > $this->maxCommentLength ) {
			return new ValidationError(
				self::CODE_COMMENT_TOO_LONG,
				[ self::CONTEXT_COMMENT_MAX_LENGTH => (string)$this->maxCommentLength ]
			);
		}

		return null;
	}

	public function validateEditTags( array $tags ): ?ValidationError {
		foreach ( $tags as $tag ) {
			if ( !in_array( $tag, $this->allowedTags ) ) {
				return new ValidationError(
					self::CODE_INVALID_TAG,
					[ self::CONTEXT_TAG_VALUE => json_encode( $tag ) ]
				);
			}
		}

		return null;
	}

}
