<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateProperty;

use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Domain\Model\CreatePropertyEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\PropertyCreator;

/**
 * @license GPL-2.0-or-later
 */
class CreateProperty {

	private CreatePropertyValidator $validator;
	private PropertyCreator $propertyCreator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		CreatePropertyValidator $validator,
		PropertyCreator $propertyCreator,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->validator = $validator;
		$this->propertyCreator = $propertyCreator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	public function execute( CreatePropertyRequest $request ): CreatePropertyResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable $request->getUsername() is checked
		$user = $request->getUsername() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		$this->assertUserIsAuthorized->checkCreatePropertyPermissions( $user );

		$revision = $this->propertyCreator->create(
			$deserializedRequest->getProperty(),
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				CreatePropertyEditSummary::newSummary( $request->getComment() )
			)
		);

		return new CreatePropertyResponse( $revision->getProperty(), $revision->getLastModified(), $revision->getRevisionId() );
	}

}
