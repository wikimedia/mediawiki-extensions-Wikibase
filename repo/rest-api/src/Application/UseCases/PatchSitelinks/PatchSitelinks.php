<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks;

use Wikibase\Repo\RestApi\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SitelinksEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\ItemWriteModelRetriever;
use Wikibase\Repo\RestApi\Domain\Services\SitelinksRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchSitelinks {
	private PatchSitelinksValidator $useCaseValidator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private SitelinksRetriever $sitelinksRetriever;
	private SitelinksSerializer $sitelinksSerializer;
	private PatchJson $patcher;
	private ItemWriteModelRetriever $itemRetriever;
	private PatchedSitelinksValidator $patchedSitelinksValidator;
	private ItemUpdater $itemUpdater;

	public function __construct(
		PatchSitelinksValidator $useCaseValidator,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		SitelinksRetriever $SitelinksRetriever,
		SitelinksSerializer $sitelinksSerializer,
		PatchJson $patcher,
		ItemWriteModelRetriever $itemRetriever,
		PatchedSitelinksValidator $patchedSitelinksValidator,
		ItemUpdater $itemUpdater
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->assertItemExists = $assertItemExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->sitelinksRetriever = $SitelinksRetriever;
		$this->sitelinksSerializer = $sitelinksSerializer;
		$this->patcher = $patcher;
		$this->itemRetriever = $itemRetriever;
		$this->patchedSitelinksValidator = $patchedSitelinksValidator;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( PatchSitelinksRequest $request ): PatchSitelinksResponse {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $deserializedRequest->getEditMetadata()->getUser() );

		$originalSitelinksSerialization = iterator_to_array( $this->sitelinksSerializer->serialize(
			$this->sitelinksRetriever->getSitelinks( $itemId )
		) );

		$patchedSitelinks = $this->patcher->execute( $originalSitelinksSerialization, $deserializedRequest->getPatch() );

		$modifiedSitelinks = $this->patchedSitelinksValidator->validateAndDeserialize(
			"$itemId",
			$originalSitelinksSerialization,
			$patchedSitelinks
		);

		$item = $this->itemRetriever->getItemWriteModel( $itemId );
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
