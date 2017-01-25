<?php

namespace Wikibase\Repo\ChangeOpDeserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\StringNormalizer;

/**
 * Constructs ChangeOps for alias change requests
 *
 * @license GPL-2.0+
 */
class AliasesChangeOpDeserializer implements ChangeOpDeserializer {

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
		$this->validateAliasesArray( $changeRequest['aliases'] );
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

	private function validateAliasesArray( $aliases ) {
		if ( !is_array( $aliases ) ) {
			$this->errorReporter->dieError( 'List of aliases must be an array', 'not-recognized-array' );
		}
	}

}
