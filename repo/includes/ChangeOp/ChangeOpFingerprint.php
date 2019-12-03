<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Summary;

/**
 * Decorator on ChangeOps for collecting and distinguishing a collection
 * of ChangeOp instances on entity fingerprint parts.
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFingerprint extends ChangeOps {

	/** @var ChangeOps */
	private $innerChangeOps;

	public function __construct( ChangeOps $innerChangeOps ) {
		$this->innerChangeOps = $innerChangeOps;
	}

	public function add( $changeOps ) {
		$this->innerChangeOps->add( $changeOps );
	}

	public function getChangeOps() {
		return $this->innerChangeOps->getChangeOps();
	}

	public function validate( EntityDocument $entity ) {
		return $this->innerChangeOps->validate( $entity );
	}

	public function getActions() {
		return $this->innerChangeOps->getActions();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		$result = $this->innerChangeOps->apply( $entity, $summary );

		'@phan-var ChangeOpsResult $result';
		return new ChangeOpFingerprintResult( $result );
	}
}
