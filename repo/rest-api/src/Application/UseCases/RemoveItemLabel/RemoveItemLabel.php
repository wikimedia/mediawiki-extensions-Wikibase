<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel;

use OutOfBoundsException;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemLabel {

	private RemoveItemLabelValidator $useCaseValidator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		RemoveItemLabelValidator $useCaseValidator,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->assertItemExists = $assertItemExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( RemoveItemLabelRequest $request ): void {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );

		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();
		$providedEditMetadata = $deserializedRequest->getEditMetadata();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->execute( $itemId, $providedEditMetadata->getUser() );

		$item = $this->itemRetriever->getItem( $itemId );

		try {
			$label = $item->getLabels()->getByLanguage( $languageCode );
		} catch ( OutOfBoundsException $e ) {
			throw new UseCaseError(
				UseCaseError::LABEL_NOT_DEFINED,
				"Item with the ID $itemId does not have a label in the language: $languageCode"
			);
		}

		$item->getLabels()->removeByLanguage( $languageCode );

		$summary = LabelEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $label );

		$this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $providedEditMetadata->getTags(), $providedEditMetadata->isBot(), $summary )
		);
	}

}
