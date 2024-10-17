<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateProperty;

use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Domain\Model\CreatePropertyEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\PropertyCreator;

/**
 * @license GPL-2.0-or-later
 */
class CreateProperty {

	private PropertyDeserializer $propertyDeserializer;
	private PropertyCreator $propertyCreator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		PropertyDeserializer $propertyDeserializer,
		PropertyCreator $propertyCreator,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->propertyDeserializer = $propertyDeserializer;
		$this->propertyCreator = $propertyCreator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	public function execute( CreatePropertyRequest $request ): CreatePropertyResponse {
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable $request->getUsername() is checked
		$user = $request->getUsername() ? User::withUsername( $request->getUsername() ) : User::newAnonymous();
		$this->assertUserIsAuthorized->checkCreatePropertyPermissions( $user );

		$revision = $this->propertyCreator->create(
			$this->propertyDeserializer->deserialize( $request->getProperty() ),
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				CreatePropertyEditSummary::newSummary( $request->getComment() )
			)
		);

		return new CreatePropertyResponse( $revision->getProperty(), $revision->getLastModified(), $revision->getRevisionId() );
	}

}
