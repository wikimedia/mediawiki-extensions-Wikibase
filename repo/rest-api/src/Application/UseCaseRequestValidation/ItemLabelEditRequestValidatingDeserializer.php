<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class ItemLabelEditRequestValidatingDeserializer {

	private ItemLabelValidator $validator;
	private ItemWriteModelRetriever $itemRetriever;

	public function __construct(
		ItemLabelValidator $validator,
		ItemWriteModelRetriever $itemRetriever
	) {
		$this->validator = $validator;
		$this->itemRetriever = $itemRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemLabelEditRequest $request ): Term {
		$item = $this->itemRetriever->getItemWriteModel( new ItemId( $request->getItemId() ) );
		$language = $request->getLanguageCode();
		$label = $request->getLabel();

		// skip if item does not exist or label is unchanged
		if ( !$item ||
			 ( $item->getLabels()->hasTermForLanguage( $language ) &&
			   $item->getLabels()->getByLanguage( $language )->getText() === $label
			 )
		) {
			return new Term( $language, $label );
		}

		$validationError = $this->validator->validate(
			$language,
			$label,
			$item->getDescriptions()
		);
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case ItemLabelValidator::CODE_INVALID:
					throw UseCaseError::newInvalidValue( '/label' );
				case ItemLabelValidator::CODE_EMPTY:
					throw UseCaseError::newInvalidValue( '/label' );
				case ItemLabelValidator::CODE_TOO_LONG:
					throw UseCaseError::newValueTooLong( '/label', $context[ItemLabelValidator::CONTEXT_LIMIT] );
				case ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION:
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
		return new Term( $language, $label );
	}

}
