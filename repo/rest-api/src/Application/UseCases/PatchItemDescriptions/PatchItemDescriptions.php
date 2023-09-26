<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptions {

	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemDescriptionsRetriever $descriptionsRetriever;
	private DescriptionsSerializer $descriptionsSerializer;
	private JsonPatcher $patcher;
	private ItemRetriever $itemRetriever;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private ItemUpdater $itemUpdater;

	public function __construct(
		AssertUserIsAuthorized $assertUserIsAuthorized,
		ItemDescriptionsRetriever $descriptionsRetriever,
		DescriptionsSerializer $descriptionsSerializer,
		JsonPatcher $patcher,
		ItemRetriever $itemRetriever,
		DescriptionsDeserializer $descriptionsDeserializer,
		ItemUpdater $itemUpdater
	) {
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->descriptionsRetriever = $descriptionsRetriever;
		$this->descriptionsSerializer = $descriptionsSerializer;
		$this->patcher = $patcher;
		$this->itemRetriever = $itemRetriever;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( PatchItemDescriptionsRequest $request ): PatchItemDescriptionsResponse {
		// T346771 - validate user input

		$itemId = new ItemId( $request->getItemId() );

		// T346774 - check if item not found or redirected
		$this->assertUserIsAuthorized->execute( $itemId, $request->getUsername() );

		$descriptions = $this->descriptionsRetriever->getDescriptions( $itemId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->descriptionsSerializer->serialize( $descriptions );

		// T346772 - handle errors during patching
		$patchedDescriptions = $this->patcher->patch( iterator_to_array( $serialization ), $request->getPatch() );

		// T346773 - validate the patched descriptions
		$modifiedDescriptions = $this->descriptionsDeserializer->deserialize( $patchedDescriptions );
		$item = $this->itemRetriever->getItem( $itemId );
		$originalDescriptions = $item->getDescriptions();
		$item->getFingerprint()->setDescriptions( $modifiedDescriptions );

		$editMetadata = new EditMetadata(
			$request->getEditTags(),
			$request->isBot(),
			DescriptionsEditSummary::newPatchSummary( $request->getComment(), $originalDescriptions, $modifiedDescriptions )
		);

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$revision = $this->itemUpdater->update( $item, $editMetadata );

		return new PatchItemDescriptionsResponse( $revision->getItem()->getDescriptions() );
	}

}
