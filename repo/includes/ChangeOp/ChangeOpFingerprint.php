<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * Decorator on ChangeOps for collecting and distinguishing a collection
 * of ChangeOp instances on entity fingerprint parts.
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFingerprint extends ChangeOps {

	/** @var ChangeOps */
	private $innerChangeOps;

	/** @var TermValidatorFactory */
	private $termValidatorFactory;

	public function __construct( ChangeOps $innerChangeOps, TermValidatorFactory $termValidatorFactory ) {
		$this->innerChangeOps = $innerChangeOps;
		$this->termValidatorFactory = $termValidatorFactory;
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
		return new ChangeOpFingerprintResult( $result, $this->termValidatorFactory );
	}
}
