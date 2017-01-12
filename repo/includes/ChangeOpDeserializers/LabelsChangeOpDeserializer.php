<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\StringNormalizer;
use Wikimedia\Assert\Assert;

class LabelsChangeOpDeserializer implements ChangeOpDeserializer {

	private $fingerprintChangeOpFactory;

	private $stringNormalizer;

	public function __construct( FingerprintChangeOpFactory $fingerprintChangeOpFactory, StringNormalizer $stringNormalizer ) {
		$this->fingerprintChangeOpFactory = $fingerprintChangeOpFactory;
		$this->stringNormalizer = $stringNormalizer;
	}

	public function createEntityChangeOp( array $changeRequest ) {
		Assert::parameterType( 'array', $changeRequest['labels'], '$changeRequest[\'labels\']' );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['labels'] as $langCode => $arg ) {
			$this->validateMultilangArgs( $arg, $langCode );

			$language = $arg['language'];
			$newLabel = ( array_key_exists( 'remove', $arg ) ? '' :
				$this->stringNormalizer->trimToNFC( $arg['value'] ) );

			if ( $newLabel === "" ) {
				$changeOps->add( $this->fingerprintChangeOpFactory->newRemoveLabelOp( $language ) );
			} else {
				$changeOps->add( $this->fingerprintChangeOpFactory->newSetLabelOp( $language, $newLabel ) );
			}
		}

		return $changeOps;
	}

	private function validateMultilangArgs( $arg, $langCode ) {
		// FIXME: extract from EditEntity into its own class for reuse
	}

}
