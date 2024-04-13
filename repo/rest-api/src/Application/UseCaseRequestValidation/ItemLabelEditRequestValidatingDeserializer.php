<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\OldItemLabelValidator;

/**
 * @license GPL-2.0-or-later
 */
class ItemLabelEditRequestValidatingDeserializer {

	private OldItemLabelValidator $validator;

	public function __construct( OldItemLabelValidator $validator ) {
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
				case OldItemLabelValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_LABEL,
						"Not a valid label: {$context[OldItemLabelValidator::CONTEXT_LABEL]}"
					);
				case OldItemLabelValidator::CODE_EMPTY:
					throw new UseCaseError( UseCaseError::LABEL_EMPTY, 'Label must not be empty' );
				case OldItemLabelValidator::CODE_TOO_LONG:
					$maxLabelLength = $context[OldItemLabelValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::LABEL_TOO_LONG,
						"Label must be no more than $maxLabelLength characters long",
						[
							UseCaseError::CONTEXT_VALUE => $context[OldItemLabelValidator::CONTEXT_LABEL],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $maxLabelLength,
						]
					);
				case OldItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
					$language = $context[OldItemLabelValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language code '$language' can not have the same value.",
						[ UseCaseError::CONTEXT_LANGUAGE => $context[OldItemLabelValidator::CONTEXT_LANGUAGE] ]
					);
				case OldItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
					$language = $context[OldItemLabelValidator::CONTEXT_LANGUAGE];
					$matchingItemId = $context[OldItemLabelValidator::CONTEXT_MATCHING_ITEM_ID];
					$label = $context[OldItemLabelValidator::CONTEXT_LABEL];
					throw new UseCaseError(
						UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
						"Item $matchingItemId already has label '$label' associated with " .
						"language code '$language', using the same description text.",
						[
							UseCaseError::CONTEXT_LANGUAGE => $language,
							UseCaseError::CONTEXT_LABEL => $label,
							UseCaseError::CONTEXT_DESCRIPTION => $context[OldItemLabelValidator::CONTEXT_DESCRIPTION],
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
