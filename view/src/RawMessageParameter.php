<?php
declare( strict_types=1 );

namespace Wikibase\View;

/**
 * Value object to be used with {@see LocalizedTextProvider} for raw (not to be escaped) message
 * parameters containing HTML markup
 *
 * @license GPL-2.0-or-later
 */
class RawMessageParameter {

	/** @var string */
	private $contents;

	public function __construct( string $contents ) {
		$this->contents = $contents;
	}

	public function getContents(): string {
		return $this->contents;
	}

}
