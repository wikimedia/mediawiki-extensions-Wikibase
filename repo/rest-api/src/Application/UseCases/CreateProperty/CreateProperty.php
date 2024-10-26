<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateProperty;

use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\CreatePropertyEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyCreator;

/**
 * @license GPL-2.0-or-later
 */
class CreateProperty {

	use UpdateExceptionHandler;

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

	/**
	 * @throws UseCaseError
	 */
	public function execute( CreatePropertyRequest $request ): CreatePropertyResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertUserIsAuthorized->checkCreatePropertyPermissions( $editMetadata->getUser() );

		$revision = $this->executeWithExceptionHandling( fn() => $this->propertyCreator->create(
			$deserializedRequest->getProperty(),
			new EditMetadata(
				$editMetadata->getTags(),
				$editMetadata->isBot(),
				CreatePropertyEditSummary::newSummary( $request->getComment() )
			)
		) );

		return new CreatePropertyResponse( $revision->getProperty(), $revision->getLastModified(), $revision->getRevisionId() );
	}

}
