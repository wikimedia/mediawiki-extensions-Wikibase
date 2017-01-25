<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\Validators\TermChangeOpValidator;
use Wikibase\StringNormalizer;

/**
 * Constructs ChangeOps for description change requests
 *
 * @license GPL-2.0+
 */
class DescriptionsChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $fingerprintChangeOpFactory;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var TermChangeOpValidator
	 */
	private $validator;

	public function __construct(
		ApiErrorReporter $errorReporter,
		FingerprintChangeOpFactory $fingerprintChangeOpFactory,
		StringNormalizer $stringNormalizer,
		TermChangeOpValidator $validator
	) {
		$this->errorReporter = $errorReporter;
		$this->fingerprintChangeOpFactory = $fingerprintChangeOpFactory;
		$this->stringNormalizer = $stringNormalizer;
		$this->validator = $validator;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$this->validateDescriptionList( $changeRequest['descriptions'] );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['descriptions'] as $langCode => $arg ) {
			$this->validator->validateMultilangArgs( $arg, $langCode );

			$language = $arg['language'];
			$newDescription = ( array_key_exists( 'remove', $arg ) ? '' :
				$this->stringNormalizer->trimToNFC( $arg['value'] ) );

			if ( $newDescription === '' ) {
				$changeOps->add( $this->fingerprintChangeOpFactory->newRemoveDescriptionOp( $language ) );
			} else {
				$changeOps->add( $this->fingerprintChangeOpFactory->newSetDescriptionOp( $language, $newDescription ) );
			}
		}

		return $changeOps;
	}

	private function validateDescriptionList( $descriptions ) {
		if ( !is_array( $descriptions ) ) {
			$this->errorReporter->dieError( 'List of descriptions must be an array', 'not-recognized-array' );
		}
	}

}
