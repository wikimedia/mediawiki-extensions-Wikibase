<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0-or-later
 */
class PropertyDeserializer {

	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private AliasesDeserializer $aliasesDeserializer;
	private StatementsDeserializer $statementsDeserializer;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer,
		AliasesDeserializer $aliasesDeserializer,
		StatementsDeserializer $statementsDeserializer
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->statementsDeserializer = $statementsDeserializer;
	}

	/**
	 * @throws InvalidFieldException
	 * @throws MissingFieldException
	 * @throws EmptyLabelException
	 * @throws EmptyDescriptionException
	 * @throws EmptyAliasException
	 * @throws DuplicateAliasException
	 */
	public function deserialize( array $serialization ): Property {
		return new Property(
			isset( $serialization[ 'id' ] ) ? new NumericPropertyId( $serialization[ 'id' ] ) : null,
			new Fingerprint(
				$this->labelsDeserializer->deserialize( (array)( $serialization[ 'labels' ] ?? [] ) ),
				$this->descriptionsDeserializer->deserialize( (array)( $serialization[ 'descriptions' ] ?? [] ) ),
				$this->aliasesDeserializer->deserialize( (array)( $serialization[ 'aliases' ] ?? [] ) )
			),
			$serialization[ 'data-type' ],
			$this->statementsDeserializer->deserialize( (array)( $serialization[ 'statements' ] ?? [] ) )
		);
	}

}
