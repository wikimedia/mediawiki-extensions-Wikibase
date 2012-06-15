/**
 * JavasSript for managing editable representation of item aliases. This is for editing a whole set of aliases, not just
 * a single one.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableAliases.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Serves the input interface for an items aliases, extends EditableValue.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableAliases = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableAliases.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableAliases.prototype, {

	API_VALUE_KEY: null, //NOTE: This has to be adjusted as soon as the wbsetaliases API module supports it

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._init
	 */
	_init: function( subject, toolbar ) {
		var newSubject = $( '<span>' );
		$( subject ).replaceWith( newSubject ).appendTo( newSubject );
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype._init.call( this, newSubject, toolbar );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._buildInterfaces
	 */
	_buildInterfaces: function( subject ) {
		var interfaces = new Array();

		// we have to wrap the list in another node for the ListInterface, since the <ul/> is the actual value
		var interfaceParent = $( '<div/>' );
		subject.children( 'ul:first' ).replaceWith( interfaceParent ).appendTo( interfaceParent );

		interfaces.push( new wikibase.ui.PropertyEditTool.EditableValue.AliasesInterface( interfaceParent, this ) );

		return interfaces;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._interfaceHandler_onInputRegistered
	 *
	 * @param relatedInterface wikibase.ui.PropertyEditTool.EditableValue.Interface
	 */
	_interfaceHandler_onInputRegistered: function( relatedInterface ) {
		if( ! relatedInterface.isInEditMode() ) {
			return;
		} else {
			wikibase.ui.PropertyEditTool.EditableValue.prototype._interfaceHandler_onInputRegistered.call( this, relatedInterface );
		}
		// always enable cancel button since it is alright to have an empty value
		this._toolbar.editGroup.btnCancel.setDisabled( false );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getInputHelpMessage
	 */
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-aliases-input-help-message', mw.config.get('wbDataLangName') );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._getValueFromApiResponse
	 *
	 * @return array
	 */
	_getValueFromApiResponse: function( response ) {
		var value = window.wikibase.ui.PropertyEditTool.EditableValue.prototype._getValueFromApiResponse.call( this, response );
		return value !== null
			? value.split( '|' ) // TODO: not yet supported by the API and the way this will be returned might (hopefully) be different.
			: null;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getApiCallParams
	 */
	getApiCallParams: function( apiAction ) {
		var params = window.wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams.call( this, apiAction );

		params.action = 'wbsetaliases';
		params.item = 'set';
		params.set = this.getValue()[0].join( '|' );

		return params;
	},

	/**
	 * @see window.wikibase.ui.PropertyEditTool.EditableValue.preserveEmptyForm
	 * @var bool
	 */
	preserveEmptyForm: false
} );
