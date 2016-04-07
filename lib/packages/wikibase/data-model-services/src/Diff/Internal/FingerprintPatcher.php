<?php

namespace Wikibase\DataModel\Services\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
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
	 * @var MapPatcher
	 */
	private $patcher;

	public function __construct() {
		$this->patcher = new MapPatcher();
	}

	/**
	 * @param Fingerprint $fingerprint
	 * @param EntityDiff $patch
	 *
	 * @throws InvalidArgumentException
	 */
	public function patchFingerprint( Fingerprint $fingerprint, EntityDiff $patch ) {
		$this->patchTermList( $fingerprint->getLabels(), $patch->getLabelsDiff() );
		$this->patchTermList( $fingerprint->getDescriptions(), $patch->getDescriptionsDiff() );

		$this->patchAliasGroupList( $fingerprint->getAliasGroups(), $patch->getAliasesDiff() );
	}

	private function patchTermList( TermList $terms, Diff $patch ) {
		$original = $terms->toTextArray();
		$patched = $this->patcher->patch( $original, $patch );

		foreach ( $patched as $languageCode => $text ) {
			$terms->setTextForLanguage( $languageCode, $text );
		}

		foreach ( array_diff_key( $original, $patched ) as $languageCode => $text ) {
			$terms->removeByLanguage( $languageCode );
		}
	}

	private function patchAliasGroupList( AliasGroupList $groups, Diff $patch ) {
		$original = $groups->toTextArray();
		$patched = $this->patcher->patch( $original, $patch );

		foreach ( $patched as $languageCode => $aliases ) {
			$groups->setAliasesForLanguage( $languageCode, $aliases );
		}

		foreach ( array_diff_key( $original, $patched ) as $languageCode => $aliases ) {
			$groups->removeByLanguage( $languageCode );
		}
	}

}
