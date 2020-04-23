<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;

/**
 * Constructs ChangeOps for description change requests
 *
 * @license GPL-2.0-or-later
 */
class DescriptionsChangeOpDeserializer implements ChangeOpDeserializer {

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
		$this->assertIsArray( $changeRequest['descriptions'] );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['descriptions'] as $langCode => $serialization ) {
			'@phan-var array $serialization';
			$this->validator->validateTermSerialization( $serialization, $langCode );

			$language = $serialization['language'];
			$newDescription = ( array_key_exists( 'remove', $serialization ) ? '' :
				$this->stringNormalizer->trimToNFC( $serialization['value'] ) );

			if ( $newDescription === '' ) {
				$changeOps->add( $this->fingerprintChangeOpFactory->newRemoveDescriptionOp( $language ) );
			} else {
				$changeOps->add( $this->fingerprintChangeOpFactory->newSetDescriptionOp( $language, $newDescription ) );
			}
		}

		return $changeOps;
	}

	/**
	 * @param array[] $descriptions
	 *
	 * @throws ChangeOpDeserializationException
	 * @phan-assert array $descriptions
	 */
	private function assertIsArray( $descriptions ) {
		if ( !is_array( $descriptions ) ) {
			throw new ChangeOpDeserializationException( 'List of descriptions must be an array', 'not-recognized-array' );
		}
	}

}
