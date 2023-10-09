<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases;

use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
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
	private ItemAliasesRetriever $aliasesRetriever;
	private AliasesSerializer $aliasesSerializer;
	private PatchJson $patcher;
	private ItemRetriever $itemRetriever;
	private AliasesDeserializer $aliasesDeserializer;
	private ItemUpdater $itemUpdater;

	public function __construct(
		PatchItemAliasesValidator $useCaseValidator,
		ItemAliasesRetriever $aliasesRetriever,
		AliasesSerializer $aliasesSerializer,
		PatchJson $patcher,
		ItemRetriever $itemRetriever,
		AliasesDeserializer $aliasesDeserializer,
		ItemUpdater $itemUpdater
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->aliasesRetriever = $aliasesRetriever;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->patcher = $patcher;
		$this->itemRetriever = $itemRetriever;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( PatchItemAliasesRequest $request ): PatchItemAliasesResponse {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );

		$aliases = $this->aliasesRetriever->getAliases( $deserializedRequest->getItemId() );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->aliasesSerializer->serialize( $aliases );

		$patchedAliases = $this->patcher->execute( iterator_to_array( $serialization ), $deserializedRequest->getPatch() );

		$item = $this->itemRetriever->getItem( $deserializedRequest->getItemId() );
		$modifiedAliases = $this->aliasesDeserializer->deserialize( $patchedAliases );
		$item->getFingerprint()->setAliasGroups( $modifiedAliases );

		$editMetadata = new EditMetadata(
			$deserializedRequest->getEditMetadata()->getTags(),
			$deserializedRequest->getEditMetadata()->isBot(),
			AliasesEditSummary::newPatchSummary( $deserializedRequest->getEditMetadata()->getComment() )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$revision = $this->itemUpdater->update( $item, $editMetadata );

		return new PatchItemAliasesResponse(
			$revision->getItem()->getAliases(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
