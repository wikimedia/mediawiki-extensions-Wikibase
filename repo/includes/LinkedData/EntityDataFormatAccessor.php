<?php

namespace Wikibase\Repo\LinkedData;

/**
 * Interface for providing information about supported EntityData formats (as used
 * by Special:EntityData).
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
interface EntityDataFormatAccessor {

	/**
	 * @param array|null $whitelist List of allowed formats or null
	 *
	 * @return array Associative array from MIME type to format name
	 */
	public function getMimeTypes( array $whitelist = null );

	/**
	 * @param array|null $whitelist List of allowed formats or null
	 *
	 * @return array Associative array from file extension to format name
	 */
	public function getFileExtensions( array $whitelist = null );
}
