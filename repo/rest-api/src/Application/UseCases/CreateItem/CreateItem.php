<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateItem;

use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemCreator;

/**
 * @license GPL-2.0-or-later
 */
class CreateItem {

	private ItemDeserializer $itemDeserializer;
	private ItemCreator $itemCreator;

	public function __construct( ItemDeserializer $itemDeserializer, ItemCreator $itemCreator ) {
		$this->itemDeserializer = $itemDeserializer;
		$this->itemCreator = $itemCreator;
	}

	public function execute( CreateItemRequest $request ): CreateItemResponse {
		$revision = $this->itemCreator->create(
			$this->itemDeserializer->deserialize( $request->getItem() ),
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				ItemEditSummary::newCreateSummary( $request->getComment() )
			)
		);

		return new CreateItemResponse( $revision->getItem(), $revision->getLastModified(), $revision->getRevisionId() );
	}

}
