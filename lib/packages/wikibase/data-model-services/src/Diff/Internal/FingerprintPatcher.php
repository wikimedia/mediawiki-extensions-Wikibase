<?php

namespace Wikibase\DataModel\Services\Diff\Internal;

use Diff\Patcher\PatcherException;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\TermListPatcher;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * Package private.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class FingerprintPatcher {

	/**
	 * @var TermListPatcher
	 */
	private $termListPatcher;

	/**
	 * @var AliasGroupListPatcher
	 */
	private $aliasGroupListPatcher;

	public function __construct() {
		$this->termListPatcher = new TermListPatcher();
		$this->aliasGroupListPatcher = new AliasGroupListPatcher();
	}

	/**
	 * @since 1.0
	 *
	 * @param Fingerprint $fingerprint
	 * @param EntityDiff $patch
	 *
	 * @throws PatcherException
	 */
	public function patchFingerprint( Fingerprint $fingerprint, EntityDiff $patch ) {
		$this->termListPatcher->patchTermList(
			$fingerprint->getLabels(),
			$patch->getLabelsDiff()
		);

		$this->termListPatcher->patchTermList(
			$fingerprint->getDescriptions(),
			$patch->getDescriptionsDiff()
		);

		$this->aliasGroupListPatcher->patchAliasGroupList(
			$fingerprint->getAliasGroups(),
			$patch->getAliasesDiff()
		);
	}

}
