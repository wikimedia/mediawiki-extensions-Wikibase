<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryItemDescriptionValidator implements ItemDescriptionValidator {

	private TermValidatorFactory $termValidatorFactory;
	private TermsCollisionDetector $termsCollisionDetector;
	private ItemRetriever $itemRetriever;

	public function __construct(
		TermValidatorFactory $termValidatorFactory,
		TermsCollisionDetector $termsCollisionDetector,
		ItemRetriever $itemRetriever
	) {
		$this->termValidatorFactory = $termValidatorFactory;
		$this->termsCollisionDetector = $termsCollisionDetector;
		$this->itemRetriever = $itemRetriever;
	}

	public function validate( ItemId $itemId, string $language, string $description ): ?ValidationError {
		return $this->validateDescription( $description )
			   ?? $this->validateItem( $itemId, $language, $description );
	}

	private function validateDescription( string $description ): ?ValidationError {
		$result = $this->termValidatorFactory
			->getDescriptionValidator()
			->validate( $description );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[0];
			switch ( $error->getCode() ) {
				case 'description-too-short':
					return new ValidationError( self::CODE_EMPTY );
				case 'description-too-long':
					return new ValidationError(
						self::CODE_TOO_LONG,
						[
							self::CONTEXT_DESCRIPTION => $description,
							self::CONTEXT_LIMIT => $error->getParameters()[0],
						]
					);
				default:
					return new ValidationError(
						self::CODE_INVALID,
						[ self::CONTEXT_DESCRIPTION => $description ]
					);
			}
		}

		return null;
	}

	private function validateItem( ItemId $itemId, string $language, string $description ): ?ValidationError {
		$item = $this->itemRetriever->getItem( $itemId );

		// skip if Item does not exist or is a redirect
		if ( $item === null ) {
			return null;
		}

		// skip if description is unchanged
		if ( $item->getDescriptions()->hasTermForLanguage( $language ) &&
			 $item->getDescriptions()->getByLanguage( $language )->getText() === $description
		) {
			return null;
		}

		// skip if Item does not have a label
		if ( !$item->getLabels()->hasTermForLanguage( $language ) ) {
			return null;
		}

		$label = $item->getLabels()->getByLanguage( $language )->getText();
		if ( $label === $description ) {
			return new ValidationError(
				self::CODE_LABEL_DESCRIPTION_EQUAL,
				[ self::CONTEXT_LANGUAGE => $language ]
			);
		}

		$entityId = $this->termsCollisionDetector
			->detectLabelAndDescriptionCollision( $language, $label, $description );
		if ( $entityId instanceof ItemId ) {
			return new ValidationError(
				self::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					self::CONTEXT_LANGUAGE => $language,
					self::CONTEXT_LABEL => $label,
					self::CONTEXT_DESCRIPTION => $description,
					self::CONTEXT_MATCHING_ITEM_ID => (string)$entityId,
				]
			);
		}

		return null;
	}

}
