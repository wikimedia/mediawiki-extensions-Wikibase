/**
 * JavaScript for managing editable representation of an items description.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Tobias Gritschacher
 */
( function( mw, wb, $, undefined ) {
'use strict';
var PARENT = wb.ui.PropertyEditTool.EditableValue;

/**
 * Serves the input interface for an item description, extends EditableValue.
 * @constructor
 * @extends wb.ui.PropertyEditTool.EditableValue
 * @since 0.1
 */
var SELF = wb.ui.PropertyEditTool.EditableDescription = wb.utilities.inherit( PARENT, {
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.API_VALUE_KEY
	 */
	API_VALUE_KEY: 'descriptions',

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getInputHelpMessage
	 *
	 * @return string tooltip help message
	 */
	getInputHelpMessage: function() {
		return mw.msg( 'wikibase-description-input-help-message', mw.config.get('wbDataLangName') );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._setRevisionIdFromApiResponse
	 */
	_setRevisionIdFromApiResponse: function( response ) {
		wb.getRevisionStore().setDescriptionRevision( response.lastrevid );
		return true;
	},

	/**
	 * Calling the corresponding method in the wikibase.RepoApi
	 *
	 * @return {jQuery.Promise}
	 */
	queryApi: function() {
		return this._api.setDescription(
			mw.config.get( 'wbEntityId' ),
			wb.getRevisionStore().getDescriptionRevision(),
			this.getValue().toString(),
			mw.config.get( 'wgUserLanguage' )
		);
	}
} );

/**
 * @see wb.ui.PropertyEditTool.EditableValue.newFromDom
 */
SELF.newFromDom = function( subject, options, toolbar ) {
	var $subject = $( subject ),
		interfaceParent = $subject.children( '.wb-value' ).first(),
		simpleInterface = new wb.ui.PropertyEditTool.EditableValue.Interface( interfaceParent, {
			'inputPlaceholder': mw.msg( 'wikibase-description-edit-placeholder' ),
			'autoExpand': false
		} );

	return new SELF( $subject, options, simpleInterface, toolbar );
};

}( mediaWiki, wikibase, jQuery ) );
