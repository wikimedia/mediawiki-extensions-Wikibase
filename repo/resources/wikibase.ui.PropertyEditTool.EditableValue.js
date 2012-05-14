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
	 * @const
	 * Actions for doApiAction()
	 * @enum number
	 */
	API_ACTION: {
		SAVE: 1,
		REMOVE: 2,
		/**
		 * A save action which will trigger a remove, the actual difference to a real remove is how this action is
		 * handled in the interface
		 */
		SAVE_TO_REMOVE: 3
	},
	
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
		var tbParent = this._getToolbarParent();
		this._toolbar.appendTo( tbParent );
		tbParent.addClass( this.UI_CLASS + '-toolbarparent' );

		var indexParent = this._getIndexParent();
		if( indexParent ) {
			indexParent.addClass( this.UI_CLASS + '-index' );
		}
		
		if( this.isEmpty() || this.isPending() ) {
			// enable editing from the beginning if there is no value yet or pending value...
			this._toolbar.editGroup.btnEdit.doAction();
			this.removeFocus(); // ...but don't set focus there for now
		}
	},

	_setIndex: function( index ){

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
		singleInterface.onKeyUp = // ESC key does not react onKeyPressed but on onKeyUp
			function( event ) { self._interfaceHandler_onKeyUp( singleInterface, event ); };
		singleInterface.onKeyPressed =
			function( event ) { self._interfaceHandler_onKeyPressed( singleInterface, event ); };
		singleInterface.onInputRegistered =
				function(){ self._interfaceHandler_onInputRegistered( singleInterface ); };
	},
	
	/**
	 * Returns the node the toolbar should be appended to
	 *
	 * @return jQuery
	 */
	_getToolbarParent: function() {
		return this._subject.parent();
	},

	/**
	 * Returns the node reserved for the text expressing which index this editable value has
	 *
	 * @return jQuery|null
	 */
	_getIndexParent: function() {
		return null;
	},

	/**
	 * Removes all traces of this ui element from the DOM, so the represented value is still visible but not interactive
	 * anymore.
	 */
	destroy: function() {
		this.stopEditing( false );
		if( this._toolbar != null) {
			this._toolbar.destroy();
			this._toolbar = null;
		}
	},

	/**
	 * Removes the value from the data store via the API. Also removes the values representation from the dom stated
	 * differently.
	 *
	 * @param bool preserveEmptyForm allows to preserve the empty form so a new value can be entered immediately.
	 */
	remove: function( preserveEmptyForm ) {
		var degrade = $.proxy( function() {
			if( ! preserveEmptyForm ) {
				// remove value totally
				this.destroy();
				this._subject.empty().remove();
				if( this.onAfterRemove !== null ) {
					this.onAfterRemove(); // callback
				}
			} else {
				// delete value but keep empty input form
				this._reTransform( true );
				this.startEditing();
				this.removeFocus(); // don't want the focus immediately after removing the value
			}
		}, this );

		if( this.isPending() ) {
			// no API call necessary since value hasn't been stored yet.
			degrade();
		} else {
			var action = preserveEmptyForm ? this.API_ACTION.SAVE_TO_REMOVE : this.API_ACTION.REMOVE;
			this.performApiAction( action )
			.then( $.proxy( degrade, this ) );
		}
	},

	/**
	 * Saves the current value by sending it to the server. In case the current value is invalid, this will trigger a
	 * remove instead but will preserve the form to insert a new value.
	 */
	save: function( afterSaveComplete ) {
		if( ! this.isValid() ) {
			// remove instead! Save equals remove in this case!
			return this.remove( true );
		}

		this.performApiAction( this.API_ACTION.SAVE )
		.then( $.proxy( function( response ) {
			var wasPending = this.isPending();
			this._reTransform( true );

			this._pending = false; // not pending anymore after saved once
			this._subject.removeClass( 'wb-pending-value' );

			if( this.onAfterStopEditing !== null && this.onAfterStopEditing( true, wasPending ) === false ) { // callback
				return false; // cancel
			}

			afterSaveComplete && afterSaveComplete(); // callback if defined
		}, this ) );
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
		this._subject.addClass( this.UI_CLASS + '-ineditmode' );

		$.each( this._interfaces, function( index, elem ) {
			elem.startEditing();
		} );

		return true;
	},

	/**
	 * Destroys the edit box and displays the original text or the inputs new value.
	 *
	 * @param bool save whether to save the new user given value
	 * @param function afterSaveComplete function to be called after saving has been performed
	 * @return bool whether the value has changed (or was removed) in which case the changes are on their way to the API
	 */
	stopEditing: function( save, afterSaveComplete ) {
		if( typeof afterSaveComplete == 'undefined' ) {
			afterSaveComplete = function() {};
		}

		if( ! this.isInEditMode() ) {
			return false;
		}
		if( this.onStopEditing !== null && this.onStopEditing( save ) === false ) { // callback
			return false; // cancel
		}

		if( ! save && this.isPending() ) {
			// cancel pending edit...
			this._reTransform( save );
			this.remove(); // not yet existing value, no state to go back to
			return false; // do not call onAfterStopEditing() here!
		}

		if( ! save ) {
			//cancel...
			var wasPending = this._reTransform( save );

			if( this.onAfterStopEditing !== null && this.onAfterStopEditing( save, wasPending ) === false ) { // callback
				return false; // cancel
			}
			afterSaveComplete();
		}
		else {
			// save...
			this.save( afterSaveComplete );
		}

		return save;
	},

	/**
	 * remove input elements from DOM
	 *
	 * @param bool whether the state was changed
	 */
	_reTransform: function( save ) {
		if( ! this.isInEditMode() ) {
			return false;
		}
		$.each( this._interfaces, function( index, elem ) {
			elem.stopEditing( save );
		} );

		this._toolbar.editGroup.btnSave.removeTooltip();

		this._isInEditMode = false; // out of edit mode after interfaces are converted back to HTML
		this._subject.removeClass( this.UI_CLASS + '-ineditmode' );

		return true;
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
	 * Performs one of the actions available in the this.API_ACTION enum and handles all API related stuff.
	 *
	 * @param number apiAction see this.API_ACTION enum for all available actions
	 * @return jQuery.Deferred
	 */
	performApiAction: function( apiAction ) {
		var api = new mw.Api();
		var apiCall = this.getApiCallParams( apiAction );

		// we have to build our own deferred since the jqXHR object returned by api.proxy() is just referring to the
		// success of the ajax call, not to the actual success of the API request (which could have failed depending on
		// the return value).
		var deferred = $.Deferred();
		var self = this;

		var waitMsg = $( '<span/>', {
			'class': this.UI_CLASS + '-waitmsg',
			'text': mw.msg( 'wikibase-' + ( apiAction === this.API_ACTION.REMOVE ? 'remove' : 'save' ) + '-inprogress' )
		} )
		.appendTo( this._getToolbarParent() ).hide();

		deferred
		.then( function() {
			// fade out wait text
			waitMsg.fadeOut( 400, function() {
				self._subject.removeClass( self.UI_CLASS + '-waiting' );
				waitMsg.remove(); self._toolbar._elem.fadeIn( 300 ); }
			);
		} )
		.fail( function( textStatus, response ) {
			// remove and show immediately since we need nodes for the tooltip!
			self._subject
			.removeClass( self.UI_CLASS + '-waiting' )
			.addClass( self.UI_CLASS + '-aftereditnotify' );

			waitMsg.remove();
			self._toolbar._elem.show();
			self._apiCallErr( textStatus, response, apiAction );
		} );

		this._toolbar._elem.fadeOut( 200, function() {
			waitMsg.fadeIn( 200 );

			// do the actual API request and tritter jQuery.Deferred stuff:
			api.post( apiCall, {
				ok: function( textStatus ) {
					deferred.resolve( textStatus );
				},
				err: function( textStatus, response, exception ) {
					deferred.reject( textStatus, response, exception );
				}
			} );

		} );
		this._subject.addClass( this.UI_CLASS + '-waiting' );

		return deferred;
	},

	/**
	 * Returns the neccessary parameters for an api call to store the value.
	 *
	 * @param number apiAction see this.API_ACTION enum for all available actions
	 * @return Object containing the API call specific parameters
	 */
	getApiCallParams: function( apiAction ) {
		return {};
	},

	/**
	 * handle return of successful API call
	 *
	 * @param object JSON response
	 */
	_apiCallOk: function( response ) {
		if ( typeof response.success == 'undefined' ) { // out-of-scope error
			this._apiCallErr( 'unknown-error', response );
		}
	},

	/**
	 * handle error of unsuccessful API call
	 *
	 * @param string textStatus
	 * @param object JSON response
	 * @param number apiAction see this.API_ACTION enum
	 */
	_apiCallErr: function( textStatus, response, apiAction ) {
		var error = {};
		if ( textStatus != 'abort' ) {
			error = {
				code: 'unknown-error',
				shortMessage: ( apiAction === this.API_ACTION.REMOVE )
					? window.mw.msg( 'wikibase-error-remove-connection' )
					: window.mw.msg( 'wikibase-error-save-connection' ),
				message: ''
			};
			if ( typeof response.error != 'undefined' ) {
				if ( textStatus == 'timeout' ) {
					error.code = textStatus;
					error.shortMessage = ( apiAction === this.API_ACTION.REMOVE )
						? window.mw.msg( 'wikibase-error-remove-timeout' )
						: window.mw.msg( 'wikibase-error-save-timeout' );
				} else {
					error.code = response.error.code;
					error.shortMessage = ( apiAction === this.API_ACTION.REMOVE )
						? window.mw.msg( 'wikibase-error-remove-generic' )
						: window.mw.msg( 'wikibase-error-save-generic' );
					error.message = response.error.info;
				}
			}
		}
		this.showError( error, apiAction );
	},

	/**
	 * custom method to handle UI presentation of API errors
	 *
	 * @param object error
	 * @param number apiAction see this.API_ACTION enum
	 */
	showError: function( error, apiAction ) {
		// attach error tooltip to save button
		var btn = ( apiAction === this.API_ACTION.REMOVE )
			? this._toolbar.editGroup.btnRemove
			: this._toolbar.editGroup.btnSave;
		btn.addTooltip( new window.wikibase.ui.Tooltip( btn._elem, error, { gravity: 'nw' }, btn ) );
		btn.tooltip.showMessage( true );
		this.setFocus(); // re-focus input
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
	 * @return Array value but normalized
	 */
	setValue: function( value ) {
		if( ! $.isArray( value ) ) {
			value = [ value ];
		}
		$.each( value, $.proxy( function( index, val ) {
			this._interfaces[ index ].setValue( val );
		}, this ) );

		return this.getValue(); // will return value but normalized
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
	 * Returns whether the current value is valid
	 *
	 * @return bool
	 */
	isValid: function() {
		return this.validate( this.getValue() );
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
		
		// can't save if invalid input (except it is empty, in that case save == remove) OR same as before
		var disableSave = ( isInvalid && !this.isEmpty() ) || this.valueCompare( this.getInitialValue(), value );
		
		// can't cancel if empty before except the edit is pending (then it will be removed)
		var disableCancel = !this.isPending() && this.valueCompare( this.getInitialValue(), null );

		this._toolbar.editGroup.btnSave.setDisabled( disableSave );
		this._toolbar.editGroup.btnCancel.setDisabled( disableCancel );
	},

	/**
	 * interface's onKeyUp event handler
	 * (ESC key does not react on onKeyPressed)
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue.Interface interface
	 * @param jQuery.Event event
 	 */
	_interfaceHandler_onKeyUp: function( relatedInterface, event ) {
		if( event.which == 27 ) { // ESC key
			this._toolbar.editGroup.btnCancel.doAction();
		}
	},

	/**
	 * interface's onKeyPressed event handler (more user friendly regarding keyboard input handling)
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue.Interface interface
	 * @param jQuery.Event event
	 */
	_interfaceHandler_onKeyPressed: function( relatedInterface, event ) {
		if( event.which == 13 ) { // enter key
			if( this.valueCompare( this.getInitialValue(), this.getValue() ) ) {
				// value not modified yet, cancel button not available but enter should also stop editing
				this._toolbar.editGroup.btnCancel.doAction();
			} else {
				// try to save value
				this._toolbar.editGroup.btnSave.doAction();
			}
		}
	},

	/**
	 * interface's onFocus event handler
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue.Interface interface
	 * @param jQuery.Event event
	 */
	_interfaceHandler_onFocus: function( relatedInterface, event ) { },

	/**
	 * interface's onBlur event handler
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue.Interface interface
	 * @param jQuery.Event event
	 */
	_interfaceHandler_onBlur: function( relatedInterface, event ) { },
	
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
	onAfterStopEditing: null,
	
	/**
	 * Callback called after the element was removed
	 */
	onAfterRemove: null
};
