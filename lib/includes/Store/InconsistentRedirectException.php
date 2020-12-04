<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class InconsistentRedirectException extends BadRevisionException {

	/** @var int */
	private $revisionId;
	/** @var string */
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
