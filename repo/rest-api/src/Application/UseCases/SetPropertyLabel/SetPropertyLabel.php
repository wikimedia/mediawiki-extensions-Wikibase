<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel;

use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PropertyWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class SetPropertyLabel {

	use UpdateExceptionHandler;

	private PropertyWriteModelRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private SetPropertyLabelValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		SetPropertyLabelValidator $validator,
		PropertyWriteModelRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->validator = $validator;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( SetPropertyLabelRequest $request ): SetPropertyLabelResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$label = $deserializedRequest->getPropertyLabel();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertPropertyExists->execute( $propertyId );
		$this->assertUserIsAuthorized->checkEditPermissions( $propertyId, $editMetadata->getUser() );

		$property = $this->propertyRetriever->getPropertyWriteModel( $propertyId );
		$labelExists = $property->getLabels()->hasTermForLanguage( $label->getLanguageCode() );
		$property->getLabels()->setTerm( $label );

		$editSummary = $labelExists
			? LabelEditSummary::newReplaceSummary( $editMetadata->getComment(), $label )
			: LabelEditSummary::newAddSummary( $editMetadata->getComment(), $label );

		$newRevision = $this->executeWithExceptionHandling( fn() => $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $editMetadata->getTags(), $editMetadata->isBot(), $editSummary )
		) );

		return new SetPropertyLabelResponse(
			$newRevision->getProperty()->getLabels()[$label->getLanguageCode()],
			$newRevision->getLastModified(),
			$newRevision->getRevisionId(),
			$labelExists
		);
	}

}
