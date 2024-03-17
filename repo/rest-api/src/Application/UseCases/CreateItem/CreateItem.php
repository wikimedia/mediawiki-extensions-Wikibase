<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateItem;

use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemCreator;

/**
 * @license GPL-2.0-or-later
 */
class CreateItem {

	private ItemDeserializer $itemDeserializer;
	private ItemCreator $itemCreator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		ItemDeserializer $itemDeserializer,
		ItemCreator $itemCreator,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->itemDeserializer = $itemDeserializer;
		$this->itemCreator = $itemCreator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( CreateItemRequest $request ): CreateItemResponse {
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$user = $request->getUsername() !== null ? User::withUsername( $request->getUsername() ) : User::newAnonymous();

		$this->assertUserIsAuthorized->checkCreateItemPermissions( $user );

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
