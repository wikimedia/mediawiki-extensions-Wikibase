<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\Tests\Validators\TermChangeOpValidator;
use Wikibase\StringNormalizer;
use Wikimedia\Assert\Assert;

class DescriptionChangeOpDeserializer implements ChangeOpDeserializer {

	private $fingerprintChangeOpFactory;

	private $stringNormalizer;

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

	public function createEntityChangeOp( array $changeRequest ) {
		Assert::parameterType( 'array', $changeRequest['descriptions'], '$changeRequest[\'descriptions\']' );

		$changeOps = new ChangeOps();

		foreach ( $changeRequest['descriptions'] as $langCode => $arg ) {
			$this->validator->validateMultilangArgs( $arg, $langCode );

			$language = $arg['language'];
			$newDescription = ( array_key_exists( 'remove', $arg ) ? '' :
				$this->stringNormalizer->trimToNFC( $arg['value'] ) );

			if ( $newDescription === "" ) {
				$changeOps->add( $this->fingerprintChangeOpFactory->newRemoveDescriptionOp( $language ) );
			} else {
				$changeOps->add( $this->fingerprintChangeOpFactory->newSetDescriptionOp( $language, $newDescription ) );
			}
		}

		return $changeOps;
	}

}
