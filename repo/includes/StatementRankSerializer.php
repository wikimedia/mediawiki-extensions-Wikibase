<?php

namespace Wikibase;

use Deserializers\Deserializer;
use Serializers\Serializer;
use Wikibase\DataModel\Statement\Statement;

/**
 * Serializer and Deserializer for Statement Ranks.
 *
 * @todo this could be moved to DataModelSerialization (in some form)
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class StatementRankSerializer implements Serializer, Deserializer {

	/**
	 * @var string[]
	 */
	private static $rankMap = array(
		Statement::RANK_DEPRECATED => 'deprecated',
		Statement::RANK_NORMAL => 'normal',
		Statement::RANK_PREFERRED => 'preferred',
	);

	/**
	 * Returns the available ranks in serialized form.
	 *
	 * @return string[]
	 */
	public static function getRanks() {
		return array_values( self::$rankMap );
	}

	/**
	 * Deserializes the rank and returns an element from the Statement::RANK_ enum.
	 *
	 * @param string $serializedRank
	 *
	 * @return integer
	 */
	public function deserialize( $serializedRank ) {
		$ranks = array_flip( self::$rankMap );
		return $ranks[$serializedRank];
	}

	/**
	 * Serializes the rank.
	 *
	 * @param integer $rank
	 *
	 * @return string
	 */
	public function serialize( $rank ) {
		return self::$rankMap[$rank];
	}

}
