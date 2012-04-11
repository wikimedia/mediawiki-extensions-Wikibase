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
window.wikibase.ui.HeadingEditTool = function( subject ) {
	window.wikibase.ui.PropertyEditTool.call( this, subject );
};

window.wikibase.ui.HeadingEditTool.prototype = new window.wikibase.ui.PropertyEditTool();
$.extend( window.wikibase.ui.HeadingEditTool.prototype, {	
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
	 * The toolbar for editing the properties value
	 * @var: window.wikibase.ui.PropertyEditTool.Toolbar
	 */
	_valueToolbar: null,
		
	/**
	 * Initializes the edit form for the given h1 with 'firstHeading' class, basically the page title.
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
	},
	
	_initEditToolForValue: function() {
		value = $( this._subject.children( 'h1.firstHeading span' )[0] );
		this._editableValue = new window.wikibase.ui.PropertyEditTool.EditableValue( value );
		
		// TODO: If we want a separate toolbar for the label, we have to append and group the toolbar
		//       with the actual value perhaps.
		this._valueToolbar = new window.wikibase.ui.PropertyEditTool.Toolbar( this._subject );
		
		// use toolbar events to control the editable value:
		var self = this;
		this._valueToolbar.onActionEdit   = function(){ self._editableValue.startEditing(); };
		this._valueToolbar.onActionSave   = function(){ self._editableValue.stopEditing( true ); };
		this._valueToolbar.onActionCancel = function(){ self._editableValue.stopEditing( false ); };
	},
	
	/**
	 * Returns 'label'
	 */
	getPropertyName: function() {
		return 'label';
	}
} );
