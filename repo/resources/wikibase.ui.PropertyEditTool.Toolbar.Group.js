/**
 * JavasSript for 'Wikibase' property edit tool toolbar groups
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.Toolbar.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */

/**
 * Represents a group of toolbar elements within a toolbar
 */
window.wikibase.ui.PropertyEditTool.Toolbar.Group = function( elements ) {
	window.wikibase.ui.PropertyEditTool.Toolbar.call( this, elements );
};
window.wikibase.ui.PropertyEditTool.Toolbar.Group.prototype = new window.wikibase.ui.PropertyEditTool.Toolbar();
$.extend( window.wikibase.ui.PropertyEditTool.Toolbar.Group.prototype, {
	
	UI_CLASS: 'wb-ui-propertyedittoolbar-group',
	
	/**
	 * @param Array elements collection of elements within the group. This can be a empty array as well,
	 *        further elements can be added with addElement().
	 */
	_init: function( elements ) {
		window.wikibase.ui.PropertyEditTool.Toolbar.prototype._init.call( this, null );
		if( $.isArray( elements ) ) {
			this._items = elements;
		}
	},	
	
	_drawToolbar: function() {
		if( this._elem === null ) {
			// create outer div for group only the first time
			this._elem = $( '<div/>', {
				'class': this.UI_CLASS
			} );
		}
		else {
			// empty content of the group but keep group since it might be attached to a toolbar alreaedy!
			this._elem.children().detach();
			this._elem.empty();
		}
	},
	
	_drawToolbarElements: function() {
		for( var i in this._items ) {
			if( this.displayGroupSeparators && i != 0 ) {
				this._elem.append( '|' );
			}
			this._elem.append( this._items[i]._elem );
		}
		
		if( this.displayGroupSeparators ) {
			this._elem
			.prepend( '[' )
			.append( ']' );
		}
	},
	
	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * Defines whether the group should be displayed with separators "|" between ach items. In that case
	 * everything will also be wrapped within "[" and "]".
	 * @var bool
	 */
	displayGroupSeparators: true
} );

/**
 * Extends the basic toolbar group element with buttons essential for editing stuff.
 * Basically '[edit]' which gets expanded to '[cancel|save]' when hit.
 * This also interacts with a given editable value.
 */
window.wikibase.ui.PropertyEditTool.Toolbar.EditGroup = function( editableValue ) {
	window.wikibase.ui.PropertyEditTool.Toolbar.Group.call( this, editableValue );
};
window.wikibase.ui.PropertyEditTool.Toolbar.EditGroup.prototype = new window.wikibase.ui.PropertyEditTool.Toolbar.Group();
$.extend( window.wikibase.ui.PropertyEditTool.Toolbar.EditGroup.prototype, {
	
	/**
	 * @var window.wikibase.ui.PropertyEditTool.Toolbar.Button
	 */
	btnEdit: null,
	
	/**
	 * @var window.wikibase.ui.PropertyEditTool.Toolbar.Button
	 */
	btnCancel: null,
	
	/**
	 * @var window.wikibase.ui.PropertyEditTool.Toolbar.Button
	 */
	btnSave: null,
	
	/**
	 * @var window.wikibase.ui.PropertyEditTool.EditableValue
	 */
	_editableValue: null,

	/**
	 * @var window.wikibase.ui.PropertyEditTool.Toolbar.Tooltip
	 */
	tooltip: null,
	
	innerGroup: null,
	
	/**
	 * @param window.wikibase.ui.PropertyEditTool.EditableValue editableValue the editable value
	 *        the toolbar should interact with.
	 */
	_init: function( editableValue ) {
		window.wikibase.ui.PropertyEditTool.Toolbar.Group.prototype._init.call( this );
		this._editableValue = editableValue;
	},	
	
	_initToolbar: function() {
		// call prototypes base function to append toolbar itself:
		window.wikibase.ui.PropertyEditTool.Toolbar.prototype._initToolbar.call( this );
		
		// create a group inside the group so we can separate the tooltip visually
		this.innerGroup = new window.wikibase.ui.PropertyEditTool.Toolbar.Group( null );
		this.addElement( this.innerGroup );
		
		this.tooltip = new window.wikibase.ui.PropertyEditTool.Toolbar.Tooltip( 'specific message (to be inserted)' );
		
		// now create the buttons we need for basic editing:
		var button = window.wikibase.ui.PropertyEditTool.Toolbar.Button;
		
		this.btnEdit = new button( window.mw.msg( 'wikibase-edit' ) );
		this.btnEdit.onAction = this._editActionHandler();
		
		this.btnCancel = new button( window.mw.msg( 'wikibase-cancel' ) );
		this.btnCancel.onAction = this._cancelActionHandler();

		this.btnSave = new button( window.mw.msg( 'wikibase-save' ) );
		this.btnSave.onAction = this._saveActionHandler();
		
		// add 'edit' button only for now:
		this.innerGroup.addElement( this.btnEdit );
	},
	
	_editActionHandler: function() {
		return $.proxy( function(){
			this._editableValue.startEditing();
			this.addElement( this.tooltip, 0 ); // add tooltip before edit commands
			this.innerGroup.removeElement( this.btnEdit );
			this.innerGroup.addElement( this.btnSave );
			this.innerGroup.addElement( this.btnCancel );
			//this.tooltip.show();
		}, this );
	},	
	_cancelActionHandler: function() {
		return $.proxy( function() {
			this._leaveAction( false );
		}, this );
	},
	_saveActionHandler: function() {
		return $.proxy( function() {
			this._leaveAction( true );
		}, this );
	},
	
	_leaveAction: function( save ) {
		this._editableValue.stopEditing( save );
		this.removeElement( this.tooltip );
		this.innerGroup.removeElement( this.btnSave );
		this.innerGroup.removeElement( this.btnCancel );
		this.innerGroup.addElement( this.btnEdit );
	},
	
	/**
	 * @see window.wikibase.ui.PropertyEditTool.Toolbar.Group.displayGroupSeparators
	 */	
	displayGroupSeparators: false
} );
