<?php

namespace Wikibase;

/**
 * Interface for value objects that hold basic revision info.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface RevisionInfo {

	/**
	 * @see Revision::getId
	 *
	 * @return int
	 */
	public function getRevisionId();

	/**
	 * @see Revision::getTimestamp
	 *
	 * @return string in MediaWiki format or an empty string
	 */
	public function getTimestamp();

}
