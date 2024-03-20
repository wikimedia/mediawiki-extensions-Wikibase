<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateItem;

use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemCreator;

/**
 * @license GPL-2.0-or-later
 */
class CreateItem {

	private CreateItemValidator $validator;
	private ItemCreator $itemCreator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		CreateItemValidator $validator,
		ItemCreator $itemCreator,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->itemCreator = $itemCreator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( CreateItemRequest $request ): CreateItemResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$item = $deserializedRequest->getItem();

		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertUserIsAuthorized->checkCreateItemPermissions( $editMetadata->getUser() );

		$revision = $this->itemCreator->create(
			$item,
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				ItemEditSummary::newCreateSummary( $request->getComment() )
			)
		);

		return new CreateItemResponse( $revision->getItem(), $revision->getLastModified(), $revision->getRevisionId() );
	}

}
