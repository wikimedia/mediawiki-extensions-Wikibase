<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0-or-later
 */
class ItemDeserializer {

	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private AliasesDeserializer $aliasesDeserializer;
	private SitelinksDeserializer $sitelinksDeserializer;
	private StatementDeserializer $statementDeserializer;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer,
		AliasesDeserializer $aliasesDeserializer,
		SitelinksDeserializer $sitelinksDeserializer,
		StatementDeserializer $statementDeserializer
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->sitelinksDeserializer = $sitelinksDeserializer;
		$this->statementDeserializer = $statementDeserializer;
	}

	public function deserialize( array $serialization ): Item {
		return new Item(
			isset( $serialization['id'] ) ? new ItemId( $serialization['id'] ) : null,
			new Fingerprint(
				$this->labelsDeserializer->deserialize( $serialization['labels'] ?? [] ),
				$this->descriptionsDeserializer->deserialize( $serialization['descriptions'] ?? [] ),
				$this->aliasesDeserializer->deserialize( $serialization['aliases'] ?? [] )
			),
			$this->sitelinksDeserializer->deserialize( $serialization['sitelinks'] ?? [] ),
			$this->deserializeStatements( $serialization['statements'] ?? [] )
		);
	}

	private function deserializeStatements( array $statementsSerialization ): StatementList {
		return new StatementList( ...array_map( [ $this->statementDeserializer, 'deserialize' ], $statementsSerialization ) );
	}

}
