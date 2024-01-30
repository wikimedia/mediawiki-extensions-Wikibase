<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetSitelinks;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\SitelinksRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetSitelinks {

	private GetSitelinksValidator $validator;
	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private SitelinksRetriever $sitelinksRetriever;

	public function __construct(
		GetSitelinksValidator $validator,
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		SitelinksRetriever $sitelinksRetriever
	) {
		$this->validator = $validator;
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->sitelinksRetriever = $sitelinksRetriever;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetSitelinksRequest $request ): GetSitelinksResponse {
		$itemId = $this->validator->validateAndDeserialize( $request )->getItemId();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		return new GetSitelinksResponse(
			$this->sitelinksRetriever->getSitelinks( $itemId ),
			$lastModified,
			$revisionId
		);
	}

}
