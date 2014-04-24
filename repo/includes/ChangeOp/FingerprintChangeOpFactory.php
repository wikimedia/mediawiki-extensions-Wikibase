<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\Validators\TermValidatorFactory;

/**
 * Factory for ChangeOps that apply to an entity Fingerprint.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class FingerprintChangeOpFactory {

	/**
	 * @var TermValidatorFactory
	 */
	private $termValidatorFactory;

	/**
	 * @param TermValidatorFactory $termValidatorFactory
	 */
	public function __construct( TermValidatorFactory $termValidatorFactory ) {
		$this->termValidatorFactory = $termValidatorFactory;
	}

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newAddAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'add', $this->termValidatorFactory );
	}

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'set', $this->termValidatorFactory );
	}

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @return ChangeOp
	 */
	public function newRemoveAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'remove', $this->termValidatorFactory );
	}

	/**
	 * @param string $language
	 * @param string $description
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetDescriptionOp( $language, $description ) {
		return new ChangeOpDescription( $language, $description, $this->termValidatorFactory );
	}

	/**
	 * @param string $language
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveDescriptionOp( $language ) {
		return new ChangeOpDescription( $language, null, $this->termValidatorFactory );
	}

	/**
	 * @param string $language
	 * @param string $label
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetLabelOp( $language, $label ) {
		return new ChangeOpLabel( $language, $label, $this->termValidatorFactory );
	}

	/**
	 * @param string $language
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveLabelOp( $language ) {
		return new ChangeOpLabel( $language, null, $this->termValidatorFactory );
	}

}
