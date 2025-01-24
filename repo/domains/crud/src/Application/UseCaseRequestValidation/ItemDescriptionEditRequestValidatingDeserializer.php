<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class ItemDescriptionEditRequestValidatingDeserializer {

	private ItemDescriptionValidator $validator;
	private ItemWriteModelRetriever $itemRetriever;

	public function __construct(
		ItemDescriptionValidator $validator,
		ItemWriteModelRetriever $itemRetriever
	) {
		$this->validator = $validator;
		$this->itemRetriever = $itemRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemDescriptionEditRequest $request ): Term {
		$item = $this->itemRetriever->getItemWriteModel( new ItemId( $request->getItemId() ) );
		$language = $request->getLanguageCode();
		$description = $request->getDescription();

		// skip if item does not exist or description is unchanged
		if ( !$item ||
			 ( $item->getDescriptions()->hasTermForLanguage( $language ) &&
			   $item->getDescriptions()->getByLanguage( $language )->getText() === $description
			 )
		) {
			return new Term( $language, $description );
		}

		$validationError = $this->validator->validate(
			$language,
			$description,
			$item->getLabels()
		);

		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case ItemDescriptionValidator::CODE_INVALID:
				case ItemDescriptionValidator::CODE_EMPTY:
					throw UseCaseError::newInvalidValue( '/description' );
				case ItemDescriptionValidator::CODE_TOO_LONG:
					throw UseCaseError::newValueTooLong( '/description', $context[ItemDescriptionValidator::CONTEXT_LIMIT] );
				case ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL:
					throw UseCaseError::newDataPolicyViolation(
						UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
						[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
					);
				case ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE:
					throw UseCaseError::newDataPolicyViolation(
						UseCaseError::POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE,
						[
							UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE],
							UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $context[ItemDescriptionValidator::CONTEXT_CONFLICTING_ITEM_ID],
						]
					);
				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
			}
		}

		return new Term( $language, $request->getDescription() );
	}

}
