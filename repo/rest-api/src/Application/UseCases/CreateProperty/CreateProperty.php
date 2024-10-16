<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateProperty;

use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Domain\Model\CreatePropertyEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyCreator;

/**
 * @license GPL-2.0-or-later
 */
class CreateProperty {

	private PropertyDeserializer $propertyDeserializer;
	private PropertyCreator $propertyCreator;

	public function __construct( PropertyDeserializer $propertyDeserializer, PropertyCreator $propertyCreator ) {
		$this->propertyDeserializer = $propertyDeserializer;
		$this->propertyCreator = $propertyCreator;
	}

	public function execute( CreatePropertyRequest $request ): CreatePropertyResponse {
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
