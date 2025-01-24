<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription;

use OutOfBoundsException;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemDescription {
	use UpdateExceptionHandler;

	private RemoveItemDescriptionValidator $useCaseValidator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		RemoveItemDescriptionValidator $useCaseValidator,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemWriteModelRetriever $itemRetriever,
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
		$this->assertUserIsAuthorized->checkEditPermissions( $itemId, $providedEditMetadata->getUser() );

		$item = $this->itemRetriever->getItemWriteModel( $itemId );
		try {
			$description = $item->getDescriptions()->getByLanguage( $languageCode );
		} catch ( OutOfBoundsException $e ) {
			throw UseCaseError::newResourceNotFound( 'description' );
		}
		$item->getDescriptions()->removeByLanguage( $languageCode );

		$editMetadata = new EditMetadata(
			$providedEditMetadata->getTags(),
			$providedEditMetadata->isBot(),
			DescriptionEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $description )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$this->executeWithExceptionHandling( fn() => $this->itemUpdater->update( $item, $editMetadata ) );
	}

}
