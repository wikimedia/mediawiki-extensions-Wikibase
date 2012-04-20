/**
 * JavasSript for 'Wikibase' edit forms
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
"use strict";

/**
 * Module for 'Wikibase' extensions user interface functionality.
 */
window.wikibase.ui.PropertyEditTool = function( subject ) {
	if( typeof subject != 'undefined' ) {
		this._init( subject );
	}
};
window.wikibase.ui.PropertyEditTool.prototype = {
	/**
	 * @const
	 * Class which marks a edit tool ui within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittool',
	
	/**
	 * Element the edit tool is related to.
	 * @var jQuery
	 */
	_subject: null,
	
	/**
	 * Contains the toolbar for the edit tool itself, not for its values or null if it doesn't have
	 * one.
	 * @var wikibase.ui.PropertyEditTool.Toolbar
	 */
	_toolbar: null,
	
	/**
	 * The editable value for the properties data value
	 * @var wikibase.ui.PropertyEditTool.EditableValue
	 */
	_editableValues: null,
		
	/**
	 * Initializes the edit form for the given element.
	 * This should normally be called directly by the constructor.
	 */
	_init: function( subject ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._editableValues = new Array();
		
		this._subject = $( subject );
		this._subject.addClass( this.UI_CLASS + '-subject' );
				
		this._initEditToolForValues();
		this._initToolbar();
	},
	
	/**
	 * Initializes a toolbar for the whole property edit tool. By default this is just a command
	 * to add more values.
	 * @return wikibase.ui.PropertyEditTool.Toolbar
	 */
	_initToolbar: function() {
		this._toolbar = new window.wikibase.ui.PropertyEditTool.Toolbar();
		this._toolbar.renderItemSeparators = true;
		
		if( this.allowsMultipleValues ) {
			// only add 'add' button if we can have several values
			this._toolbar.btnAdd = new window.wikibase.ui.PropertyEditTool.Toolbar.Button( window.mw.msg( 'wikibase-add' ) );
			this._toolbar.btnAdd.onAction = $.proxy( function() {
				this.enterNewValue();
			}, this );

			this._toolbar.addElement( this._toolbar.btnAdd );
		}
		
		this._toolbar.appendTo( this._getToolbarParent() );
	},
	
	/**
	 * Returns the node the toolbar should be appended to
	 * @return jQuery
	 */
	_getToolbarParent: function() {
		return this._subject;
	},
	
	/*
	 * @todo: not decided yet whether this should be implemented. This would be neded if
	 *        label and value can be editied parallel, not if both get their own "edit"
	 *        button though (in this case other stuff has to be refactored probably).
	 */	/*
	_initEditToolForLabel: function() {
		//this._editableLabel = ...
	},
	*/
   
	_initEditToolForValues: function() {
		var allValues = this._getValueElems();
		
		if( ! this.allowsMultipleValues ) {
			allValues = $( allValues[0] );
		}
		
		var self = this;
		$.each( allValues, function( index, item ) {
			self._initSingleValue( item );
		} );
	},
	
	/**
	 * Takes care of initialization of a single value
	 * @param jQuery valueElem
	 * @return wikibase.ui.PropertyEditTool.EditableValue the initialized value
	 */
	_initSingleValue: function( valueElem ) {
		var editableValue = new ( this.getEditableValuePrototype() )();
		
		// message to be displayed for empty input:
		editableValue.inputPlaceholder = window.mw.msg( 'wikibase-' + this.getPropertyName() + '-edit-placeholder' );
		
		var editableValueToolbar = this._buildSingleValueToolbar( editableValue );
		
		// initialiye editable value and give appropriate toolbar on the way:
		editableValue._init( valueElem, editableValueToolbar );
		
		this._editableValues.push( editableValue );		
		return editableValue;
	},
	
	/**
	 * Builds the toolbar for a single editable value
	 * @return wikibase.ui.PropertyEditTool.Toolbar
	 */
	_buildSingleValueToolbar: function( editableValue ) {
		var toolbar = new window.wikibase.ui.PropertyEditTool.Toolbar();
		
		// give the toolbar a edit group with basic edit commands:
		var editGroup = new window.wikibase.ui.PropertyEditTool.Toolbar.EditGroup();
		editGroup.displayRemoveButton = this.allowsMultipleValues; // remove button if we have a list
		editGroup._init( editableValue );
		
		toolbar.addElement( editGroup );
		toolbar.editGroup = editGroup; // remember this
		
		return toolbar;
	},
	
	/**
	 * Returns the nodes representing the properties values. This can also return an array of jQuery
	 * objects if the value is represented by several nodes not sharing a mutual parent.
	 * @return jQuery|jQuery[]
	 */
	_getValueElems: function() {
		return this._subject.children( '.wb-property-container-value' );
	},
	
	destroy: function() {
		if ( this._editableValue != null ) {
			//this._editableValue.destroy();
		}
	},
	
	/**
	 * Allows to enter a new value, the input interface will be available but the process can still
	 * be cancelled.
	 */
	enterNewValue: function() {
		
	},
	
	/**
	 * Returns the related properties title
	 *
	 * @todo: perhaps at a later point we want to have a getProperty() method instead to return
	 *        a proper object describing the property. Also considering different kinds of snaks.
	 * 
	 * @var string
	 */
	getPropertyName: function() {
		return $( this._subject.children( '.wb-property-container-key' )[0] ).attr( 'title' );
	},

	/**
	 * defines which editable value should be used for this.
	 * @return Object
	 */
	getEditableValuePrototype: function() {
		return window.wikibase.ui.PropertyEditTool.EditableValue;
	},
	
	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * If true, the tool will manage several editable values and offer a remove and add command
	 * @var bool
	 */
	allowsMultipleValues: true
};
