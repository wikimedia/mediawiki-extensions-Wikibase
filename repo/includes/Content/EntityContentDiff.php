<?php

namespace Wikibase\Repo\Content;

use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Services\Diff\EntityDiff;

/**
 * Represents a diff between two Wikibase\EntityContent instances.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityContentDiff extends Diff {

	/**
	 * @var EntityDiff
	 */
	private $entityDiff;

	/**
	 * @var Diff
	 */
	private $redirectDiff;

	/**
	 * @param EntityDiff $entityDiff
	 * @param Diff $redirectDiff
	 */
	public function __construct( EntityDiff $entityDiff, Diff $redirectDiff ) {
		$operations = [];

		$this->entityDiff = $entityDiff;
		$this->redirectDiff = $redirectDiff;

		$operations = array_merge( $operations, $this->entityDiff->getOperations() );
		$operations = array_merge( $operations, $this->redirectDiff->getOperations() );

		parent::__construct( $operations, true );
	}

	/**
	 * @return EntityDiff
	 */
	public function getEntityDiff() {
		return $this->entityDiff;
	}

	/**
	 * @return Diff
	 */
	public function getRedirectDiff() {
		return $this->redirectDiff;
	}

}
