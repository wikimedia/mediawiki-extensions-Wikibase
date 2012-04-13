/**
 * JavasSript for toolbars for 'Wikibase' property editing.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.Toolbar.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 * @author Tobias Gritschacher
 */

/**
 * Gives basic edit toolbar functionality, serves the "[edit]" button as well as the "[cancel|save]"
 * buttons and other related stuff.
 * 
 * @param jQuery parent
 */
window.wikibase.ui.PropertyEditTool.Toolbar = function( parent ) {
	if( typeof parent != 'undefined' ) {
		this._init( parent );
	}
};
window.wikibase.ui.PropertyEditTool.Toolbar.prototype = {
	/**
	 * @const
	 * Class which marks the element within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittoolbar',
	
	/**
	 * @var jQuery
	 */
	_parent: null,
	
	/**
	 * @var jQuery
	 */
	_elem: null,
	
	/**
	 * @var Array
	 */
	_items: null,
	
	/**
	 * Initializes the edit toolbar for the given element.
	 * This should normally be called directly by the constructor.
	 * 
	 * @param parent the element holding the toolbar
	 */
	_init: function( parent ) {
		if( this._parent !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._items = new Array();
		this._parent = parent;
		this.draw(); // draw first to have toolbar wrapper
		this._initToolbar();
	},
	
	_initToolbar: function() {
		
	},
	
	/**
	 * Function for (re)rendering the element
	 */
	draw: function() {
		this._drawToolbar();
		this._drawToolbarElements();
	},
	
	/**
	 * Draws the toolbar element itself without its content
	 */
	_drawToolbar: function() {		
		if( this._elem !== null ) {
			this._elem.children().detach();
			this._elem.remove();
		}
		this._elem = $( '<div/>', {
			'class': this.UI_CLASS
		} );
		
		/*
		.append( "[" );
		
		for( var i in buttons ) {
			if( i != 0 ) {
				this._elem.append( "|" );
			}
			this._elem.append( buttons[i] );
		}		
		this._elem.append( "]" );
		*/
	   
		// if this is a right-to-left language, prepend the toolbar
		// FIXME: there might be a nicer way to check for this, also this might be language settings
		//        and context related later!
		if( $( 'body' ).hasClass( 'rtl' ) ) {
			this._parent.prepend( this._elem );
		} else {
			this._parent.append( this._elem );
		}
	},
	
	/**
	 * Draws the toolbar elements like buttons and labels
	 */
	_drawToolbarElements: function() {
		for( var i in this._items ) {
			this._elem.append( this._items[i]._elem );
		}
	},
	
	/**
	 * This will add a toolbar element, e.g. a label or a button to the toolbar at the given index.
	 */
	addElement: function( elem, index ) {
		// TODO: add index functionality!
		//this._elem.append( elem._elem );
		this._items.push( elem );		
		this.draw();
	},
	
	/**
	 * Removes an element from the toolbar
	 * @param elem the element to remove
	 * @return bool false if element isn't part of this element
	 */
	removeElement: function( elem ) {
		$index = $.inArray( elem, this._items );
		if( $index === -1 ) {
			return false;
		}
		this._items.splice( $index, 1 );
		
		//elem._elem.detach(); // only detach so it still can be attached somewhere else!
		// TODO check whether this is even part of the toolbar!
		this.draw();
	},

	destroy: function() {
		// TODO
	}
};

/**
 * Represents a group of toolbar elements within a toolbar
 */
window.wikibase.ui.PropertyEditTool.Toolbar.Group = function( editableValue ) {
	window.wikibase.ui.PropertyEditTool.Toolbar.call( this, editableValue );
};
window.wikibase.ui.PropertyEditTool.Toolbar.Group.prototype = new window.wikibase.ui.PropertyEditTool.Toolbar();
$.extend( window.wikibase.ui.PropertyEditTool.Toolbar.Group.prototype, {
	
	UI_CLASS: 'wb-ui-propertyedittoolbar-group',
	
	_drawToolbarElements: function() {
		for( var i in this._items ) {
			if( i != 0 ) {
				this._elem.append( '|' );
			}
			this._elem.append( this._items[i]._elem );
		}
		
		this._elem
		.prepend( '[' )
		.append( ']' );
	}
	
} );

/**
 * Extends the basic toolbar with buttons essential for editing stuff.
 * Basically '[edit]' which gets expanded to '[cancel|save]' when hit.
 * This also interacts with an editable value.
 */
window.wikibase.ui.PropertyEditTool.EditToolbar = function( editableValue ) {
	window.wikibase.ui.PropertyEditTool.Toolbar.call( this, editableValue );
};
window.wikibase.ui.PropertyEditTool.EditToolbar.prototype = new window.wikibase.ui.PropertyEditTool.Toolbar();
$.extend( window.wikibase.ui.PropertyEditTool.EditToolbar.prototype, {
	
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
	 * @param window.wikibase.ui.PropertyEditTool.EditableValue editableValue the editable value
	 *        the toolbar should interact with.
	 */
	_init: function( editableValue ) {
		// the toolbar is placed besides the editable value itself:
		var parent = editableValue._subject.parent();
		window.wikibase.ui.PropertyEditTool.Toolbar.prototype._init.call( this, parent );
		
		this._editableValue = editableValue;
	},	
	
	_initToolbar: function() {
		// call prototypes base function to append toolbar itself:
		window.wikibase.ui.PropertyEditTool.Toolbar.prototype._initToolbar.call( this );
		
		// now create the buttons we need for basic editing:
		var button = window.wikibase.ui.PropertyEditTool.Toolbar.Button;
		
		this.btnEdit = new button( window.mw.msg( 'wikibase-edit' ) );
		this.btnEdit.onAction = this._editActionHandler();
		
		this.btnCancel = new button( window.mw.msg( 'wikibase-cancel' ) );
		this.btnCancel.onAction = this._cancelActionHandler();

		this.btnSave = new button( window.mw.msg( 'wikibase-save' ) );
		this.btnSave.onAction = this._saveActionHandler();
		
		// add 'edit' button only for now:
		this.addElement( this.btnEdit );
	},
	
	_editActionHandler: function() {
		return $.proxy( function(){
			this._editableValue.startEditing();
			this.removeElement( this.btnEdit );
			this.addElement( this.btnCancel );
			this.addElement( this.btnSave );
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
		this.removeElement( this.btnCancel );
		this.removeElement( this.btnSave );
		this.addElement( this.btnEdit );		
	}
	
} );