/**
 * JavasSript for managing editable representation of property values.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Manages several editable value pieces which act as converters between the pure html input and the
 * input interface. Also does the API call to store new and modify existing values as well as the
 * removal of stored values.
 * 
 * @param jQuery subject
 * @param wikibase.ui.Toolbar toolbar
 */
window.wikibase.ui.PropertyEditTool.EditableValue = function( subject, toolbar ) {
	if( typeof subject != 'undefined' && typeof toolbar != 'undefined' ) {
		this._init( subject, toolbar );
	}
};
window.wikibase.ui.PropertyEditTool.EditableValue.prototype = {
	/**
	 * @const
	 * Class which marks the element within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittool-editablevalue',
	
	/**
	 * Element representing the editable value. This element will either hold the value or the input
	 * box in case it is activated for edit.
	 * @var jQuery
	 */
	_subject: null,
	
	/**
	 * This is true if the input interface is initialized at the time.
	 * @var bool
	 */
	_isInEditMode: false,
	
	/**
	 * Holds the input element in case this is in edit mode
	 * @var null|jQuery
	 */
	_inputElem: null,
	
	/**
	 * The toolbar controling the editable value
	 * @var window.wikibase.ui.Toolbar
	 */
	_toolbar: null,
	
	/**
	 * If this is true, it means that the value has not been stored to the database at all. So if
	 * in edit mode and pressing cancel, the element will be removed
	 * @var bool
	 */
	_pending: false,
	
	/**
	 * Array holding all the interfaces which are part of the editable value.
	 * @var wikibase.ui.PropertyEditTool.EditableValue.Interface[]
	 */
	_interfaces: null,
	
	/**
	 * Initializes the editable value.
	 * This should normally be called directly by the constructor.
	 * 
	 * @param jQuery subject
	 * @param wikibase.ui.Toolbar toolbar shouldn't be initialized yet
	 */
	_init: function( subject, toolbar ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._subject = $( subject );
		this._pending = this._subject.hasClass( 'wb-pending-value' );
		
		this._initInterfaces();

		this._toolbar = toolbar;
		this._toolbar.appendTo( this._getToolbarParent() );
		
		if( this.isEmpty() || this.isPending() ) {
			// enable editing from the beginning if there is no value yet or pending value...
			this._toolbar.editGroup.btnEdit.doAction();
			this.removeFocus(); // ...but don't set focus there for now
		}
	},
	
	/**
	 * initializes the interfaces handled by this editable value.
	 */
	_initInterfaces: function() {
		var interfaces = this._buildInterfaces( this._subject );
		$.each( interfaces, $.proxy( function( index, elem ) {
			this._configSingleInterface( elem );
		}, this ) );
		this._interfaces = interfaces;
	},
	
	/**
	 * Function analysing the subject and splitting it into all the input interfaces needed by the
	 * editable value.
	 * 
	 * @return wikibase.ui.PropertyEditTool.EditableValue.Interface[]
	 */
	_buildInterfaces: function( subject ) {
		var interfaces = new Array();
		interfaces.push( new wikibase.ui.PropertyEditTool.EditableValue.Interface( subject, this ) );
		return interfaces;
	},
	
	/**
	 * Does the initialization for a single editable value interface. Basically this will bind the used
	 * events and set needed options.
	 * 
	 * @param wikibase.ui.PropertyEditTool.EditableValue.Interface singleInterface
	 */
	_configSingleInterface: function( singleInterface ) {
		var self = this;		
		singleInterface.onFocus = function( event ){ self._interfaceHandler_onFocus( singleInterface, event ); };
		singleInterface.onBlur = function( event ){ self._interfaceHandler_onBlur( singleInterface, event ); };
		singleInterface.onKeyPressed =
			function( event ) { self._interfaceHandler_onKeyPressed( singleInterface, event ); };
		singleInterface.onKeyUp = // ESC key does not react onKeyPressed but on onKeyUp
			function( event ) { self._interfaceHandler_onKeyPressed( singleInterface, event ); };
		singleInterface.onInputRegistered =
				function(){ self._interfaceHandler_onInputRegistered( singleInterface ); };
	},
	
	/**
	 * Returns the node the toolbar should be appended to
	 */
	_getToolbarParent: function() {
		return this._subject.parent();
	},
	
	/**
	 * Removes the value from the dom as well as from the data store via the API
	 */
	remove: function() {
		// TODO API call
		this.doApiCall( true );
		//this.destroy(); // no need to destroy this proberly since we remove anything for real! FIXME: really??
		this._subject.empty().remove();
		
		if( this.onAfterRemove !== null ) {
			this.onAfterRemove(); // callback
		}
	},

	destroy: function() {
		if( this._toolbar != null) {
			this._toolbar.destroy();
			this._toolbar = null;
		}
		this.stopEditing( false );
	},

	/**
	 * By calling this, the editable value will be made editable for the user.
	 * Call stopEditing() to save or cancel the editing process.
	 * Basically this initializes the input box as sub element of the subject and uses the
	 * elements content as initial text.
	 *
	 * @return bool will return false if edit mode is active already.
	 */
	startEditing: function() {
		if( this.isInEditMode() ) {
			return false;
		}
		this._isInEditMode = true;
		
		$.each( this._interfaces, function( index, elem ) {
			elem.startEditing();
		} );

		if ( this._toolbar.editGroup.tooltip !== null ) {
			/*
			FIXME: tooltip needs to recalculate its horizontal position after input elements have been placed inside
			the DOM; but show() has already been called on initialization, so the tooltip is marked as visible (which
			is necessary since the tooltip should be permanently shown on some occasions)
			 */
			this._toolbar.editGroup.tooltip.hide();
			this._toolbar.editGroup.tooltip.show( true );
		}

		return true;
	},

	/**
	 * Destroys the edit box and displays the original text or the inputs new value.
	 *
	 * @param bool save whether to save the new user given value
	 * @return bool whether the value has changed compared to the original value
	 */
	stopEditing: function( save ) {
		if( ! this.isInEditMode() ) {
			return false;
		}
		if( this.onStopEditing !== null && this.onStopEditing( save ) === false ) { // callback
			return false; // cancel
		}
		if( !save && this.isPending() ) {
			// not yet existing value, no state to go back to
			this.remove();
			return false;
			// do not call afterStopEditing() here!
		}
		
		var changed = false;
		
		$.each( this._interfaces, function( index, elem ) {
				changed = elem.stopEditing( save ) || changed;
		} );
		
		// out of edit mode after interfaces are converted back to HTML:
		this._isInEditMode = false;
		
		var wasPending = this.isPending();
		if( save ) {
			this.doApiCall( false );
			this._pending = false; // TODO: might have to move this to API call error/success handling when implemented
			this._subject.removeClass( 'wb-pending-value' );
		}
		
		if( this.afterStopEditing !== null && this.afterStopEditing( save, changed, wasPending ) === false ) { // callback
			return false; // cancel
		}
		
		// any change at all compared to initial value?
		return changed;
	},

	/**
	 * Sets the focus to the input interface
	 */
	setFocus: function() {
		if( this.isInEditMode() ) {
			this._interfaces[0].setFocus();
		}
	},

	/**
	 * Removes the focus from the input interface
	 */
	removeFocus: function() {
		if( this.isInEditMode() ) {
			this._interfaces[0].removeFocus();
		}
	},

	/**
	 * Does the actual API call
	 * @param bool removeValue whether to make the remove or the save call to the API
	 */
	doApiCall: function( removeValue ) {
		var apiCall = this.getApiCallParams( removeValue );
		
		mw.loader.using( 'mediawiki.api', jQuery.proxy( function() {
			var localApi = new mw.Api();
			localApi.post( apiCall, {
				ok: jQuery.proxy( this._apiCallOk, this ),
				err: jQuery.proxy( this._apiCallErr, this )
			} );
		}, this ) );
	},

	/**
	 * Returns the neccessary parameters for an api call to store the value.
	 * @return Object containing the API call specific parameters
	 */
	getApiCallParams: function() {
		return {};
	},

	/**
	 * handle return of successful API call
	 */
	_apiCallOk: function() {
		//console.log( arguments );
	},

	/**
	 * handle error of unsuccessful API call
	 */
	_apiCallErr: function() {
		//console.log( arguments );
	},

	/**
	 * Returns whether the input interface is loaded currently
	 *
	 * @return bool
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},
	
	/**
	 * Returns true if the value is in edit mode and not stored in the database yet.
	 * 
	 * @return bool
	 */
	isPending: function() {
		return this._pending;
	},

	/**
	 * Returns the current value
	 * // TODO: should return an object representing the properties value
	 *
	 * @return Array
	 */
	getValue: function() {
		var result = [];
		$.each( this._interfaces, function( index, elem ) {
			result.push( elem.getValue() );
		} );
		return result;
	},
	
	/**
	 * Sets a value
	 * // TODO: should take an object representing a properties value
	 * 
	 * @param Array|string value
	 */
	setValue: function( value ) {
		if( ! $.isArray( value ) ) {
			value = [ value ];
		}
		$.each( value, $.proxy( function( index, val ) {
			this._interfaces[ index ].setValue( val );
		}, this ) );
	},

	/**
	 * If the input is in edit mode, this will return the value active before the edit mode was entered.
	 * If its not in edit mode, the current value will be returned.
	 * 
	 * @return Array
	 */
	getInitialValue: function() {
		if( ! this.isInEditMode() ) {
			return this.getValue();
		}
		
		var result = [];
		
		$.each( this._interfaces, function( index, elem ) {
			result.push( elem.getInitialValue() );
		} );
		
		return result;
	},

	/**
	 * Returns a short information about how the input should be inserted by the user.
	 * 
	 * @return string
	 */
	getInputHelpMessage: function() {
		return '';
	},

	/**
	 * Returns true if there is currently no value assigned
	 *
	 * @return bool
	 */
	isEmpty: function() {
		for( var i in this._interfaces ) {
			if( ! this._interfaces[ i ].isEmpty() ) {
				return false;
			}
		}
		return true;
	},

	/**
	 * Velidates whether a certain value would be valid for this editable value.
	 *
	 * @todo: we might want to move this into a prototype describing the property/snak later.
	 *
	 * @param Array value
	 * @return bool
	 */
	validate: function( value ) {
		for( var i in value ) {
			var iInterface = this._interfaces[ i ];
			// don't validate if it isn't active since it should be valid anyhow
			// TODO re-evaluate better implementation
			if( iInterface.isActive() && ! iInterface.validate( value[ i ] ) ) {
				return false;
			}
		}
		return true;
	},
	
	/**
	 * Helper function to compares two values returned by getValue() or getInitialValue() as long as
	 * we work with arrays instead of proper objects here.
	 * When comparing the values, this will also do an normalization on the values before comparing
	 * them, so even though they are not exactly the same perhaps, they stillh ave the same meaning
	 * and true will be returned.
	 * 
	 * @todo: make this deprecated as soon as we use objects representing property values...
	 * 
	 * @param Array value1
	 * @param Array|null value2 if null, this will check whether value1 is empty
	 * @return bool
	 */
	valueCompare: function( value1, value2 ) {
		if( value1.length !== this._interfaces.length ) {
			return false; // there has to be one value for each interface!
		}

		if( value2 === null ) {
			// check for empty value1
			for( var i in value1 ) {
				if( $.trim( value1[ i ] ) !== '' ) {
					return false;
				}
			}
			return true;
		}

		// check for equal arrays with same entries in same order
		if( value1.length !== value2.length ) {
			return false;
		}
		for( var i in value1 ) {
			// normalize first:
			var val1 = this._interfaces[ i ].normalize( value1[ i ] );
			var val2 = this._interfaces[ i ].normalize( value2[ i ] );

			if( val1 !== val2 ) {
				return false;
			}
		}
		return true;
	},
	
	_interfaceHandler_onInputRegistered: function( relatedInterface ) {
		if( ! relatedInterface.isInEditMode() ) {
			return;
		}

		var value = this.getValue();
		var isInvalid = !this.validate( value );
		
		// can't save if invalid input OR same as before
		var disableSave = isInvalid || this.valueCompare( this.getInitialValue(), value );
		
		// can't cancel if empty before except the edit is pending (then it will be removed)
		var disableCancel = !this.isPending() && this.valueCompare( this.getInitialValue(), null );

		this._toolbar.editGroup.btnSave.setDisabled( disableSave );
		this._toolbar.editGroup.btnCancel.setDisabled( disableCancel );
	},

	_interfaceHandler_onKeyPressed: function( relatedInterface, event ) {
		if( event.which == 13 ) {
			this._toolbar.editGroup.btnSave.doAction();
		}
		else if( event.which == 27 ) {
			this._toolbar.editGroup.btnCancel.doAction();
		}
	},

	_interfaceHandler_onFocus: function( relatedInterface, event ) {
		this._toolbar.editGroup.tooltip.show( true );
	},
	_interfaceHandler_onBlur: function( relatedInterface, event ) {
		this._toolbar.editGroup.tooltip.hide();
	},
	
	///////////
	// EVENTS:
	///////////
	
	/**
	 * Callback called when the edit process is going to be ended. If the callback returns false, the
	 * process will be cancelled.
	 *
	 * @param bool save whether the result should be saved. If false, the editing will be cancelled
	 *        without saving.
	 * @return bool whether to go on with the stop editing.
	 * 
	 * @example function( save ) {return true}
	 */
	onStopEditing: null,
	
	/**
	 * Callback called after the editing process is finished. At this point the element is not in
	 * edit mode anymore.
	 * This will not be called in case the element was just created, still pending, and the editing
	 * process was cancelled.
	 * 
	 * @param bool saved whether the result will be saved. If true, the result is sent to the API
	 *        already and the internal value is changed to the new value.
	 * @param bool changed whether the value was changed during the editing process.
	 * @param bool wasPending whether the element was pending before the edit.
	 * 
	 * @example function( saved, changed, wasPending ) {return true}
	 */
	afterStopEditing: null,
	
	/**
	 * Callback called after the element was removed
	 */
	onAfterRemove: null
};
