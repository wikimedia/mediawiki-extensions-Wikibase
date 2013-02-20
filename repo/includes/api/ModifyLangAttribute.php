<?php

namespace Wikibase\Api;

use ApiBase, Language;

use Wikibase\Autocomment;
use Wikibase\Utils;

/**
 * API module to set the language attributes for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ModifyLangAttribute extends ModifyEntity {

	/**
	 * @see \Wikibase\Api\ModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		// Note that language should always exist as a prerequisite for this
		// call to succeede. The param value will not always exist because
		// that signals a label to remove.
	}

	protected function createSummary( array $params ) {
		$set = isset( $params['value'] ) && 0<strlen( $params['value'] );

		$summary = parent::createSummary( $params );
		$summary->setAction( ( $set ? 'set' : 'remove' ) );
		$summary->setLanguage( $params['language'] );

		return $summary;
	}

	/**
	 * @see \ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			parent::getAllowedParamsForId(),
			parent::getAllowedParamsForSiteLink(),
			parent::getAllowedParamsForEntity(),
			array(
				'language' => array(
					ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
					ApiBase::PARAM_REQUIRED => true,
				),
				'value' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
			)
		);
	}

	/**
	 * @see \ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			parent::getParamDescriptionForId(),
			parent::getParamDescriptionForSiteLink(),
			parent::getParamDescriptionForEntity(),
			array(
				'language' => 'Language for the label and description',
				'value' => 'The value to set for the language attribute',
			)
		);
	}

}
