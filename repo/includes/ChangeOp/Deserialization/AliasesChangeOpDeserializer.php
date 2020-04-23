<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;

/**
 * Constructs ChangeOps for alias change requests
 *
 * @license GPL-2.0-or-later
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
		$aliasesChangeOps = $this->buildIndexedAliasesChangeOps( $indexedAliases );

		return $aliasesChangeOps;
	}

	/**
	 * @param array[] $aliasGroups
	 *
	 * @return array[]
	 */
	private function getIndexedAliases( array $aliasGroups ) {
		$indexedAliases = [];

		foreach ( $aliasGroups as $languageCode => $aliases ) {
			$this->assertIsArray( $aliases );

			if ( array_values( $aliases ) !== $aliases ) {
				$aliases = [ $aliases ];
			}

			if ( is_string( $languageCode ) ) {
				$indexedAliases[$languageCode] = $aliases;
			} else {
				$indexedAliases[] = $aliases;
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
	private function buildIndexedAliasesChangeOps( array $indexedAliases ) {
		$aliasesChangeOps = new ChangeOps();

		foreach ( $indexedAliases as $langCode => $serializations ) {
			$aliasesToSet = [];
			$language = '';

			if ( empty( $serializations ) ) {
				$aliasesChangeOps->add( $this->fingerprintChangeOpFactory->newSetAliasesOp( $langCode, [] ) );
				continue;
			}

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
