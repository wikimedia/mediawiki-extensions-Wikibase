/**
 * JavaScript for 'Wikibase' edit form for an items aliases
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
'use strict';
var PARENT = wb.ui.PropertyEditTool;

/**
 * Module for 'Wikibase' extensions user interface functionality for editing an items aliases.
 * @constructor
 * @since 0.1
 */
wb.ui.AliasesEditTool = wb.utilities.inherit( PARENT , {
	/**
	 * Initializes the edit form for the aliases.
	 * This should normally be called directly by the constructor.
	 *
	 * @see wb.ui.PropertyEditTool._init
	 */
	_init: function( subject, options ) {
		var self = this;

		// setting default options
		options = $.extend( {}, PARENT.prototype._options, {
			/**
			 * @see wikibase.ui.PropertyEditTool.allowsMultipleValues
			 */
			allowsMultipleValues: false,

			/**
			 * @see wikibase.ui.PropertyEditTool.allowsFullErase
			 */
			allowsFullErase: true
		} );

		// call PropertyEditTool's init():
		PARENT.prototype._init.call( this, subject, options );

		// add class specific to this ui element:
		this._subject.addClass( 'wb-ui-aliasesedittool' );

		$( this._toolbar.$btnAdd ).on( 'wbbuttonaction', function( event ) {
			// Hide add button when hitting it since edit mode toolbar will appear.
			self._toolbar.hide();
		} );

		if( this._editableValues.length > 0 && this._editableValues[0].getValue().length > 0 ) {
			this._toolbar.hide(); // hide add button if there are any aliases
		}

		// Very special handling of special AliasesEditTool on special case when no aliases are
		// defined and edit mode is triggered:
		$( wikibase ).on(
			'startItemPageEditMode',
			$.proxy(
				function( event, origin ) {
					if ( !this.getOption( 'allowsMultipleValues' ) ) {
						if (
							this instanceof wikibase.ui.AliasesEditTool &&
							origin instanceof wikibase.ui.PropertyEditTool.EditableAliases
						) {
							this._subject.addClass( this.UI_CLASS + '-ineditmode' );
						}
					}
				}, this
			)
		);

	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._getValuesParent
	 *
	 * @return jQuery
	 */
	_getValuesParent: function() {
		// grid layout helper constructed in the init() function
		return this._subject.children( '.wb-gridhelper:first' );
	},

	/**
	 * @see wb.ui.PropertyEditTool._initSingleValue
	 *
	 * @param {jQuery} valueElem
	 * @param {Object} [options]
	 * @return {wb.ui.PropertyEditTool.EditableValue}
	 */
	_initSingleValue: function( valueElem, options ) {
		var editableValue = wb.ui.PropertyEditTool.prototype._initSingleValue.apply( this, arguments );

		// show add button when leaving edit mode without having any aliases at all
		$( editableValue ).on( 'afterStopEditing', $.proxy( function( event ) {
			if ( this.getValues().length === 0 ) {
				this._toolbar.show();
			}
		}, this ) );

		return editableValue;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._newEmptyValueDOM()
	 *
	 * @return {jQuery}
	 */
	_newEmptyValueDOM: function() {
		return mw.template( 'wb-aliases', '' );
	},

	/**
	 * @see wb.ui.PropertyEditTool._buildSingleValueToolbar
	 */
	_buildSingleValueToolbar: function( options ) {
		var self = this,
			toolbar = PARENT.prototype._buildSingleValueToolbar.call( this, options );

		// determine whether to show or hide the add button when cancelling edit mode
		$( toolbar.$editGroup.data( 'toolbareditgroup' ).$btnCancel ).on(
			'wbbuttonaction',
			function( event ) {
				if ( self.getValues()[0].getValue()[0].length === 0 ) { // no aliases at all
					self._toolbar.show();
					self._editableValues[0].destroy();
					self._editableValues[0].getSubject().remove(); // subject will be re-created via add button
					self._editableValues = [];
				} else {
					self._toolbar.hide();
				}
			}
		);

		return toolbar;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getValueElems
	 *
	 * @return jQuery
	 */
	_getValueElems: function() {
		return this._subject.find( '.wb-aliases-container:first' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.getPropertyName
	 *
	 * @return string 'aliases'
	 */
	getPropertyName: function() {
		return 'aliases';
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.getEditableValuePrototype
	 *
	 * @return wikibase.ui.PropertyEditTool.EditableAliases
	 */
	getEditableValuePrototype: function() {
		return wikibase.ui.PropertyEditTool.EditableAliases;
	}

} );

/**
 * Returns the basic DOM structure sufficient for a new wikibase.ui.AliasesEditTool
 * @static
 *
 * @return {jQuery}
 */
wb.ui.AliasesEditTool.getEmptyStructure = function() {
	return mw.template(
		'wb-aliases-wrapper', '', '', mw.message( 'wikibase-aliases-label' ).escaped(), ''
	);
};

} )( mediaWiki, wikibase, jQuery );
