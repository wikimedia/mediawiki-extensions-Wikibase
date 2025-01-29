<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink;

use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\SitelinkEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class SetSitelink {

	use UpdateExceptionHandler;

	private SetSitelinkValidator $validator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		SetSitelinkValidator $validator,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemWriteModelRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( SetSitelinkRequest $request ): SetSitelinkResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$siteId = $deserializedRequest->getSiteId();
		$sitelink = $deserializedRequest->getSitelink();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $deserializedRequest->getEditMetadata()->getUser() );

		$item = $this->itemRetriever->getItemWriteModel( $itemId );
		$sitelinkExists = $item->hasLinkToSite( $siteId );

		$editSummary = $sitelinkExists
			? SitelinkEditSummary::newReplaceSummary( $request->getComment(), $sitelink, $item->getSiteLink( $siteId ) )
			: SitelinkEditSummary::newAddSummary( $request->getComment(), $sitelink );

		$item->getSiteLinkList()->setSiteLink( $sitelink );

		$newRevision = $this->executeWithExceptionHandling( fn() => $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( [], false, $editSummary )
		) );

		return new SetSitelinkResponse(
			$newRevision->getItem()->getSitelinks()[ $siteId ],
			$newRevision->getLastModified(),
			$newRevision->getRevisionId(),
			$sitelinkExists
		);
	}

}
