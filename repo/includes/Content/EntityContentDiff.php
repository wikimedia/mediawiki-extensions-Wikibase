<?php

namespace Wikibase\Repo\Content;

use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;

/**
 * Represents a diff between two Wikibase\EntityContent instances.
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
	 * @var EntityDocument
	 */
	private $entity;

	/**
	 * @param EntityDiff $entityDiff
	 * @param Diff $redirectDiff
	 */
	public function __construct(
		EntityDiff $entityDiff,
		Diff $redirectDiff,
		EntityDocument $entity
	) {
		$operations = array();

		$this->entityDiff = $entityDiff;
		$this->redirectDiff = $redirectDiff;
		$this->entity = $entity;

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

	/**
	 * @return EntityDocument
	 */
	public function getEntity() {
		return $this->entity;
	}

}
