<?php

namespace Wikibase\DataModel\Services\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
		$labels = $this->patcher->patch(
			$fingerprint->getLabels()->toTextArray(),
			$patch->getLabelsDiff()
		);

		$fingerprint->setLabels( $this->newTermListFromArray( $labels ) );

		$descriptions = $this->patcher->patch(
			$fingerprint->getDescriptions()->toTextArray(),
			$patch->getDescriptionsDiff()
		);

		$fingerprint->setDescriptions( $this->newTermListFromArray( $descriptions ) );

		$this->patchAliases( $fingerprint, $patch->getAliasesDiff() );
	}

	private function newTermListFromArray( $termArray ) {
		$termList = new TermList();

		foreach ( $termArray as $language => $labelText ) {
			$termList->setTextForLanguage( $language, $labelText );
		}

		return $termList;
	}

	private function patchAliases( Fingerprint $fingerprint, Diff $aliasesDiff ) {
		$patchedAliases = $this->patcher->patch(
			$this->getAliasesArrayForPatching( $fingerprint->getAliasGroups() ),
			$aliasesDiff
		);

		$fingerprint->setAliasGroups( $this->getAliasesFromArrayForPatching( $patchedAliases ) );
	}

	private function getAliasesArrayForPatching( AliasGroupList $aliases ) {
		$textLists = array();

		/**
		 * @var AliasGroup $aliasGroup
		 */
		foreach ( $aliases as $languageCode => $aliasGroup ) {
			$textLists[$languageCode] = $aliasGroup->getAliases();
		}

		return $textLists;
	}

	private function getAliasesFromArrayForPatching( array $patchedAliases ) {
		$aliases = new AliasGroupList();

		foreach( $patchedAliases as $languageCode => $aliasList ) {
			$aliases->setAliasesForLanguage( $languageCode, $aliasList );
		}

		return $aliases;
	}

}
