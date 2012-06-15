/**
 * JavasSript for 'Wikibase' edit form for an items aliases
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.AliasesEditTool.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 * @author H. Snater
 */
"use strict";

/**
 * Module for 'Wikibase' extensions user interface functionality for editing an items aliases.
 *
 * @since 0.1
 */
window.wikibase.ui.AliasesEditTool = function( subject ) {
	window.wikibase.ui.PropertyEditTool.call( this, subject );
};

window.wikibase.ui.AliasesEditTool.prototype = new window.wikibase.ui.PropertyEditTool();
$.extend( window.wikibase.ui.AliasesEditTool.prototype, {
	/**
	 * Initializes the edit form for the aliases.
	 * This should normally be called directly by the constructor.
	 *
	 * @see wikibase.ui.PropertyEditTool._init
	 */
	_init: function( subject ) {
		// call prototypes _init():
		window.wikibase.ui.PropertyEditTool.prototype._init.call( this, subject );
		// add class specific to this ui element:
		this._subject.addClass( 'wb-ui-aliasesedittool' );

		$( this._toolbar.btnAdd ).on( 'action', $.proxy( function( event ) {
			this._toolbar.hide();
		}, this ) );

		// determine whether to show or hide add button
		$( this ).on( 'action', $.proxy( function( event ) {
			if ( this.getValues()[0].getValue()[0].length === 0 ) {
				this._toolbar.show();
				this._editableValues[0].destroy();
				this._editableValues[0]._subject.remove(); // subject will be re-created via add button
				this._editableValues = [];
			} else {
				this._toolbar.hide();
			}
		}, this ) );

		$( this ).trigger( 'action' ); // initialize state of add button toolbar

	},

	/**
	 * @see wikibase.ui.PropertyEditTool._initSingleValue
	 *
	 * @return wikibase.ui.PropertyEditTool.EditableValue
	 */
	_initSingleValue: function( valueElem ) {
		var editableValue = wikibase.ui.PropertyEditTool.prototype._initSingleValue.call( this, valueElem );

		$( editableValue ).on( 'afterStopEditing', $.proxy( function( event ) {
			if ( this.getValues().length === 0 ) {
				this._toolbar.show();
			}
		}, this ) );

		return editableValue;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._newEmptyValueDOM
	 *
	 * @return jQuery
	 */
	_newEmptyValueDOM: function() {
		return $( '<ul/>' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._buildSingleValueToolbar
	 *
	 * @return wikibase.ui.Toolbar
	 */
	_buildSingleValueToolbar: function( editableValue ) {
		var toolbar = window.wikibase.ui.PropertyEditTool.prototype._buildSingleValueToolbar.call( this, editableValue );

		$( toolbar._items[0] ).on( 'leave', $.proxy( function( event ) {
			$( this ).trigger( 'action' );
		}, this ) );

		$( toolbar._items[0].btnCancel ).on( 'action', $.proxy( function( event ) {
			$( this ).trigger( 'action' );
		}, this ) );

		return toolbar;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getValueElems
	 */
	_getValueElems: function() {
		return this._subject.children( '.wb-aliases-container:first' );
	},
	
	/**
	 * @see wikibase.ui.PropertyEditTool.getPropertyName
	 * 
	 * @return string 'label'
	 */
	getPropertyName: function() {
		return 'label';
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.getEditableValuePrototype
	 */
	getEditableValuePrototype: function() {
		return window.wikibase.ui.PropertyEditTool.EditableAliases;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.allowsMultipleValues
	 * @var bool
	 */
	allowsMultipleValues: false,

	/**
	 * @see wikibase.ui.PropertyEditTool.allowsFullErase
	 * @var bool
	 */
	allowsFullErase: true

} );

/**
 * Returns the basic DOM structure sufficient for a new wikibase.ui.AliasEditTool
 *
 * @return jQuery
 */
window.wikibase.ui.AliasesEditTool.getEmptyStructure = function() {
	return $(
		'<div class="wb-aliases">' +
			'<span class="wb-aliases-label">' + mw.message( 'wikibase-aliases-label' ).escaped() + '</span>' +
			'<ul class="wb-aliases-container"></ul>' +
		'</div>'
	);
};