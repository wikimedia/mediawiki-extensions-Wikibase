<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetSitelink;

use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SitelinkEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class SetSitelink {

	private SetSitelinkValidator $validator;
	private SitelinkDeserializer $sitelinkDeserializer;
	private AssertItemExists $assertItemExists;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		SetSitelinkValidator $validator,
		SitelinkDeserializer $sitelinkDeserializer,
		AssertItemExists $assertItemExists,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->validator = $validator;
		$this->sitelinkDeserializer = $sitelinkDeserializer;
		$this->assertItemExists = $assertItemExists;
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
		$sitelink = $this->sitelinkDeserializer->deserialize( $siteId, $request->getSitelink() );

		$this->assertItemExists->execute( $itemId );

		$item = $this->itemRetriever->getItem( $itemId );
		$sitelinkExists = $item->getSiteLinkList()->hasLinkWithSiteId( $siteId );
		$item->getSiteLinkList()->setSiteLink( $sitelink );

		$editSummary = $sitelinkExists
			? SitelinkEditSummary::newReplaceSummary( '', $sitelink )
			: SitelinkEditSummary::newAddSummary( '', $sitelink );

		$newRevision = $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( [], false, $editSummary )
		);

		return new SetSitelinkResponse(
			$newRevision->getItem()->getSitelinks()[ $siteId ],
			$newRevision->getLastModified(),
			$newRevision->getRevisionId(),
			$sitelinkExists
		);
	}

}
