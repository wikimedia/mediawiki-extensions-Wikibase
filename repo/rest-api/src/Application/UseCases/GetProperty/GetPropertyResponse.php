<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyResponse {

	private PropertyParts $propertyParts;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;

	private int $revisionId;

	public function __construct( PropertyParts $propertyParts, string $lastModified, int $revisionId ) {
		$this->propertyParts = $propertyParts;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getPropertyParts(): PropertyParts {
		return $this->propertyParts;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}
}
