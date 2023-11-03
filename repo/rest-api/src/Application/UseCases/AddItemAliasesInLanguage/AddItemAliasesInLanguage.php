<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage;

use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class AddItemAliasesInLanguage {

	private ItemRetriever $itemRetriever;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemUpdater $itemUpdater;
	private AddItemAliasesInLanguageValidator $validator;

	public function __construct(
		ItemRetriever $itemRetriever,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemUpdater $itemUpdater,
		AddItemAliasesInLanguageValidator $validator
	) {
		$this->itemRetriever = $itemRetriever;
		$this->assertItemExists = $assertItemExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->itemUpdater = $itemUpdater;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( AddItemAliasesInLanguageRequest $request ): AddItemAliasesInLanguageResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();
		$newAliases = $deserializedRequest->getItemAliasesInLanguage();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->execute( $itemId, $editMetadata->getUser()->getUsername() );

		$item = $this->itemRetriever->getItem( $itemId );
		$aliasesExist = $item->getAliasGroups()->hasGroupForLanguage( $languageCode );
		$originalAliases = $aliasesExist ? $item->getAliasGroups()->getByLanguage( $languageCode )->getAliases() : [];

		$item->getAliasGroups()->setAliasesForLanguage( $languageCode, $this->addAliases( $originalAliases, $newAliases ) );
		$newRevision = $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$editMetadata->getTags(),
				$editMetadata->isBot(),
				AliasesInLanguageEditSummary::newAddSummary(
					$editMetadata->getComment(),
					$item->getAliasGroups()->getByLanguage( $languageCode )
				)
			)
		);

		return new AddItemAliasesInLanguageResponse(
			$newRevision->getItem()->getAliases()[ $languageCode ],
			$newRevision->getLastModified(),
			$newRevision->getRevisionId(),
			$aliasesExist
		);
	}

	private function addAliases( array $originalAliases, array $newAliases ): array {
		$duplicates = array_intersect( $newAliases, $originalAliases );
		if ( !empty( $duplicates ) ) {
			throw new UseCaseError(
				UseCaseError::ALIAS_DUPLICATE,
				"Alias list contains a duplicate alias: '${duplicates[0]}'",
				[ UseCaseError::CONTEXT_ALIAS => $duplicates[0] ]
			);
		}

		return array_merge( $originalAliases, $newAliases );
	}

}
