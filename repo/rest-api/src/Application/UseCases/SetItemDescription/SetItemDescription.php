<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class SetItemDescription {

	private SetItemDescriptionValidator $validator;
	private AssertItemExists $assertItemExists;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		SetItemDescriptionValidator $validator,
		AssertItemExists $assertItemExists,
		ItemRetriever $itemRetriever,
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
	public function execute( SetItemDescriptionRequest $request ): SetItemDescriptionResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );
		$description = new Term( $request->getLanguageCode(), $request->getDescription() );

		$this->assertItemExists->execute( $itemId );

		$this->assertUserIsAuthorized->execute( $itemId, $request->getUsername() );

		$item = $this->itemRetriever->getItem( $itemId );
		$descriptionExists = $item->getDescriptions()->hasTermForLanguage( $request->getLanguageCode() );
		$item->getDescriptions()->setTerm( $description );

		$editSummary = $descriptionExists
			? DescriptionEditSummary::newReplaceSummary( $request->getComment(), $description )
			: DescriptionEditSummary::newAddSummary( $request->getComment(), $description );

		$revision = $this->itemUpdater->update(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$item,
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				$editSummary
			)
		);

		return new SetItemDescriptionResponse(
			$revision->getItem()->getDescriptions()[$request->getLanguageCode()],
			$revision->getLastModified(),
			$revision->getRevisionId(),
			$descriptionExists
		);
	}
}
