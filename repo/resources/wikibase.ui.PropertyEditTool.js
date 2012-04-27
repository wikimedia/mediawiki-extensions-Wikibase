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
	 * @var wikibase.ui.PropertyEditTool.EditableValue[]
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
		this._toolbar.innerGroup = new window.wikibase.ui.PropertyEditTool.Toolbar.Group();
		this._toolbar.addElement( this._toolbar.innerGroup );
		
		if( this.allowsMultipleValues ) {
			// toolbar group for buttons:
			this._toolbar.lblFull = new window.wikibase.ui.PropertyEditTool.Toolbar.Label(
					'&nbsp;- ' + window.mw.msg( 'wikibase-propertyedittool-full' )
			);
			
			// only add 'add' button if we can have several values
			this._toolbar.btnAdd = new window.wikibase.ui.PropertyEditTool.Toolbar.Button( window.mw.msg( 'wikibase-add' ) );
			this._toolbar.btnAdd.onAction = $.proxy( function() {
				this.enterNewValue();
			}, this );
			
			this._toolbar.innerGroup.addElement( this._toolbar.btnAdd );
			
			// enable button only if this is not full yet, overwrite function directly	
			var self = this;
			this._toolbar.btnAdd.setDisabled = function( disable ) {
				var isFull = self.isFull();
				if( ! disable && self.isFull() ) {
					// full list, don't enable 'add' button, show hint
					self._toolbar.addElement( self._toolbar.lblFull );
					disable = true;
				}
				if( ! disable && self.isInAddMode() ) {					
					disable = true; // still adding new value, don't enable 'add' button!
				}
				if( disable == false ) {
					// enabled, label with 'full' message not required
					self._toolbar.removeElement( self._toolbar.lblFull );
				}
				return window.wikibase.ui.PropertyEditTool.Toolbar.Button.prototype.setDisabled.call( this, disable );
			};
			this._toolbar.btnAdd.setDisabled( false ); // will run the code above
		}
		
		this._toolbar.appendTo( this._getToolbarParent() );
	},
	
	/**
	 * Returns whether further values can be added
	 * 
	 * @return bool
	 */
	isFull: function() {
		if( this.allowsMultipleValues ) {
			return true;
		} else {
			return this._editableValues === null || this._editableValues.length < 1;
		}
	},
	
	/**
	 * Returns whether the tool is in edit mode currently. This is true if any of the values managed
	 * by this is in edit mode currently.
	 *
	 * @return bool
	 */
	isInEditMode: function() {
		// is in edit mode if any of the editable values is in edit mode
		for( var i in this._editableValues ) {
			if( this._editableValues[ i ].isInEditMode() ) {
				return true;
			}
		}
		return false;
	},
	
	/**
	 * Returns whether the tool is in edit mode for adding a new value right now.
	 * 
	 * @return bool
	 */
	isInAddMode: function() {
		// most likely that the last item is pending, so start to check there
		for( var i = this._editableValues.length; i--; i >= 0 ) {
			var editableValue = this._editableValues[ i ];
			if( editableValue.isInEditMode() && editableValue.isPending() ) {
				return true;
			}
		}
		return false;
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
   
   /**
	* Collects all values represented within the DOM already and initializes EditableValue instances
	* for them.
	*/
	_initEditToolForValues: function() {
		// gets the DOM nodes representing EditableValue
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
	 * 
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
		
		var self = this;
		editableValue.onAfterRemove = function() {
			self._editableValueHandler_onAfterRemove( editableValue );
		};
		
		this._editableValues.push( editableValue );		
		return editableValue;
	},
	
	/**
	 * Called whenever an editable value managed by this was removed.
	 */
	_editableValueHandler_onAfterRemove: function( editableValue ) {
		var elemIndex = this.getIndexOf( editableValue );			

		// remove EditableValue from list of managed values:
		this._editableValues.splice( elemIndex, 1 );

		if( elemIndex >= this._editableValues.length ) {
			elemIndex = -1; // element removed from end
		}	
		this._onRefreshView( elemIndex );
		
		// enables 'add' button again if it was disabled because of full list:
		this._toolbar.btnAdd.setDisabled( this.isInAddMode() );
	},
	
	/**
	 * returns the index of an EditableValue within this collection. If the element is not part of
	 * this, -1 will be returned
	 * 
	 * @param wikibase.ui.PropertyEditTool.EditableValue elem
	 * @return int
	 */
	getIndexOf: function( element ) {
		return $.inArray( element, this._editableValues );
	},
	
	/**
	 * Builds the toolbar for a single editable value
	 * 
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
	 * 
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
	 * 
	 * @return newValue wikibase.ui.PropertyEditTool.EditableValue
	 */
	enterNewValue: function() {
		var newValueElem = this._newEmptyValueDOM(); // get DOM for new empty value
		newValueElem.addClass( 'wb-pending-value' );
		
		this._subject.append( newValueElem );
		var newValue = this._initSingleValue( newValueElem );		
				
		this._toolbar.btnAdd.setDisabled( true ); // disable 'add' button...
		
		var self = this;
		newValue.afterStopEditing = function( save, changed, wasPending ) {
			self._newValueHandler_afterStopEditing( newValue, save, changed, wasPending );
			newValue.onStopEditing = null; // make sure handler is only called once!
		};		
		
		this._onRefreshView( this.getIndexOf( newValue ) );
		newValue.setFocus();
		return newValue;
	},
	
	/**
	 * Handler called only the first time a new value was added and saved or cancelled.
	 */
	_newValueHandler_afterStopEditing: function( newValue, save, changed, wasPending ) {
		this._toolbar.btnAdd.setDisabled( false ); // ...until stop editing new item		
	},
	
	/**
	 * Called when the view changes, for example if elements are removed or added in case this is a
	 * view allowing multiple values.
	 * 
	 * @param int fromIndex the index of the value in this._editableValues which triggered the
	 *        refresh request (because of insertion or deletion). This is -1 if an element was
	 *        removed at the end of the view.
	 */
	_onRefreshView: function( fromIndex ) {
		if( fromIndex < 0 ) {
			return; // element at the end was removed, no update requiredy
		}
		for( var i = fromIndex; i < this._editableValues.length; i++ ) {
			var isEven = ( i % 2 ) != 0;			
			this._editableValues[ i ]._subject
			.addClass( isEven ? 'even' : 'uneven' )
			.removeClass( isEven ? 'uneven' : 'even' );			
		};
	},
	
	/**
	 * Creates the DOM structure for a new empty value which can be appended to the list of values.
	 * 
	 * @return jQuery
	 */
	_newEmptyValueDOM: function() {
		return $( '<span/>' );
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
	 * 
	 * @return window.wikibase.ui.PropertyEditTool.EditableValue
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
