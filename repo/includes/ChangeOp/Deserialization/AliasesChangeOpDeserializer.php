<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
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
		$this->assertIsArray( $changeRequest['aliases'] );
		return $this->getAliasesChangeOps( $changeRequest['aliases'] );
	}

	/**
	 * @param array[] $aliases
	 *
	 * @return ChangeOps
	 *
	 * @throws ChangeOpDeserializationException
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
		$indexedAliases = [];

		foreach ( $aliases as $langCode => $serialization ) {
			if ( !is_string( $langCode ) ) {
				$indexedAliases[] = ( array_values( $serialization ) === $serialization ) ? $serialization : [ $serialization ];
			} else {
				$indexedAliases[$langCode] = ( array_values( $serialization ) === $serialization ) ? $serialization : [ $serialization ];
			}
		}

		return $indexedAliases;
	}

	/**
	 * @param array[] $indexedAliases
	 *
	 * @return ChangeOps
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function getIndexedAliasesChangeOps( array $indexedAliases ) {
		$aliasesChangeOps = new ChangeOps();

		foreach ( $indexedAliases as $langCode => $serializations ) {
			$aliasesToSet = [];
			$language = '';

			foreach ( $serializations as $serialization ) {
				$this->validator->validateTermSerialization( $serialization, $langCode );

				$alias = [ $this->stringNormalizer->trimToNFC( $serialization['value'] ) ];
				$language = $serialization['language'];

				if ( array_key_exists( 'remove', $serialization ) ) {
					$aliasesChangeOps->add( $this->fingerprintChangeOpFactory->newRemoveAliasesOp( $language, $alias ) );
				} elseif ( array_key_exists( 'add', $serialization ) ) {
					$aliasesChangeOps->add( $this->fingerprintChangeOpFactory->newAddAliasesOp( $language, $alias ) );
				} else {
					$aliasesToSet[] = $alias[0];
				}
			}

			if ( $aliasesToSet !== [] ) {
				$aliasesChangeOps->add( $this->fingerprintChangeOpFactory->newSetAliasesOp( $language, $aliasesToSet ) );
			}
		}

		return $aliasesChangeOps;
	}

	/**
	 * @param array[] $aliases
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function assertIsArray( $aliases ) {
		if ( !is_array( $aliases ) ) {
			throw new ChangeOpDeserializationException( 'List of aliases must be an array', 'not-recognized-array' );
		}
	}

}
