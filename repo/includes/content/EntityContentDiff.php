<?php

namespace Wikibase\DataModel\Entity;

use Diff\DiffOp\Diff\Diff;

/**
 * Represents a diff between two Wikibase\EntityContent instances.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
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
	 * Constructor.
	 *
	 * @param EntityDiff $entityDiff
	 * @param Diff $redirectDiff
	 */
	public function __construct( EntityDiff $entityDiff = null, Diff $redirectDiff = null ) {
		$operations = array();

		$this->entityDiff = $entityDiff ? $entityDiff : new EntityDiff();
		$this->redirectDiff = $redirectDiff ? $redirectDiff : new Diff();

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
