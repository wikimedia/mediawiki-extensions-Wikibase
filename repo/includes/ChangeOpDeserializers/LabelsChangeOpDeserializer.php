<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\Validators\TermChangeOpValidator;
use Wikibase\StringNormalizer;
use Wikimedia\Assert\Assert;

/**
 * TODO: add class description
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
	 * @var TermChangeOpValidator
	 */
	private $validator;

	public function __construct(
		FingerprintChangeOpFactory $fingerprintChangeOpFactory,
		StringNormalizer $stringNormalizer,
		TermChangeOpValidator $validator
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
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		Assert::parameterType( 'array', $changeRequest['labels'], '$changeRequest[\'labels\']' );

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

}
