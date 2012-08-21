<?php

namespace Wikibase;
use ApiBase, Language;

/**
 * API module to set the language attributes for a Wikibase item.
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
abstract class ApiModifyLangAttribute extends ApiModifyItem {

	/**
	 * @see ApiModifyItem::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Item $item, array $params ) {
		$permissions = parent::getRequiredPermissions( $item, $params );
		return $permissions;
	}

	/**
	 * @see ApiModifyItem::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		// Note that language should always exist as a prerequisite for this
		// call to succeede. The param value will not always exist because
		// that signals a label to remove.
	}

	/**
	 * @see  ApiModifyItem::getTextForComment()
	 */
	protected function getTextForComment( array $params, $plural = 1 ) {
		return Autocomment::formatAutoComment(
			$this->getModuleName() . '-' . ( ( isset( $params['value'] ) && 0<strlen( $params['value'] ) ) ? 'set' : 'remove' ),
			array( /* $plural */ 1, $params['language'] )
		);
	}

	/**
	 * @see  ApiModifyItem::getTextForSummary()
	 */
	protected function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			Autocomment::pickValuesFromParams( $params, 'value' )
		);
	}

	/**
	 * @see ApiModifyItem::createItem()
	 */
	protected function createItem( array $params ) {
		$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
		return null;
	}

	/**
	 * @see ApiModifyItem::modifyItem()
	 */
	protected function modifyItem( ItemContent &$itemContent, array $params ) {
		return true;
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'language' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'value' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		) );
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'language' => 'Language for the label and description',
			'value' => 'The value to set for the language attribute',
		) );
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

}