<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyResponse {

	private PropertyData $propertyData;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;

	private int $revisionId;

	public function __construct( PropertyData $propertyData, string $lastModified, int $revisionId ) {
		$this->propertyData = $propertyData;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getPropertyData(): PropertyData {
		return $this->propertyData;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}
}
