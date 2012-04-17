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
		allValues.each( function() {
			self._initSingleValue( this );
		} );
	},
	
	/**
	 * Takes care of initialization of a single value
	 * @param jQuery valueElem
	 */
	_initSingleValue: function( valueElem ) {
		var editableValue = new ( this.getEditableValuePrototype() )();
		
		editableValue.inputPlaceholder = window.mw.msg( 'wikibase-' + this.getPropertyName() + '-edit-placeholder' );
		editableValue._init( valueElem );
		
		this._editableValues.push( editableValue );
	},
	
	/**
	 * Returns the nodes representing the properties values.
	 * @return jQuery|Array
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
