<?php

namespace Wikibase\DataModel\Services\Diff\Internal;

use Diff\DiffOp\AtomicDiffOp;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Diff\Patcher\MapPatcher;
use Diff\Patcher\PatcherException;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class FingerprintPatcher {

	/**
	 * @param Fingerprint $fingerprint
	 * @param EntityDiff $patch
	 *
	 * @throws PatcherException
	 */
	public function patchFingerprint( Fingerprint $fingerprint, EntityDiff $patch ) {
		$this->patchTermList( $fingerprint->getLabels(), $patch->getLabelsDiff() );
		$this->patchTermList( $fingerprint->getDescriptions(), $patch->getDescriptionsDiff() );

		$this->patchAliasGroupList( $fingerprint->getAliasGroups(), $patch->getAliasesDiff() );
	}

	/**
	 * @param TermList $terms
	 * @param Diff $patch
	 *
	 * @throws PatcherException
	 */
	private function patchTermList( TermList $terms, Diff $patch ) {
		foreach ( $patch as $lang => $diffOp ) {
			$this->patchTerm( $terms, $lang, $diffOp );
		}
	}

	/**
	 * @see MapPatcher
	 *
	 * @param TermList $terms
	 * @param string $lang
	 * @param AtomicDiffOp $diffOp
	 *
	 * @throws PatcherException
	 */
	private function patchTerm( TermList $terms, $lang, AtomicDiffOp $diffOp ) {
		$hasLang = $terms->hasTermForLanguage( $lang );

		switch ( true ) {
			case $diffOp instanceof DiffOpAdd:
				/** @var DiffOpAdd $diffOp */
				if ( !$hasLang ) {
					$terms->setTextForLanguage( $lang, $diffOp->getNewValue() );
				}
				break;

			case $diffOp instanceof DiffOpChange:
				/** @var DiffOpChange $diffOp */
				if ( $hasLang
					&& $terms->getByLanguage( $lang )->getText() === $diffOp->getOldValue()
				) {
					$terms->setTextForLanguage( $lang, $diffOp->getNewValue() );
				}
				break;

			case $diffOp instanceof DiffOpRemove:
				/** @var DiffOpRemove $diffOp */
				if ( $hasLang
					&& $terms->getByLanguage( $lang )->getText() === $diffOp->getOldValue()
				) {
					$terms->removeByLanguage( $lang );
				}
				break;

			default:
				throw new PatcherException( 'Invalid terms diff' );
		}
	}

	/**
	 * @param AliasGroupList $groups
	 * @param Diff $patch
	 *
	 * @throws PatcherException
	 */
	private function patchAliasGroupList( AliasGroupList $groups, Diff $patch ) {
		foreach ( $patch as $lang => $diffOp ) {
			$this->patchAliasGroup( $groups, $lang, $diffOp );
		}
	}

	/**
	 * @see MapPatcher
	 *
	 * @param AliasGroupList $groups
	 * @param string $lang
	 * @param DiffOp $diffOp
	 *
	 * @throws PatcherException
	 */
	private function patchAliasGroup( AliasGroupList $groups, $lang, DiffOp $diffOp ) {
		$hasLang = $groups->hasGroupForLanguage( $lang );

		switch ( true ) {
			case $diffOp instanceof DiffOpAdd:
				/** @var DiffOpAdd $diffOp */
				if ( !$hasLang ) {
					$groups->setAliasesForLanguage( $lang, $diffOp->getNewValue() );
				}
				break;

			case $diffOp instanceof DiffOpChange:
				/** @var DiffOpChange $diffOp */
				$this->applyAliasGroupChange( $groups, $lang, $diffOp );
				break;

			case $diffOp instanceof DiffOpRemove:
				/** @var DiffOpRemove $diffOp */
				if ( $hasLang
					&& $groups->getByLanguage( $lang )->getAliases() === $diffOp->getOldValue()
				) {
					$groups->removeByLanguage( $lang );
				}
				break;

			case $diffOp instanceof Diff:
				$this->applyAliasGroupDiff( $groups, $lang, $diffOp );
				break;

			default:
				throw new PatcherException( 'Invalid aliases diff' );
		}
	}

	/**
	 * @param AliasGroupList $groups
	 * @param string $lang
	 * @param DiffOpChange $patch
	 */
	private function applyAliasGroupChange( AliasGroupList $groups, $lang, DiffOpChange $patch ) {
		if ( $groups->hasGroupForLanguage( $lang )
			&& $groups->getByLanguage( $lang )->getAliases() === $patch->getOldValue()
		) {
			$groups->setAliasesForLanguage( $lang, $patch->getNewValue() );
		}
	}

	/**
	 * @param AliasGroupList $groups
	 * @param string $lang
	 * @param Diff $patch
	 */
	private function applyAliasGroupDiff( AliasGroupList $groups, $lang, Diff $patch ) {
		$hasLang = $groups->hasGroupForLanguage( $lang );

		if ( $hasLang || !$this->containsOperationsOnOldValues( $patch ) ) {
			$aliases = $hasLang ? $groups->getByLanguage( $lang )->getAliases() : array();
			$aliases = $this->getPatchedAliases( $aliases, $patch );
			$groups->setAliasesForLanguage( $lang, $aliases );
		}
	}

	/**
	 * @param Diff $diff
	 *
	 * @return bool
	 */
	private function containsOperationsOnOldValues( Diff $diff ) {
		return $diff->getChanges() !== array()
			|| $diff->getRemovals() !== array();
	}

	/**
	 * @see ListPatcher
	 *
	 * @param string[] $aliases
	 * @param Diff $patch
	 *
	 * @throws PatcherException
	 * @return string[]
	 */
	private function getPatchedAliases( array $aliases, Diff $patch ) {
		/** @var DiffOp $diffOp */
		foreach ( $patch as $diffOp ) {
			switch ( true ) {
				case $diffOp instanceof DiffOpAdd:
					/** @var DiffOpAdd $diffOp */
					$aliases[] = $diffOp->getNewValue();
					break;

				case $diffOp instanceof DiffOpChange:
					/** @var DiffOpChange $diffOp */
					$key = array_search( $diffOp->getOldValue(), $aliases, true );

					if ( $key !== false ) {
						unset( $aliases[$key] );
						$aliases[] = $diffOp->getNewValue();
					}
					break;

				case $diffOp instanceof DiffOpRemove:
					/** @var DiffOpRemove $diffOp */
					$key = array_search( $diffOp->getOldValue(), $aliases, true );

					if ( $key !== false ) {
						unset( $aliases[$key] );
					}
					break;

				default:
					throw new PatcherException( 'Invalid aliases diff' );
			}
		}

		return $aliases;
	}

}
