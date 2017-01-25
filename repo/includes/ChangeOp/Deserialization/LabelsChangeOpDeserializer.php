<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\StringNormalizer;

/**
 * Constructs ChangeOps for label change requests
 *
 * @license GPL-2.0+
 */
class LabelsChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $fingerprintChangeOpFactory;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var TermChangeOpSerializationValidator
	 */
	private $validator;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	public function __construct(
		FingerprintChangeOpFactory $fingerprintChangeOpFactory,
		StringNormalizer $stringNormalizer,
		TermChangeOpSerializationValidator $validator,
		ApiErrorReporter $errorReporter
	) {
		$this->fingerprintChangeOpFactory = $fingerprintChangeOpFactory;
		$this->stringNormalizer = $stringNormalizer;
		$this->validator = $validator;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$this->validateLabelsArray( $changeRequest['labels'] );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['labels'] as $langCode => $arg ) {
			$this->validator->validateMultilangArgs( $arg, $langCode );

			$language = $arg['language'];
			$newLabel = ( array_key_exists( 'remove', $arg ) ? '' :
				$this->stringNormalizer->trimToNFC( $arg['value'] ) );

			if ( $newLabel === '' ) {
				$changeOps->add( $this->fingerprintChangeOpFactory->newRemoveLabelOp( $language ) );
			} else {
				$changeOps->add( $this->fingerprintChangeOpFactory->newSetLabelOp( $language, $newLabel ) );
			}
		}

		return $changeOps;
	}

	private function validateLabelsArray( $labels ) {
		if ( !is_array( $labels ) ) {
			$this->errorReporter->dieError( 'List of labels must be an array', 'not-recognized-array' );
		}
	}

}
