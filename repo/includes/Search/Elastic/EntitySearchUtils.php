<?php
namespace Wikibase\Repo\Search\Elastic;

use Elastica\Query\ConstantScore;
use Elastica\Query\Match;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * Utilities useful for entity searches.
 * @package Wikibase\Repo\Search\Elastic
 */
final class EntitySearchUtils {

	/**
	 * Create constant score query for a field.
	 * @param string $field
	 * @param string|double $boost
	 * @param string $text
	 * @return ConstantScore
	 */
	public static function makeConstScoreQuery( $field, $boost, $text ) {
		$csquery = new ConstantScore();
		$csquery->setFilter( new Match( $field, $text ) );
		$csquery->setBoost( $boost );
		return $csquery;
	}

	/**
	 * If the text looks like ID, normalize it to ID title
	 * Cases handled:
	 * - q42
	 * - (q42)
	 * - leading/trailing spaces
	 * - http://www.wikidata.org/entity/Q42
	 * @param string $text
	 * @param EntityIdParser $idParser
	 * @return string Normalized ID or original string
	 */
	public static function normalizeId( $text, EntityIdParser $idParser ) {
		// TODO: this is a bit hacky, better way would be to make the field case-insensitive
		// or add new subfiled which is case-insensitive
		$text = strtoupper( str_replace( [ '(', ')' ], '', trim( $text ) ) );
		$id = self::parseOrNull( $text, $idParser );
		if ( $id ) {
			return $id->getSerialization();
		}
		if ( preg_match( '/\b(\w+)$/', $text, $matches ) && $matches[1] ) {
			$id = self::parseOrNull( $matches[1], $idParser );
			if ( $id ) {
				return $id->getSerialization();
			}
		}
		return $text;
	}

	/**
	 * Parse entity ID or return null
	 * @param $text
	 * @param EntityIdParser $idParser
	 * @return null|\Wikibase\DataModel\Entity\EntityId
	 */
	public static function parseOrNull( $text, EntityIdParser $idParser ) {
		try {
			$id = $idParser->parse( $text );
		} catch ( EntityIdParsingException $ex ) {
			return null;
		}
		return $id;
	}

}
