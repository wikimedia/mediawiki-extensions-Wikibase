<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0-or-later
 */
class PropertyDeserializer {

	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private AliasesDeserializer $aliasesDeserializer;
	private StatementDeserializer $statementDeserializer;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer,
		AliasesDeserializer $aliasesDeserializer,
		StatementDeserializer $statementDeserializer
	) {

		$this->labelsDeserializer = $labelsDeserializer;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->statementDeserializer = $statementDeserializer;
	}

	public function deserialize( array $property ): Property {
		return new Property(
			null,
			new Fingerprint(
				$this->labelsDeserializer->deserialize( $property[ 'labels' ] ?? [] ),
				$this->descriptionsDeserializer->deserialize( $property[ 'descriptions' ] ?? [] ),
				$this->aliasesDeserializer->deserialize( $property[ 'aliases' ] ?? [], '/property' )
			),
			$property[ 'data_type' ],
			$this->deserializeStatements( $property[ 'statements' ] ?? [] )
		);
	}

	private function deserializeStatements( array $statements ): StatementList {
		$statementList = new StatementList();
		foreach ( $statements as $propertyStatements ) {
			foreach ( $propertyStatements as $statement ) {
				$statementList->addStatement( $this->statementDeserializer->deserialize( $statement ) );
			}
		}

		return $statementList;
	}
}
