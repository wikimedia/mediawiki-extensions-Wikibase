<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Validation;

use Wikibase\Repo\Domains\Crud\Domain\Services\TagsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class EditMetadataValidator {

	public const CODE_INVALID_TAG = 'edit-metadata-validator-code-invalid-edit-tag';
	public const CODE_COMMENT_TOO_LONG = 'edit-metadata-validator-code-comment-too-long';

	public const CONTEXT_COMMENT_MAX_LENGTH = 'edit-metadata-validator-context-comment-max-length';
	public const CONTEXT_TAG_VALUE = 'edit-metadata-validator-context-tag-value';

	public function __construct(
		private readonly int $maxCommentLength,
		private readonly TagsRetriever $tagsRetriever ) {
	}

	public function validateComment( ?string $comment ): ?ValidationError {
		if ( $comment !== null && strlen( $comment ) > $this->maxCommentLength ) {
			return new ValidationError(
				self::CODE_COMMENT_TOO_LONG,
				[ self::CONTEXT_COMMENT_MAX_LENGTH => $this->maxCommentLength ]
			);
		}

		return null;
	}

	public function validateEditTags( array $tags ): ?ValidationError {
		$allowedTags = $this->tagsRetriever->getAllowedTags();
		foreach ( $tags as $tag ) {
			if ( !in_array( $tag, $allowedTags ) ) {
				return new ValidationError(
					self::CODE_INVALID_TAG,
					[ self::CONTEXT_TAG_VALUE => $tag ]
				);
			}
		}

		return null;
	}

}
