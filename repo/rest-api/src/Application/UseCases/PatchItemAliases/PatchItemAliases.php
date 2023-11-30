<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases;

use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\AliasesEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemAliases {

	private PatchItemAliasesValidator $useCaseValidator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemAliasesRetriever $aliasesRetriever;
	private AliasesSerializer $aliasesSerializer;
	private PatchJson $patcher;
	private PatchedAliasesValidator $patchedAliasesValidator;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		PatchItemAliasesValidator $useCaseValidator,
		AssertItemExists $assertItemExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemAliasesRetriever $aliasesRetriever,
		AliasesSerializer $aliasesSerializer,
		PatchJson $patcher,
		PatchedAliasesValidator $patchedAliasesValidator,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->assertItemExists = $assertItemExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->aliasesRetriever = $aliasesRetriever;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->patcher = $patcher;
		$this->patchedAliasesValidator = $patchedAliasesValidator;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( PatchItemAliasesRequest $request ): PatchItemAliasesResponse {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertItemExists->execute( $itemId );
		$this->assertUserIsAuthorized->execute( $itemId, $editMetadata->getUser() );

		$aliases = $this->aliasesRetriever->getAliases( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->aliasesSerializer->serialize( $aliases );

		$patchedAliases = $this->patcher->execute( iterator_to_array( $serialization ), $deserializedRequest->getPatch() );
		$modifiedAliases = $this->patchedAliasesValidator->validateAndDeserialize( $patchedAliases );

		$item = $this->itemRetriever->getItem( $itemId );
		$originalAliases = $item->getAliasGroups();
		$item->getFingerprint()->setAliasGroups( $modifiedAliases );

		$revision = $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$editMetadata->getTags(),
				$editMetadata->isBot(),
				AliasesEditSummary::newPatchSummary( $editMetadata->getComment(), $originalAliases, $modifiedAliases )
			)
		);

		return new PatchItemAliasesResponse(
			$revision->getItem()->getAliases(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
