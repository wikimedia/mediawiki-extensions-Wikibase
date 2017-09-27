<?php
namespace Wikibase\Repo\Search\Elastic;

use Elastica\Query\ConstantScore;
use Elastica\Query\Match;

/**
 * Utilities useful for entity searches.
 * @package Wikibase\Repo\Search\Elastic
 */
final class EntitySearchUtils {

	/**
	 * Get suitable rescore profile.
	 * If internal config has none, return just the name and let RescoureBuilder handle it.
	 * @param array $settings ElasticSearch repo settings
	 * @param string $profileConfigName Setting that defines our profile name.
	 * @return array|string Profile data or profile name.
	 */
	public static function getRescoreProfile( array $settings, $profileConfigName ) {
		if ( empty( $settings[$profileConfigName] ) ) {
			// somehow this config is not defined, this is not good!
			// use "default" and hope for the best
			$profileName = "default";
		} else {
			$profileName = $settings[$profileConfigName];
		}
		if ( !empty( $settings['rescoreProfileOverride'] ) ) {
			$profileName = $settings['rescoreProfileOverride'];
		}
		if ( $settings['rescoreProfiles'][$profileName] ) {
			return $settings['rescoreProfiles'][$profileName];
		}
		return $profileName;
	}

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

}
