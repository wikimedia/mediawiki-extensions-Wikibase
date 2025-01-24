<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\ReadModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class Sitelink {

	private string $siteId;
	private string $title;
	private array $badges;
	private string $url;

	/**
	 * @param string $siteId
	 * @param string $title
	 * @param ItemId[] $badges
	 * @param string $url
	 */
	public function __construct( string $siteId, string $title, array $badges, string $url ) {
		foreach ( $badges as $badge ) {
			if ( !$badge instanceof ItemId ) {
				throw new InvalidArgumentException( '$badges must be of type ItemId[]' );
			}
		}

		$this->siteId = $siteId;
		$this->title = $title;
		$this->badges = $badges;
		$this->url = $url;
	}

	public function getSiteId(): string {
		return $this->siteId;
	}

	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @return ItemId[]
	 */
	public function getBadges(): array {
		return $this->badges;
	}

	public function getUrl(): string {
		return $this->url;
	}

}
