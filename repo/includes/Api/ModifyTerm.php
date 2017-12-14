<?php

namespace Wikibase\Repo\Api;

use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * API module to set the terms for a Wikibase entity.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ModifyTerm extends ModifyEntity {

	/**
	 * Creates a Summary object based on the given API call parameters.
	 * The Summary will be initializes with the appropriate action name
	 * and target language. It will not have any summary arguments set.
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	protected function createSummary( array $params ) {
		$set = isset( $params['value'] ) && 0 < strlen( $params['value'] );

		$summary = parent::createSummary( $params );
		$summary->setAction( ( $set ? 'set' : 'remove' ) );
		$summary->setLanguage( $params['language'] );

		return $summary;
	}

	/**
	 * @see ModifyEntity::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			[
				'language' => [
					self::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getTermsLanguages()->getLanguages(),
					self::PARAM_REQUIRED => true,
				],
				'value' => [
					self::PARAM_TYPE => 'string',
				],
			]
		);
	}

}
