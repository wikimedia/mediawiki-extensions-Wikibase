<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityRedirect;

/**
 * Represents a revision of a Wikibase redirect.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RedirectRevision {

	/**
	 * @var EntityRedirect
	 */
	private $redirect;

	/**
	 * @var int
	 */
	private $revisionId;

	/**
	 * @var string
	 */
	private $mwTimestamp;

	/**
	 * @param EntityRedirect $redirect
	 * @param int $revisionId Revision ID or 0 for none
	 * @param string $mwTimestamp in MediaWiki format or an empty string for none
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityRedirect $redirect,
		int $revisionId = 0,
		string $mwTimestamp = ''
	) {
		if ( $revisionId < 0 ) {
			throw new InvalidArgumentException( 'Revision ID must be a non-negative integer.' );
		}

		if ( $mwTimestamp !== '' && !preg_match( '/^\d{14}\z/', $mwTimestamp ) ) {
			throw new InvalidArgumentException( 'Timestamp must be a string of 14 digits or empty.' );
		}

		$this->redirect = $redirect;
		$this->revisionId = $revisionId;
		$this->mwTimestamp = $mwTimestamp;
	}

	/**
	 * @return EntityRedirect
	 */
	public function getRedirect() {
		return $this->redirect;
	}

	/**
	 * @see RevisionRecord::getId
	 *
	 * @return int
	 */
	public function getRevisionId() {
		return $this->revisionId;
	}

	/**
	 * @see RevisionRecord::getTimestamp
	 *
	 * @return string in MediaWiki format or an empty string
	 */
	public function getTimestamp() {
		return $this->mwTimestamp;
	}

}
