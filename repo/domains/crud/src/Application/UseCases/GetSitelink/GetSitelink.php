<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink;

use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Services\SitelinkRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetSitelink {

	private GetSitelinkValidator $validator;
	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private SitelinkRetriever $sitelinkRetriever;

	public function __construct(
		GetSitelinkValidator $validator,
		GetLatestItemRevisionMetadata $getRevisionMetadata,
		SitelinkRetriever $sitelinkRetriever
	) {
		$this->validator = $validator;
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->sitelinkRetriever = $sitelinkRetriever;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetSitelinkRequest $request ): GetSitelinkResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$siteId = $deserializedRequest->getSiteId();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $itemId );

		$sitelink = $this->sitelinkRetriever->getSitelink( $itemId, $siteId );
		if ( !$sitelink ) {
			throw UseCaseError::newResourceNotFound( 'sitelink' );
		}

		return new GetSitelinkResponse( $sitelink, $lastModified, $revisionId );
	}
}
