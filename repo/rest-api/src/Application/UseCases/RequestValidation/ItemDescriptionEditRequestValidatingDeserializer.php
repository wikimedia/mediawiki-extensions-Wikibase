<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\ItemDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;

/**
 * @license GPL-2.0-or-later
 */
class ItemDescriptionEditRequestValidatingDeserializer {

	private ItemDescriptionValidator $validator;

	public function __construct( ItemDescriptionValidator $validator ) {
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
				case ItemDescriptionValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_DESCRIPTION,
						"Not a valid description: {$context[ItemDescriptionValidator::CONTEXT_VALUE]}"
					);
				case ItemDescriptionValidator::CODE_EMPTY:
					throw new UseCaseError(
						UseCaseError::DESCRIPTION_EMPTY,
						'Description must not be empty'
					);
				case ItemDescriptionValidator::CODE_TOO_LONG:
					$limit = $context[ItemDescriptionValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::DESCRIPTION_TOO_LONG,
						"Description must be no more than $limit characters long",
						[
							UseCaseError::CONTEXT_VALUE => $context[ItemDescriptionValidator::CONTEXT_VALUE],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
						]
					);
				case ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language code '$language' can not have the same value",
						[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
					);
				case ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
					$matchingItemId = $context[ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID];
					$label = $context[ItemDescriptionValidator::CONTEXT_LABEL];
					throw new UseCaseError(
						UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
						"Item '$matchingItemId' already has label '$label' associated with "
						. "language code '$language', using the same description text",
						[
							UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE],
							UseCaseError::CONTEXT_LABEL => $label,
							UseCaseError::CONTEXT_DESCRIPTION => $context[ItemDescriptionValidator::CONTEXT_DESCRIPTION],
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
