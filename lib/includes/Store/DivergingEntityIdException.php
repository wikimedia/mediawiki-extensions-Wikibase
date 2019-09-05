<?php

namespace Wikibase\Lib\Store;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class DivergingEntityIdException extends BadRevisionException {
	/**
	 * @var EntityRevision
	 */
	private $entityRevision;

	public function __construct( EntityRevision $entityRevision, $status = "", $code = 0, Exception $previous = null ) {
		$this->entityRevision = $entityRevision;

		parent::__construct( $status, $code, $previous );
	}

	public function getEntityRevision(): EntityRevision {
		return $this->entityRevision;
	}

}
