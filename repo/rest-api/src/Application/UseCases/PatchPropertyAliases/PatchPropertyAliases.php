<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases;

use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\AliasesEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyAliases {

	private PatchPropertyAliasesValidator $validator;
	private PropertyAliasesRetriever $aliasesRetriever;
	private AliasesSerializer $aliasesSerializer;
	private PatchJson $patchJson;
	private PropertyRetriever $propertyRetriever;
	private AliasesDeserializer $aliasesDeserializer;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		PatchPropertyAliasesValidator $validator,
		PropertyAliasesRetriever $aliasesRetriever,
		AliasesSerializer $aliasesSerializer,
		PatchJson $patchJson,
		PropertyRetriever $propertyRetriever,
		AliasesDeserializer $aliasesDeserializer,
		PropertyUpdater $propertyUpdater
	) {
		$this->validator = $validator;
		$this->aliasesRetriever = $aliasesRetriever;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->patchJson = $patchJson;
		$this->propertyRetriever = $propertyRetriever;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->propertyUpdater = $propertyUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchPropertyAliasesRequest $request ): PatchPropertyAliasesResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$aliases = $this->aliasesRetriever->getAliases( $deserializedRequest->getPropertyId() );
		$patchedAliases = $this->aliasesDeserializer->deserialize( $this->patchJson->execute(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			iterator_to_array( $this->aliasesSerializer->serialize( $aliases ) ),
			$deserializedRequest->getPatch()
		) );

		$property = $this->propertyRetriever->getProperty( $deserializedRequest->getPropertyId() );
		$property->getFingerprint()->setAliasGroups( $patchedAliases );

		$revision = $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( [], false, new AliasesEditSummary() )
		);

		return new PatchPropertyAliasesResponse(
			$revision->getProperty()->getAliases(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
