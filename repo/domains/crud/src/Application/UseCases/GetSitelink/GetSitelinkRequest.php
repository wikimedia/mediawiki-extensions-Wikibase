<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SiteIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetSitelinkRequest implements UseCaseRequest, ItemIdRequest, SiteIdRequest {

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
