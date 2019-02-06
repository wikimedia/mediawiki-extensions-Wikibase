<?php

namespace Wikibase\DataAccess;

/**
 * A dummy EntitySource only used to fulfill constructor requirements.
 *
 * Using any method of this class will result in LogicException.
 *
 * @license GPL-2.0-or-later
 */
class UnusableEntitySource extends EntitySource {

	public function __construct() {
	}

	public function getDatabaseName() {
		throw new \LogicException( __CLASS__ . ' should never be used' );
	}

	public function getSourceName() {
		throw new \LogicException( __CLASS__ . ' should never be used' );
	}

	public function getEntityTypes() {
		throw new \LogicException( __CLASS__ . ' should never be used' );
	}

	public function getEntityNamespaceIds() {
		throw new \LogicException( __CLASS__ . ' should never be used' );
	}

	public function getEntitySlotNames() {
		throw new \LogicException( __CLASS__ . ' should never be used' );
	}

	public function getConceptBaseUri() {
		throw new \LogicException( __CLASS__ . ' should never be used' );
	}

	public function getInterwikiPrefix() {
		throw new \LogicException( __CLASS__ . ' should never be used' );
	}

}
