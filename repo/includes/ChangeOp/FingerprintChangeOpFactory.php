<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * Factory for ChangeOps that apply to an entity Fingerprint.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class FingerprintChangeOpFactory {

	/**
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	public function __construct( TermValidatorFactory $termValidatorFactory ) {
		$this->termValidatorFactory = $termValidatorFactory;
	}

	public function newFingerprintChangeOp( ChangeOps $changeOps ): ChangeOp {
		return new ChangeOpFingerprint( $changeOps, $this->termValidatorFactory );
	}

	/**
	 * @param string $languageCode
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newAddAliasesOp( $languageCode, array $aliases ) {
		return new ChangeOpAliases( $languageCode, $aliases, 'add', $this->termValidatorFactory );
	}

	/**
	 * @param string $languageCode
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetAliasesOp( $languageCode, array $aliases ) {
		return new ChangeOpAliases( $languageCode, $aliases, 'set', $this->termValidatorFactory );
	}

	/**
	 * @param string $languageCode
	 * @param string[] $aliases
	 *
	 * @return ChangeOp
	 */
	public function newRemoveAliasesOp( $languageCode, array $aliases ) {
		return new ChangeOpAliases( $languageCode, $aliases, 'remove', $this->termValidatorFactory );
	}

	/**
	 * @param string $languageCode
	 * @param string $description
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetDescriptionOp( $languageCode, $description ) {
		return new ChangeOpDescription( $languageCode, $description, $this->termValidatorFactory );
	}

	/**
	 * @param string $languageCode
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveDescriptionOp( $languageCode ) {
		return new ChangeOpDescription( $languageCode, null, $this->termValidatorFactory );
	}

	/**
	 * @param string $languageCode
	 * @param string $label
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetLabelOp( $languageCode, $label ) {
		return new ChangeOpLabel( $languageCode, $label, $this->termValidatorFactory );
	}

	/**
	 * @param string $languageCode
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveLabelOp( $languageCode ) {
		return new ChangeOpLabel( $languageCode, null, $this->termValidatorFactory );
	}

}
