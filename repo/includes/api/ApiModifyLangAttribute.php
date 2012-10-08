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
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
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

	/**
	 * @see  ApiModifyEntity::getTextForComment()
	 */
	protected function getTextForComment( array $params, $plural = 1 ) {
		return Autocomment::formatAutoComment(
			$this->getModuleName() . '-' . ( ( isset( $params['value'] ) && 0<strlen( $params['value'] ) ) ? 'set' : 'remove' ),
			array( /* $plural */ 1, $params['language'] )
		);
	}

	/**
	 * @see  ApiModifyEntity::getTextForSummary()
	 */
	protected function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			Autocomment::pickValuesFromParams( $params, 'value' )
		);
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
