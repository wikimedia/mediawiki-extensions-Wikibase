<?php

namespace Wikibase\Repo\Content;

use Diff\DiffOp\Diff\Diff;
use Wikibase\DataModel\Services\Diff\EntityDiff;

/**
 * Represents a diff between two Wikibase\Repo\Content\EntityContent instances.
 *
 * @license GPL-2.0-or-later
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
	 * @var string
	 */
	private $entityType;

	/**
	 * @param EntityDiff $entityDiff
	 * @param Diff $redirectDiff
	 * @param string $entityType
	 */
	public function __construct(
		EntityDiff $entityDiff,
		Diff $redirectDiff,
		$entityType
	) {
		$operations = [];

		$this->entityDiff = $entityDiff;
		$this->redirectDiff = $redirectDiff;
		$this->entityType = $entityType;

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
	 * @return string
	 */
	public function getEntityType() {
		return $this->entityType;
	}

}
