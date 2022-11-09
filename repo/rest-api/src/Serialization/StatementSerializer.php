<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementSerializer {

	private const RANK_LABELS = [
		Statement::RANK_DEPRECATED => 'deprecated',
		Statement::RANK_NORMAL => 'normal',
		Statement::RANK_PREFERRED => 'preferred'
	];
	private PropertyValuePairSerializer $propertyValuePairSerializer;

	public function __construct( PropertyValuePairSerializer $propertyValuePairSerializer ) {
		$this->propertyValuePairSerializer = $propertyValuePairSerializer;
	}

	public function serialize( Statement $statement ): array {

		return array_merge(
			[
			'id' => $statement->getGuid(),
			'rank' => self::RANK_LABELS[ $statement->getRank() ],
			'qualifiers' => array_map(
				fn( Snak $qualifier ) => $this->propertyValuePairSerializer->serialize( $qualifier ),
				$statement->getQualifiers()->getArrayCopy()
			),
			'references' => [],
			],
			$this->propertyValuePairSerializer->serialize( $statement->getMainSnak() )
		);
	}

}
