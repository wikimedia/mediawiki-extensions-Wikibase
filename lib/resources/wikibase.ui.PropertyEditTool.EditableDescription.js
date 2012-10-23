/**
 * JavaScript for managing editable representation of an items description.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
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
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getApiCallParams
	 *
	 * @param number apiAction
	 * @return Object containing the API call specific parameters
	 */
	getApiCallParams: function( apiAction ) {
		var params = PARENT.prototype.getApiCallParams.call( this, apiAction );
		if( mw.config.get( 'wbEntityId' ) === null ) { // new item should be created
			var newItem ='{"descriptions":{"' + mw.config.get( 'wgUserLanguage' ) + '":"' + this.getValue().toString() + '"}}';
			return $.extend( params, {
				action: "wbsetitem",
				data: newItem
			} );
		} else {
			return $.extend( params, {
				action: 'wbsetdescription',
				value: this.getValue().toString(),
				baserevid: mw.config.get( 'wgCurRevisionId' )
			} );
		}
	}
} );

/**
 * @see wb.ui.PropertyEditTool.EditableValue.newFromDom
 */
SELF.newFromDom = function( subject, options, toolbar ) {
	var $subject = $( subject ),
		interfaceParent = $subject.children( '.wb-value' ).first(),
		simpleInterface = new wb.ui.PropertyEditTool.EditableValue.Interface( interfaceParent, this );

	simpleInterface.setOption( 'inputPlaceholder', mw.msg( 'wikibase-description-edit-placeholder' ) );
	simpleInterface.setOption( 'autoExpand', false );

	return new SELF( $subject, options, simpleInterface, toolbar );
};

}( mediaWiki, wikibase, jQuery ) );
