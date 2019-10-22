<?php

namespace Wikibase;

use Exception;
use Wikibase\Lib\Store\BadRevisionException;

/**
 * @license GPL-2.0-or-later
 */
class InconsistentRedirectException extends BadRevisionException {

	private $revisionId;
	private $slot;

	public function __construct( int $revisionId, string $slot, $status = '', $code = 0, Exception $previous = null ) {
		$this->revisionId = $revisionId;
		$this->slot = $slot;

		parent::__construct( $status, $code, $previous );
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

	public function getSlotRole(): string {
		return $this->slot;
	}

}
