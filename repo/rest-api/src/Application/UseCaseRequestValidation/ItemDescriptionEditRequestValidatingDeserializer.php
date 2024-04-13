<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\OldItemDescriptionValidator;

/**
 * @license GPL-2.0-or-later
 */
class ItemDescriptionEditRequestValidatingDeserializer {

	private OldItemDescriptionValidator $validator;

	public function __construct( OldItemDescriptionValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemDescriptionEditRequest $request ): Term {
		$language = $request->getLanguageCode();
		$validationError = $this->validator->validate(
			new ItemId( $request->getItemId() ),
			$language,
			$request->getDescription()
		);

		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case OldItemDescriptionValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_DESCRIPTION,
						"Not a valid description: {$context[OldItemDescriptionValidator::CONTEXT_DESCRIPTION]}"
					);
				case OldItemDescriptionValidator::CODE_EMPTY:
					throw new UseCaseError(
						UseCaseError::DESCRIPTION_EMPTY,
						'Description must not be empty'
					);
				case OldItemDescriptionValidator::CODE_TOO_LONG:
					$limit = $context[OldItemDescriptionValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::DESCRIPTION_TOO_LONG,
						"Description must be no more than $limit characters long",
						[
							UseCaseError::CONTEXT_VALUE => $context[OldItemDescriptionValidator::CONTEXT_DESCRIPTION],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
						]
					);
				case OldItemDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language code '$language' can not have the same value",
						[ UseCaseError::CONTEXT_LANGUAGE => $context[OldItemDescriptionValidator::CONTEXT_LANGUAGE] ]
					);
				case OldItemDescriptionValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
					$matchingItemId = $context[OldItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID];
					$label = $context[OldItemDescriptionValidator::CONTEXT_LABEL];
					throw new UseCaseError(
						UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
						"Item '$matchingItemId' already has label '$label' associated with "
						. "language code '$language', using the same description text",
						[
							UseCaseError::CONTEXT_LANGUAGE => $context[OldItemDescriptionValidator::CONTEXT_LANGUAGE],
							UseCaseError::CONTEXT_LABEL => $label,
							UseCaseError::CONTEXT_DESCRIPTION => $context[OldItemDescriptionValidator::CONTEXT_DESCRIPTION],
							UseCaseError::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
						]
					);
				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
			}
		}

		return new Term( $language, $request->getDescription() );
	}

}
