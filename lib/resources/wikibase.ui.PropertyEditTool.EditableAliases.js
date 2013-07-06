/**
 * JavaScript for managing editable representation of item aliases. This is for editing a whole set of aliases, not just
 * a single one.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $ ) {
'use strict';
/* jshint camelcase: false */

var PARENT = wb.ui.PropertyEditTool.EditableValue;

/**
 * Serves the input interface for an items aliases, extends EditableValue.
 * @constructor
 * @extends wb.ui.PropertyEditTool.EditableValue
 * @since 0.1
 */
var SELF = wb.ui.PropertyEditTool.EditableAliases = wb.utilities.inherit( PARENT, {

	API_VALUE_KEY: 'aliases',

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue._options
	 */
	_options: $.extend( {}, PARENT.prototype._options, {
		inputHelpMessageKey: 'wikibase-aliases-input-help-message'
	} ),

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._init
	 *
	 * @param {jQuery} subject
	 * @param {Object} options
	 * @param {jQuery.wikibase.toolbar} toolbar
	 */
	_init: function( subject, options, toolbar ) {
		var newSubject = $( '<span>' );

		// Do not use $.replaceWith() since it drops widget data:
		$( this._subject ).after( newSubject );
		newSubject.append( $( this._subject ).detach() );

		// overwrite subject // TODO: really not that nice, is it?
		this._subject = newSubject;

		PARENT.prototype._init.call( this, newSubject, options, toolbar );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._interfaceHandler_onInputRegistered
	 *
	 * @param relatedInterface wikibase.ui.PropertyEditTool.EditableValue.Interface
	 */
	_interfaceHandler_onInputRegistered: function( relatedInterface ) {
		if( relatedInterface.isInEditMode() ) {
			PARENT.prototype._interfaceHandler_onInputRegistered.call( this, relatedInterface );

			// Always enable cancel button since it is alright to have an empty value:
			var editGroup = this._toolbar.$editGroup.data( 'toolbareditgroup' );
			editGroup.$btnCancel.data( 'wbbutton' ).enable();
		}
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._getValueFromApiResponse
	 */
	_getValueFromApiResponse: function( response ) {
		if ( response[ this.API_VALUE_KEY ]
			&& $.isArray( response[ this.API_VALUE_KEY ][ window.mw.config.get( 'wgUserLanguage' ) ] )
		) {
			var values = [];
			$.each( response[ this.API_VALUE_KEY ][ window.mw.config.get( 'wgUserLanguage' ) ], function( i, item ) {
				values.push( item.value );
			} );
			return values;
		} else {
			return null;
		}
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._setRevisionIdFromApiResponse
	 */
	_setRevisionIdFromApiResponse: function( response ) {
		wb.getRevisionStore().setAliasesRevision( response.lastrevid );
		return true;
	},

	/**
	 * Removes injected nodes in addition to parent's destroy routine.
	 *
	 * @see wikibase.ui.PropertyEditTool.EditableValue._destroy
	 */
	_destroy: function() {
		var originalSubject = this._subject.find( 'ul:first' );

		// div injected in this._buildInterfaces()
		this._subject.find( 'ul:first' ).parent().replaceWith( this._subject.find( 'ul:first' ) );

		// span injected in this._init()
		this._subject.replaceWith( this._subject.children() );

		this._subject = originalSubject;

		PARENT.prototype._destroy.call( this );
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
	showError: function( error, anchor ) {
		// EditableAliases has no "remove" button. However, when saving with an empty value, a
		// "remove" action is implied. But since there ist no "remove" button to attach an error
		// tooltip to, the "save" button shall be used even when a "remove" action has been
		// triggered.
		anchor = this._toolbar.$editGroup.data( 'toolbareditgroup' ).$btnSave.data( 'wbbutton' );
		PARENT.prototype.showError.call( this, error, anchor );
	},

	/**
	 * Calling the corresponding method in the wikibase.RepoApi
	 *
	 * @return {jQuery.Promise}
	 */
	queryApi: function() {
		return this._api.setAliases(
			mw.config.get( 'wbEntityId' ),
			wb.getRevisionStore().getAliasesRevision(),
			this._interfaces[0].getNewItems(),
			this._interfaces[0].getRemovedItems(),
			this.getValueLanguageContext()
		);
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.preserveEmptyForm
	 * @var bool
	 */
	preserveEmptyForm: false
} );

/**
 * @see wb.ui.PropertyEditTool.EditableValue.newFromDom
 */
SELF.newFromDom = function( subject, options, toolbar ) {
	var ev = wb.ui.PropertyEditTool.EditableValue,
		$subject = $( subject ),
		$interfaceParent = $( '<div/>' );

	options = options || {};
	options.valueLanguageContext =
		options.valueLanguageContext || ev.getValueLanguageContextFromDom( $subject );

	// Do not use $.replaceWith() since it drops widget data:
	$subject.filter( 'ul:first' ).after( $interfaceParent );
	$interfaceParent.append( $subject.filter( 'ul:first' ).detach() );

	var aliasesInterface = new ev.AliasesInterface( $interfaceParent );

	return new SELF( $interfaceParent, options, aliasesInterface );
};

}( mediaWiki, wikibase, jQuery ) );
