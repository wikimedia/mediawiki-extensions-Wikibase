<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription;

use OutOfBoundsException;
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
class RemoveItemDescription {

	private RemoveItemDescriptionValidator $useCaseValidator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		RemoveItemDescriptionValidator $useCaseValidator,
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

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( RemoveItemDescriptionRequest $request ): void {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();
		$providedEditMetadata = $deserializedRequest->getEditMetadata();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->execute( $itemId, $providedEditMetadata->getUser()->getUsername() );

		$item = $this->itemRetriever->getItem( $itemId );
		try {
			$description = $item->getDescriptions()->getByLanguage( $languageCode );
		} catch ( OutOfBoundsException $e ) {
			throw new UseCaseError(
				UseCaseError::DESCRIPTION_NOT_DEFINED,
				"Item with the ID $itemId does not have a description in the language: $languageCode"
			);
		}
		$item->getDescriptions()->removeByLanguage( $languageCode );

		$editMetadata = new EditMetadata(
			$providedEditMetadata->getTags(),
			$providedEditMetadata->isBot(),
			DescriptionEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $description )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$this->itemUpdater->update( $item, $editMetadata );
	}

}
