<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;

/**
 * @license GPL-2.0-or-later
 */
class ItemLabelEditRequestValidatingDeserializer {

	private ItemLabelValidator $validator;

	public function __construct( ItemLabelValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemLabelEditRequest $request ): Term {
		$validationError = $this->validator->validate(
			new ItemId( $request->getItemId() ),
			$request->getLanguageCode(),
			$request->getLabel()
		);
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case ItemLabelValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_LABEL,
						"Not a valid label: {$context[ItemLabelValidator::CONTEXT_LABEL]}"
					);
				case ItemLabelValidator::CODE_EMPTY:
					throw new UseCaseError( UseCaseError::LABEL_EMPTY, 'Label must not be empty' );
				case ItemLabelValidator::CODE_TOO_LONG:
					$maxLabelLength = $context[ItemLabelValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::LABEL_TOO_LONG,
						"Label must be no more than $maxLabelLength characters long",
						[
							UseCaseError::CONTEXT_VALUE => $context[ItemLabelValidator::CONTEXT_LABEL],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $maxLabelLength,
						]
					);
				case ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
					$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language code '$language' can not have the same value.",
						[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
					);
				case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
					$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
					$matchingItemId = $context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID];
					$label = $context[ItemLabelValidator::CONTEXT_LABEL];
					throw new UseCaseError(
						UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
						"Item $matchingItemId already has label '$label' associated with " .
						"language code '$language', using the same description text.",
						[
							UseCaseError::CONTEXT_LANGUAGE => $language,
							UseCaseError::CONTEXT_LABEL => $label,
							UseCaseError::CONTEXT_DESCRIPTION => $context[ItemLabelValidator::CONTEXT_DESCRIPTION],
							UseCaseError::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
						]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}
		return new Term( $request->getLanguageCode(), $request->getLabel() );
	}

}
