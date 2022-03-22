<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSuccessResult {

	/**
	 * @var array
	 */
	private $itemSerialization;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private $lastModified;

	/**
	 * @var int
	 */
	private $revisionId;

	public function __construct( array $itemSerialization, string $lastModified, int $revisionId ) {
		$this->itemSerialization = $itemSerialization;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getItem(): array {
		return $this->itemSerialization;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}
}
