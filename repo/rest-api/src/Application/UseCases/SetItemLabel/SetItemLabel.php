<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabel {

	use UpdateExceptionHandler;

	private AssertItemExists $assertItemExists;
	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private SetItemLabelValidator $validator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		SetItemLabelValidator $validator,
		AssertItemExists $assertItemExists,
		ItemWriteModelRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( SetItemLabelRequest $request ): SetItemLabelResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$label = $deserializedRequest->getItemLabel();

		$this->assertItemExists->execute( $itemId );

		$editMetadata = $deserializedRequest->getEditMetadata();
		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $editMetadata->getUser() );

		$item = $this->itemRetriever->getItemWriteModel( $itemId );
		$labelExists = $item->getLabels()->hasTermForLanguage( $label->getLanguageCode() );
		$item->getLabels()->setTerm( $label );

		$editSummary = $labelExists
			? LabelEditSummary::newReplaceSummary( $editMetadata->getComment(), $label )
			: LabelEditSummary::newAddSummary( $editMetadata->getComment(), $label );

		$newRevision = $this->executeWithExceptionHandling( fn() => $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $editMetadata->getTags(), $editMetadata->isBot(), $editSummary )
		) );

		return new SetItemLabelResponse(
			$newRevision->getItem()->getLabels()[$label->getLanguageCode()],
			$newRevision->getLastModified(),
			$newRevision->getRevisionId(),
			$labelExists
		);
	}

}
