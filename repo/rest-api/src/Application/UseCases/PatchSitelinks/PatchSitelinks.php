<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks;

use Wikibase\Repo\RestApi\Application\Serialization\SitelinksDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SitelinksEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\SitelinksRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchSitelinks {
	private PatchSitelinksValidator $useCaseValidator;
	private SitelinksRetriever $sitelinksRetriever;
	private SitelinksSerializer $sitelinksSerializer;
	private PatchJson $patcher;
	private ItemRetriever $itemRetriever;
	private SitelinksDeserializer $sitelinksDeserializer;
	private ItemUpdater $itemUpdater;

	public function __construct(
		PatchSitelinksValidator $useCaseValidator,
		SitelinksRetriever $SitelinksRetriever,
		SitelinksSerializer $sitelinksSerializer,
		PatchJson $patcher,
		ItemRetriever $itemRetriever,
		SitelinksDeserializer $sitelinksDeserializer,
		ItemUpdater $itemUpdater
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->sitelinksRetriever = $SitelinksRetriever;
		$this->sitelinksSerializer = $sitelinksSerializer;
		$this->patcher = $patcher;
		$this->itemRetriever = $itemRetriever;
		$this->sitelinksDeserializer = $sitelinksDeserializer;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( PatchSitelinksRequest $request ): PatchSitelinksResponse {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();

		$patchedSitelinks = $this->patcher->execute(
			iterator_to_array( $this->sitelinksSerializer->serialize( $this->sitelinksRetriever->getSitelinks( $itemId ) ) ),
			$deserializedRequest->getPatch()
		);

		$modifiedSitelinks = $this->sitelinksDeserializer->deserialize( $patchedSitelinks );

		$item = $this->itemRetriever->getItem( $itemId );
		$item->setSiteLinkList( $modifiedSitelinks );

		$editMetadata = new EditMetadata(
			$deserializedRequest->getEditMetadata()->getTags(),
			$deserializedRequest->getEditMetadata()->isBot(),
			SitelinksEditSummary::newPatchSummary( $deserializedRequest->getEditMetadata()->getComment() )
		);

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$revision = $this->itemUpdater->update( $item, $editMetadata );

		return new PatchSitelinksResponse(
			$revision->getItem()->getSitelinks(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}
}
