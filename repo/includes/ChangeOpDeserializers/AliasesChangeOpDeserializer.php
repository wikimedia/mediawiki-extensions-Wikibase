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

class AliasesChangeOpDeserializer implements ChangeOpDeserializer {

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
		Assert::parameterType( 'array', $changeRequest['aliases'], '$changeRequest[\'aliases\']' );
		return $this->getAliasesChangeOps( $changeRequest['aliases'] );
	}

	/**
	 * @param array[] $aliases
	 *
	 * @return ChangeOps
	 */
	private function getAliasesChangeOps( array $aliases ) {
		$indexedAliases = $this->getIndexedAliases( $aliases );
		$aliasesChangeOps = $this->getIndexedAliasesChangeOps( $indexedAliases );

		return $aliasesChangeOps;
	}

	/**
	 * @param array[] $aliases
	 *
	 * @return array[]
	 */
	private function getIndexedAliases( array $aliases ) {
		$indexedAliases = array();

		foreach ( $aliases as $langCode => $arg ) {
			if ( !is_string( $langCode ) ) {
				$indexedAliases[] = ( array_values( $arg ) === $arg ) ? $arg : array( $arg );
			} else {
				$indexedAliases[$langCode] = ( array_values( $arg ) === $arg ) ? $arg : array( $arg );
			}
		}

		return $indexedAliases;
	}

	/**
	 * @param array[] $indexedAliases
	 *
	 * @return ChangeOps
	 */
	private function getIndexedAliasesChangeOps( array $indexedAliases ) {
		$aliasesChangeOps = new ChangeOps();

		foreach ( $indexedAliases as $langCode => $args ) {
			$aliasesToSet = array();
			$language = '';

			foreach ( $args as $arg ) {
				$this->validator->validateMultilangArgs( $arg, $langCode );

				$alias = array( $this->stringNormalizer->trimToNFC( $arg['value'] ) );
				$language = $arg['language'];

				if ( array_key_exists( 'remove', $arg ) ) {
					$aliasesChangeOps->add( $this->fingerprintChangeOpFactory->newRemoveAliasesOp( $language, $alias ) );
				} elseif ( array_key_exists( 'add', $arg ) ) {
					$aliasesChangeOps->add( $this->fingerprintChangeOpFactory->newAddAliasesOp( $language, $alias ) );
				} else {
					$aliasesToSet[] = $alias[0];
				}
			}

			if ( $aliasesToSet !== array() ) {
				$aliasesChangeOps->add( $this->fingerprintChangeOpFactory->newSetAliasesOp( $language, $aliasesToSet ) );
			}
		}

		return $aliasesChangeOps;
	}

}
