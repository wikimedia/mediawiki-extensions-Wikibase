<?php

namespace Wikibase\Repo\SeaHorse;


use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Entity\EntityDocument;

class SeaHorseDiffer implements \Wikibase\DataModel\Services\Diff\EntityDifferStrategy {

	public function canDiffEntityType( $entityType ) {
		return $entityType === SeaHorseSaddle::ENTITY_TYPE;
	}

	/**
	 * @param EntityDocument $from
	 * @param EntityDocument $to
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function diffEntities( EntityDocument $from, EntityDocument $to ) {
		$dops = [];

		if ($from->isEmpty() && !$to->isEmpty()) {
			$dops['content'] = new DiffOpAdd( $to->getContent() );
		} elseif (!$from->isEmpty() && $to->isEmpty()) {
			$dops['content'] = new DiffOpRemove( $from->getContent() );
		} elseif (!$from->isEmpty() && !$to->isEmpty()) {
			$dops['content'] = new DiffOpChange( $from->getContent(), $to->getContent() );
		}

		return new SeaHorseDiff($dops);
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getConstructionDiff( EntityDocument $entity ) {
		return new SeaHorseDiff(['content' => new DiffOpAdd( $entity->getContent() ) ] );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDiff
	 * @throws InvalidArgumentException
	 */
	public function getDestructionDiff( EntityDocument $entity ) {

		return new SeaHorseDiff(['content' => new DiffOpRemov( $entity->getContent() ) ] );
	}

}
