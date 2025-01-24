<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class PropertyRevision {

	private Property $property;
	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;
	private int $revisionId;

	public function __construct( Property $property, string $lastModified, int $revisionId ) {
		$this->property = $property;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getProperty(): Property {
		return $this->property;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}
}
