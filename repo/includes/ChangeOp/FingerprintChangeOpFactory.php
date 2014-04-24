<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;

/**
 * Factory for ChangeOps.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class FingerprintChangeOpFactory {

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newAddAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'add' );
	}

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'set' );
	}

	/**
	 * @param string $language
	 * @param string[] $aliases
	 *
	 * @return ChangeOp
	 */
	public function newRemoveAliasesOp( $language, array $aliases ) {
		return new ChangeOpAliases( $language, $aliases, 'remove' );
	}

	/**
	 * @param string $language
	 * @param string $description
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetDescriptionOp( $language, $description ) {
		return new ChangeOpDescription( $language, $description );
	}

	/**
	 * @param string $language
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveDescriptionOp( $language ) {
		return new ChangeOpDescription( $language, null );
	}

	/**
	 * @param string $language
	 * @param string $label
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetLabelOp( $language, $label ) {
		return new ChangeOpLabel( $language, $label );
	}

	/**
	 * @param string $language
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveLabelOp( $language ) {
		return new ChangeOpLabel( $language, null );
	}

}
