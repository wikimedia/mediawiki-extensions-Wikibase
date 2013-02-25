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
 */
abstract class ModifyLangAttribute extends ModifyEntity {

	/**
	 * @see \Wikibase\Api\ModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		if ( isset( $params['language'] ) && mb_strlen( $params['value'] ) > Settings::get( 'multilang-limit' ) ) {
			$this->dieUsage( $this->msg( 'wikibase-constraint-violation', $params['language'] )->text(), 'constraint-violation' );
		}
	}

	/**
	 * @see  \Wikibase\Api\IAutocomment::getTextForComment()
	 */
	public function getTextForComment( array $params, $plural = 1 ) {
		return Autocomment::formatAutoComment(
			$this->getModuleName() . '-' . ( ( isset( $params['value'] ) && 0<strlen( $params['value'] ) ) ? 'set' : 'remove' ),
			array( /* $plural */ 1, $params['language'] )
		);
	}

	/**
	 * @see  \Wikibase\Api\IAutocomment::getTextForSummary()
	 */
	public function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			Autocomment::pickValuesFromParams( $params, 'value' )
		);
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
