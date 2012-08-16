/**
 * JavaScript for managing editable representation of item aliases. This is for editing a whole set of aliases, not just
 * a single one.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
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
window.wikibase.ui.PropertyEditTool.EditableAliases = function( subject, toolbar ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject, toolbar );
};
window.wikibase.ui.PropertyEditTool.EditableAliases.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableAliases.prototype, {

	API_VALUE_KEY: 'aliases',

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._init
	 *
	 * @param jQuery subject
	 * @param wikibase.ui.Toolbar toolbar
	 */
	_init: function( subject, toolbar ) {
		var newSubject = $( '<span>' );
		$( subject ).replaceWith( newSubject ).appendTo( newSubject );
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype._init.call( this, newSubject, toolbar );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._buildInterfaces
	 *
	 * @param jQuery subject
	 * @return wikibase.ui.PropertyEditTool.EditableValue.AliasInterface[]
	 */
	_buildInterfaces: function( subject ) {
		var interfaces = [];

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
		if( relatedInterface.isInEditMode() ) {
			wikibase.ui.PropertyEditTool.EditableValue.prototype._interfaceHandler_onInputRegistered.call( this, relatedInterface );
			// always enable cancel button since it is alright to have an empty value
			this._toolbar.editGroup.btnCancel.setDisabled( false );
		}
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getInputHelpMessage
	 *
	 * @return string tooltip help message
	 */
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-aliases-input-help-message', mw.config.get('wbDataLangName') );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._getValueFromApiResponse
	 *
	 * @return array|null
	 */
	_getValueFromApiResponse: function( response ) {
		if ( !$.isArray( response[ this.API_VALUE_KEY ][ window.mw.config.get( 'wgUserLanguage' ) ] ) ) {
			return null;
		} else {
			var values = [];
			$.each( response[ this.API_VALUE_KEY ][ window.mw.config.get( 'wgUserLanguage' ) ], function( i, item ) {
				values.push( item.value );
			} );
			return values;
		}
	},

	/**
	 * Sets a value
	 * @see wikibase.ui.PropertyEditTool.EditableValue
	 *
	 * @param Array value to set
	 * @return Array set value
	 */
	setValue: function( value ) {
		if( $.isArray( value ) ) {
			this._interfaces[0].setValue( value );
		}
		return this.getValue();
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.showError
	 */
	showError: function( error, apiAction ) {
		/*
		 * EditableAliases has no option to manually trigger a complete removal of the input component
		 * (but when saving with an empty value, a remove action is implied without having a remove button to which an
		 * error tooltip may be attached to; subsequently, the remove action has to be internally converted to an action
		 * triggered by the save button internally)
		 */
		if ( apiAction === this.API_ACTION.REMOVE ) {
			apiAction = this.API_ACTION.SAVE_TO_REMOVE;
		}
		wikibase.ui.PropertyEditTool.EditableValue.prototype.showError.call( this, error, apiAction );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getApiCallParams
	 *
	 * @param number apiAction see this.API_ACTION enum for all available actions
	 * @return Object containing the API call specific parameters
	 */
	getApiCallParams: function( apiAction ) {
		var params = window.wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams.call( this, apiAction );
		params.action = 'wbsetaliases';
		params.baserevid = mw.config.get( 'wgCurRevisionId' );
		params.set = this.getValue()[0].join( '|' );
		return params;
	},

	/**
	 * @see window.wikibase.ui.PropertyEditTool.EditableValue.preserveEmptyForm
	 * @var bool
	 */
	preserveEmptyForm: false
} );
