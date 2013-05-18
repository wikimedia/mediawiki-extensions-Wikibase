/**
 * JavaScript for managing editable representation of item labels.
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
var PARENT = wb.ui.PropertyEditTool.EditableValue,
	SELF;

/**
 * Serves the input interface for the label of an item, extends EditableValue.
 * @constructor
 * @extends wb.ui.PropertyEditTool.EditableValue
 * @since 0.1
 */
SELF = wb.ui.PropertyEditTool.EditableLabel = wb.utilities.inherit( PARENT, {
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.API_VALUE_KEY
	 */
	API_VALUE_KEY: 'labels',

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue._options
	 */
	_options: $.extend( {}, PARENT.prototype._options, {
		inputHelpMessageKey: 'wikibase-label-input-help-message'
	} ),

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._setRevisionIdFromApiResponse
	 */
	_setRevisionIdFromApiResponse: function( response ) {
		wb.getRevisionStore().setLabelRevision( response.lastrevid );
		return true;
	},

	/**
	 * Calling the corresponding method in the wikibase.RepoApi
	 *
	 * @return {jQuery.Promise}
	 */
	queryApi: function() {
		return this._api.setLabel(
			mw.config.get( 'wbEntityId' ),
			wb.getRevisionStore().getLabelRevision(),
			this.getValue().toString(),
			this.getValueLanguageContext()
		);
	}
} );

/**
 * @see wb.ui.PropertyEditTool.EditableValue.newFromDom
 */
SELF.newFromDom = function( subject, options, toolbar ) {
	var ev = wb.ui.PropertyEditTool.EditableValue,
		$subject = $( subject ),
		$interfaceParent = $subject.find( '.wb-value' ).first(),
		simpleInterface = new ev.Interface( $interfaceParent, {
			'inputPlaceholder': mw.msg( 'wikibase-label-edit-placeholder' ),
			'autoExpand': false
		} );

	options = options || {};
	options.valueLanguageContext =
		options.valueLanguageContext || ev.getValueLanguageContextFromDom( $interfaceParent );

	// TODO: get rid of this
	simpleInterface.normalize = function( value ) {
		value = ev.Interface.prototype.normalize.call( this, value );
		// make sure we don't ever allow two+ sequential spaces in an item's label:
		value = value.replace( /\s+/g, ' ' );
		return value;
	};

	return new SELF( $subject, options, simpleInterface, toolbar );
};

}( mediaWiki, wikibase, jQuery ) );
