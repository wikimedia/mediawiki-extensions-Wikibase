<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SiteIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinkRequest implements UseCaseRequest, ItemIdRequest, SiteIdRequest {

	private string $itemId;
	private string $siteId;

	public function __construct( string $itemId, string $siteId ) {
		$this->itemId = $itemId;
		$this->siteId = $siteId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getSiteId(): string {
		return $this->siteId;
	}

}
