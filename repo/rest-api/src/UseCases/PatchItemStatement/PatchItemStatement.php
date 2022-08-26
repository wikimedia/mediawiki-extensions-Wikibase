<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementPatcher;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatement {

	private $statementIdParser;
	private $itemRetriever;
	private $statementPatcher;
	private $itemUpdater;

	public function __construct(
		StatementGuidParser $statementIdParser,
		ItemRetriever $itemRetriever,
		StatementPatcher $statementPatcher,
		ItemUpdater $itemUpdater
	) {
		$this->statementIdParser = $statementIdParser;
		$this->itemRetriever = $itemRetriever;
		$this->statementPatcher = $statementPatcher;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( PatchItemStatementRequest $request ): PatchItemStatementSuccessResponse {
		// TODO: request validation (T316243)

		$requestedItemId = $request->getItemId();
		$statementId = $this->statementIdParser->parse( $request->getStatementId() );
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementId->getEntityId();
		'@phan-var ItemId $itemId';

		// TODO: handle statement not found and redirect errors (T316321)

		$item = $this->itemRetriever->getItem( $itemId );
		$statementToPatch = $item->getStatements()->getFirstStatementWithGuid( (string)$statementId );
		$patchedStatement = $this->statementPatcher->patch( $statementToPatch, $request->getPatch() );

		// TODO: handle errors caused by patching (T316319)
		// TODO: validate patched statement (T316316)

		$item->getStatements()->replaceStatement( $statementId, $patchedStatement );

		$newRevision = $this->itemUpdater->update(
			$item,
			new EditMetadata( $request->getEditTags(), $request->isBot(), $request->getComment() )
		);

		return new PatchItemStatementSuccessResponse(
			$newRevision->getItem()->getStatements()->getFirstStatementWithGuid( (string)$statementId ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
