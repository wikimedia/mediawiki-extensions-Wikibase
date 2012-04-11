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
	_editableValue: null,
		
	/**
	 * Initializes the edit form for the given element.
	 * This should normally be called directly by the constructor.
	 */
	_init: function( subject ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._subject = $( subject );
		this._subject.addClass( this.UI_CLASS + '-subject' );
				
		this._initEditToolForValue();
		//this._removeEditToolForLabel( true );
		
	},
	
	/**
	 * @todo: not decided yet whether this should be implemented. This would be neded if
	 *        label and value can be editied parallel, not if both get their own "edit"
	 *        button though (in this case other stuff has to be refactored probably).
	 */	
	_initEditToolForLabel: function() {
		//this._editableLabel = ...
	},
	
	_initEditToolForValue: function() {
		value = $( this._subject.children( '.wb-property-container-value' )[0] );
		this._editableValue = new window.wikibase.ui.PropertyEditTool.EditableValue( value );
		
		// TODO: If we want a separate toolbar for the label, we have to append and group the toolbar
		//       with the actual value perhaps.
		this._valueToolbar = new window.wikibase.ui.PropertyEditTool.Toolbar( this._subject );
		
		// use toolbar events to control the editable value:
		this._valueToolbar.onActionEdit   = function(){ this._editableValue.startEditing(); };
		this._valueToolbar.onActionSave   = function(){ this._editableValue.stopEditing( true ); };
		this._valueToolbar.onActionCancel = function(){ this._editableValue.stopEditing( false ); };
	},
	
	destroy: function() {
		//this._editableLabel.destroy();
		this._editableValue.destroy();
		// TODO
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
		return this._subject.children( '.wb-property-container-key' )[0].title();
	}
};
