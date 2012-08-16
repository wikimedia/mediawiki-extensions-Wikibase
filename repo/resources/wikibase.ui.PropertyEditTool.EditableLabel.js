/**
 * JavaScript for managing editable representation of item labels.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Tobias Gritschacher
 */
"use strict";

/**
 * Serves the input interface for the label of an item, extends EditableValue.
 *
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableLabel = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableLabel.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableLabel.prototype, {

	API_VALUE_KEY: 'labels',

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._buildInterfaces
	 *
	 * @param jQuery subject
	 * @return wikibase.ui.PropertyEditTool.EditableValue.Interface[]
	 */
	_buildInterfaces: function( subject ) {
		var interfaces = window.wikibase.ui.PropertyEditTool.EditableValue.prototype._buildInterfaces.call( this, subject );

		interfaces[0].inputPlaceholder = mw.msg( 'wikibase-label-edit-placeholder' );
		interfaces[0].autoExpand = true;

		interfaces[0].normalize = function( value ) {
			value = window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.normalize.call( this, value );
			value = value.replace( /\s+/g, ' ' ); // make sure we don't ever allow several spaces in the items label
			return value;
		};

		return interfaces;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.getInputHelpMessage
	 *
	 * @return string tooltip help message
	 */
	getInputHelpMessage: function() {
		return mw.msg( 'wikibase-label-input-help-message', mw.config.get('wbDataLangName') );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams
	 *
	 * @param number apiAction
	 * @return Object containing the API call specific parameters
	 */
	getApiCallParams: function( apiAction ) {
		var params = window.wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams.call( this, apiAction );
		if( mw.config.get( 'wbItemId' ) === null ) { // new item should be created
			var newItem ='{"labels":{"' + mw.config.get( 'wgUserLanguage' ) + '":"' + this.getValue().toString() + '"}}';
			return $.extend( params, {
				action: "wbsetitem",
				data: newItem
			} );
		} else {
			return $.extend( params, {
				action: "wbsetlabel",
				value: this.getValue().toString(),
				baserevid: mw.config.get( 'wgCurRevisionId' )
			} );
		}
	}
} );
