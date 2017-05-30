<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
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

	public function __construct(
		FingerprintChangeOpFactory $fingerprintChangeOpFactory,
		StringNormalizer $stringNormalizer,
		TermChangeOpSerializationValidator $validator
	) {
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
	 *
	 * @throws ChangeOpDeserializationException
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$this->assertIsArray( $changeRequest['labels'] );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['labels'] as $langCode => $serialization ) {
			$this->validator->validateTermSerialization( $serialization, $langCode );

			$language = $serialization['language'];
			$newLabel = ( array_key_exists( 'remove', $serialization ) ? '' :
				$this->stringNormalizer->trimToNFC( $serialization['value'] ) );

			if ( $newLabel === '' ) {
				$changeOps->add( $this->fingerprintChangeOpFactory->newRemoveLabelOp( $language ) );
			} else {
				$changeOps->add( $this->fingerprintChangeOpFactory->newSetLabelOp( $language, $newLabel ) );
			}
		}

		return $changeOps;
	}

	/**
	 * @param array $labels
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function assertIsArray( $labels ) {
		if ( !is_array( $labels ) ) {
			throw new ChangeOpDeserializationException( 'List of labels must be an array', 'not-recognized-array' );
		}
	}

}
