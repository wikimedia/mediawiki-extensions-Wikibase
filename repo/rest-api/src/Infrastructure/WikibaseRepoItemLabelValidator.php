<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\Store\TermsCollisionDetector;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoItemLabelValidator implements ItemLabelValidator {

	private TermValidatorFactoryLabelTextValidator $labelTextValidator;
	private TermsCollisionDetector $termsCollisionDetector;
	private ItemRetriever $itemRetriever;

	public function __construct(
		TermValidatorFactoryLabelTextValidator $labelTextValidator,
		TermsCollisionDetector $termsCollisionDetector,
		ItemRetriever $itemRetriever
	) {
		$this->labelTextValidator = $labelTextValidator;
		$this->termsCollisionDetector = $termsCollisionDetector;
		$this->itemRetriever = $itemRetriever;
	}

	public function validate( ItemId $itemId, string $language, string $label ): ?ValidationError {
		return $this->labelTextValidator->validate( $label )
			   ?? $this->validateItem( $itemId, $language, $label );
	}

	private function validateItem( ItemId $itemId, string $language, string $label ): ?ValidationError {
		$item = $this->itemRetriever->getItem( $itemId );

		// skip if Item does not exist or is a redirect
		if ( $item === null ) {
			return null;
		}

		// skip if label is unchanged
		if ( $item->getLabels()->hasTermForLanguage( $language ) &&
			 $item->getLabels()->getByLanguage( $language )->getText() === $label
		) {
			return null;
		}

		// skip if Item does not have a description
		if ( !$item->getDescriptions()->hasTermForLanguage( $language ) ) {
			return null;
		}

		$description = $item->getDescriptions()->getByLanguage( $language )->getText();
		if ( $label === $description ) {
			return new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => $language ],
			);
		}

		$entityId = $this->termsCollisionDetector
			->detectLabelAndDescriptionCollision( $language, $label, $description );
		if ( $entityId instanceof ItemId ) {
			return new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemLabelValidator::CONTEXT_LANGUAGE => $language,
					ItemLabelValidator::CONTEXT_LABEL => $label,
					ItemLabelValidator::CONTEXT_DESCRIPTION => $description,
					ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => (string)$entityId,
				]
			);
		}

		return null;
	}
}
