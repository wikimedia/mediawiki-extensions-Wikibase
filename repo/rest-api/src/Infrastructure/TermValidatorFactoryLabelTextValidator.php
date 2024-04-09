<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Application\Validation\OldItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryLabelTextValidator {

	private TermValidatorFactory $termValidatorFactory;

	public function __construct( TermValidatorFactory $termValidatorFactory ) {
		$this->termValidatorFactory = $termValidatorFactory;
	}

	public function validate( string $labelText, string $language ): ?ValidationError {
		$result = $this->termValidatorFactory
			->getLabelValidator( Item::ENTITY_TYPE )
			->validate( $labelText );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[0];
			switch ( $error->getCode() ) {
				case 'label-too-short':
					return new ValidationError(
						OldItemLabelValidator::CODE_EMPTY,
						[ OldItemLabelValidator::CONTEXT_LANGUAGE => $language ]
					);
				case 'label-too-long':
					return new ValidationError(
						OldItemLabelValidator::CODE_TOO_LONG,
						[
							OldItemLabelValidator::CONTEXT_LABEL => $labelText,
							OldItemLabelValidator::CONTEXT_LANGUAGE => $language,
							OldItemLabelValidator::CONTEXT_LIMIT => $error->getParameters()[0],
						]
					);
				default:
					return new ValidationError(
						OldItemLabelValidator::CODE_INVALID,
						[ OldItemLabelValidator::CONTEXT_LABEL => $labelText, OldItemLabelValidator::CONTEXT_LANGUAGE => $language ]
					);
			}
		}

		return null;
	}

}
