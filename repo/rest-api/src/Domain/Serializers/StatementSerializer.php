<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\Serializers\StatementSerializer as LegacyStatementSerializer;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementSerializer implements Serializer {
	private $statementSerializer;

	/**
	 * @param LegacyStatementSerializer $statementSerializer Should have $useObjectsForMaps (e.g. for qualifiers) set to true.
	 */
	public function __construct( LegacyStatementSerializer $statementSerializer ) {
		$this->statementSerializer = $statementSerializer;
	}

	/**
	 * @param Statement $statement
	 */
	// phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	public function serialize( $statement ): array {
		return array_merge(
			[
				'qualifiers' => (object)[],
				'qualifiers-order' => [],
				'references' => [],
			],
			$this->statementSerializer->serialize( $statement )
		);
	}

}
