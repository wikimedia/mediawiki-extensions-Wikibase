<?php

namespace Wikibase;
use ApiBase, Language;

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
abstract class ApiModifyLangAttribute extends ApiModifyEntity {

	/**
	 * @see ApiModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		// Note that language should always exist as a prerequisite for this
		// call to succeede. The param value will not always exist because
		// that signals a label to remove.
	}

	protected function createSummary( array $params ) {
		$summary = parent::createSummary( $params );
		$summary->addAutoCommentArgs( 1, $params['language'] );
		$summary->setAction( ( isset( $params['value'] ) && 0<strlen( $params['value'] ) ) ? 'set' : 'remove' );

		return $summary;
	}

	/**
	 * @see ApiBase::getAllowedParams()
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
	 * @see ApiBase::getParamDescription()
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
