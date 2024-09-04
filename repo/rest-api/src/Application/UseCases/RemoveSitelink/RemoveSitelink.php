<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveSitelink;

use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SitelinkEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class RemoveSitelink {

	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertItemExists $assertItemExists;
	private RemoveSitelinkValidator $validator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		ItemWriteModelRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		AssertItemExists $assertItemExists,
		RemoveSitelinkValidator $validator,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->assertItemExists = $assertItemExists;
		$this->validator = $validator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( RemoveSitelinkRequest $request ): void {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$siteId = $deserializedRequest->getSiteId();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $editMetadata->getUser() );

		$item = $this->itemRetriever->getItemWriteModel( $itemId );

		if ( !$item->hasLinkToSite( $siteId ) ) {
			throw UseCaseError::newResourceNotFound( 'sitelink' );
		}

		$removedSitelink = $item->getSiteLink( $siteId );
		$item->removeSiteLink( $siteId );
		$this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$editMetadata->getTags(),
				$editMetadata->isBot(),
				SitelinkEditSummary::newRemoveSummary( $editMetadata->getComment(), $removedSitelink )
			)
		);
	}

}
