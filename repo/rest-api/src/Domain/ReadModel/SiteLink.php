<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class SiteLink {

	private string $site;
	private string $title;
	private array $badges;
	private string $url;

	/**
	 * @param ItemId[] $badges
	 */
	public function __construct( string $site, string $title, array $badges, string $url ) {
		foreach ( $badges as $badge ) {
			if ( !$badge instanceof ItemId ) {
				throw new InvalidArgumentException( '$badges must be of type ItemId[]' );
			}
		}

		$this->site = $site;
		$this->title = $title;
		$this->badges = $badges;
		$this->url = $url;
	}

	public function getSite(): string {
		return $this->site;
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
