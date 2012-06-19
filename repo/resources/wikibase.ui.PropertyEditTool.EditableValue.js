/**
 * JavaScript for managing editable representation of property values.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 *
 * Events:
 * -------
 * TODO untangle afterSaveComplete and afterStopEditing
 * afterSaveComplete: Triggered after saving a valid item and cancelling
 *                    Parameters: (1) jQuery.event
 * afterStopEditing: Triggered after having left edit mode
 *                   Parameters: (1) jQuery.event
 *                               (2) bool - save - whether save action was triggered
 *                               (3) bool - wasPending - whether value is a completely new value
 * newItemCreated: Triggered after an item has been created and the necessary API request has returned
 *                   Parameters: (1) jQuery.event
 *                               (2) JSON - item - the new item returned by the API request FIXME: this should be an
 *                                                                                                 'Item' object!
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
	 * specific property key within the API JSON structure.
	 * @const string
	 */
	API_VALUE_KEY: null,

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
		this._subject.addClass( this.UI_CLASS );

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
	 * @param jQuery subject
	 * @return wikibase.ui.PropertyEditTool.EditableValue.Interface[]
	 */
	_buildInterfaces: function( subject ) {
		var interfaces = [];

		var interfaceParent = $( '<span/>' ).append( subject.contents() );
		subject.prepend( interfaceParent );
		interfaces.push( new window.wikibase.ui.PropertyEditTool.EditableValue.Interface( interfaceParent, this ) );

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
		// create new div within the structure where we can append the toolbar later:
		this.__toolbarParent = this.__toolbarParent || $( '<span/>' ).appendTo( this._subject );
		return this.__toolbarParent;
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
	 * Removes the value from the data store via the API. Also removes the values representation from the dom stated
	 * differently.
	 *
	 * @return jQuery.Promise in case the remove was called before and is still running, the deferred from the ongoing
	 *         remove will be returned and the deferreds property isOngoingRemove will be set to true.
	 */
	remove: function() {
		if( this.__isRemoving ) {
			this.__isRemoving.isOngoingRemove = true;
			return this.__isRemoving_deferred.promise(); // returns the deferred
		}

		var degrade = $.proxy( function() {
			if( !this.preserveEmptyForm ) {
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
			// no API call necessary since value hasn't been stored yet...
			degrade();
			return $.Deferred().resolve(); // ...return new deferred nonetheless
		} else {
			var action = this.preserveEmptyForm ? this.API_ACTION.SAVE_TO_REMOVE : this.API_ACTION.REMOVE;

			// store deferred so we can return it when this is called again while still running
			// NOTE: can't store deferred in this.__isRemoving because .always() might be called even before return!
			this.__isRemoving = true;
			this.__isRemoving_deferred =
				this.performApiAction( action )
				.then( degrade )
				.always( $.proxy( function() {
					this.__isRemoving = false;
				}, this ) );

			return this.__isRemoving_deferred.promise(); // return deferred
		}
	},

	/**
	 * Saves the current value by sending it to the server. In case the current value is invalid, this will trigger a
	 * remove instead but will preserve the form to insert a new value.
	 *
	 * @return jQuery.Promise
	 */
	save: function() {
		if( arguments.length > 0 ) {
			alert( "TAKE A LOOK AT EditableValue.save() again!" );
		}
		if( ! this.isValid() ) {
			// remove instead! Save equals remove in this case!
			return this.remove( true );
		}

		return this.performApiAction( this.API_ACTION.SAVE ) // returns deferred
		.then( $.proxy( function( response ) {
			var wasPending = this.isPending();
			this._reTransform( true );
			this._pending = false; // not pending anymore after saved once
			this._subject.removeClass( 'wb-pending-value' );
			$( this ).triggerHandler( 'afterStopEditing', [ true, wasPending ] );
		}, this ) )
		.promise();
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
	 * @return bool whether the value has changed (or was removed) in which case the changes are on their way to the API
	 */
	stopEditing: function( save ) {
		if( ! this.isInEditMode() || this.__isStopEditing ) {
			return false;
		}

		if( this.onStopEditing !== null && this.onStopEditing( save ) === false ) { // callback
			return false; // cancel
		}

		if( ! save && this.isPending() ) {
			// cancel pending edit...
			this._reTransform( false );
			this.remove(); // not yet existing value, no state to go back to
			return false; // do not trigger 'afterStopEditing' here!
		}

		if( ! save ) {
			//cancel...
			var wasPending = this._reTransform( false );
			$( this ).triggerHandler( 'afterStopEditing', [ save, this.isPending() ] );
			$( this ).triggerHandler( 'afterSaveComplete' );
		}
		else {
			this.__isStopEditing = true; // don't go here twice as long as callback still running

			// save... (will call all the API stuff)
			this.save()
			.done(
				$.proxy( function() {
					if ( this.isValid() ) { // editable value will be removed if invalid
						$( this ).triggerHandler( 'afterSaveComplete' );
					} else {
						$( this ).triggerHandler( 'afterStopEditing', [ save, this.isPending() ] );
					}
				}, this )
			)
			.always( $.proxy( function() {
				this.__isStopEditing = false;
			}, this ) );
		}

		return save;
	},

	/**
	 * remove input elements from DOM
	 *
	 * @param bool save whether the current value should be kept. If false, the initial value will be restored.
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
	 * @return jQuery.Promise
	 */
	performApiAction: function( apiAction ) {
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
		.then( function( response ) {
			// fade out wait text
			waitMsg.fadeOut( 400, function() {
				self._subject.removeClass( self.UI_CLASS + '-waiting' );

				if( apiAction !== self.API_ACTION.REMOVE ) {
					var responseVal = self._getValueFromApiResponse( response.item );
					if( responseVal !== null ) {
						// set normalized value from response if supported by API module
						self.setValue( responseVal );
					}

					if( mw.config.get( 'wbItemId' ) === null ) {
						// if the 'save' process will create a new item, trigger the event!
						$( window.wikibase ).triggerHandler( 'newItemCreated', response.item );
					}

					waitMsg.remove();
					self._toolbar._elem.fadeIn( 300 ); // only re-display toolbar if value wasn't removed
				}
			} );
		} )
		.fail( function( textStatus, response ) {
			// remove and show immediately since we need nodes for the tooltip!
			self._subject.removeClass( self.UI_CLASS + '-waiting' );
			waitMsg.remove();
			self._toolbar._elem.show();
			self._apiCallErr( textStatus, response, apiAction );
		} );

		this._toolbar._elem.fadeOut( 200, $.proxy( function() {
			waitMsg.fadeIn( 200 );
			// do the actual API request and trigger jQuery.Deferred stuff:
			this.queryApi( deferred, apiAction );
		}, this ) );
		this._subject.addClass( this.UI_CLASS + '-waiting' );

		return deferred.promise();
	},

	/**
	 * Extracts a value usable for this from an API response returned after saving the current state.
	 * Returns null in case the API module doesn't return any normalized value. This will fai an error if the given
	 * response is not compatible.
	 *
	 * @param array response
	 * @return string|null
	 */
	_getValueFromApiResponse: function( response ) {
		return ( this.API_VALUE_KEY !== null )
			? response[ this.API_VALUE_KEY ][ window.mw.config.get( 'wgUserLanguage' ) ].value
			: null;
	},

	/**
	 * submitting the AJAX request to query the API
	 *
	 * @param jQuery.deferred deferred handling the returning AJAX request
	 * @param number apiAction see this.API_ACTION enum for all available actions
	 */
	queryApi: function( deferred, apiAction ) {
		var api = new mw.Api();
		var apiCall = this.getApiCallParams( apiAction );
		$.extend( apiCall, { usekeys: 1 } ); // according to API
		api.post( apiCall, {
			ok: function( response ) {
				deferred.resolve( response );
			},
			err: function( textStatus, response ) {
				deferred.reject( textStatus, response );
			}
		} );
	},

	/**
	 * Returns the neccessary parameters for an api call to store the value.
	 *
	 * @param number apiAction see this.API_ACTION enum for all available actions
	 * @return Object containing the API call specific parameters
	 */
	getApiCallParams: function( apiAction ) {
		var itemId = mw.config.get( 'wbItemId' );
		var params = {
			language: mw.config.get( 'wgUserLanguage' ),
			token: mw.user.tokens.get( 'editToken' )
		};

		if( itemId !== null ) {
			// API param can only be used if item exists
			params.id = itemId;
			params.item = 'set';
		} else {
			// add a new item, ID will be received in APIs return value
			params.item = 'add';
		}

		return params;
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
				code: textStatus,
				shortMessage: ( apiAction === this.API_ACTION.REMOVE )
					? mw.msg( 'wikibase-error-remove-connection' )
					: mw.msg( 'wikibase-error-save-connection' ),
				message: ''
			};
			if ( textStatus == 'timeout' ) {
				error.shortMessage = ( apiAction === this.API_ACTION.REMOVE )
					? mw.msg( 'wikibase-error-remove-timeout' )
					: mw.msg( 'wikibase-error-save-timeout' );
			} else {
				if ( typeof response.error != 'undefined' ) {
					error.code = response.error.code;
					error.message = response.error.info;
					error.shortMessage = ( apiAction === this.API_ACTION.REMOVE )
						? mw.msg( 'wikibase-error-remove-generic' )
						: mw.msg( 'wikibase-error-save-generic' );
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

		this._subject.addClass( this.UI_CLASS + '-aftereditnotify' );

		btn.setTooltip( new window.wikibase.ui.Tooltip( btn._elem, error, { gravity: 'nw' } ) );
		btn.getTooltip().show( true );
		$( btn.getTooltip() ).on( 'hide', $.proxy( function() {
			this._subject.removeClass( this.UI_CLASS + '-aftereditnotify' );
		}, this ) );

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
		return this.valueCompare( this.getValue() );
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
	 * Helper function comparing two values returned by getValue() or getInitialValue() as long as
	 * we work with arrays instead of proper objects here.
	 * When comparing the values, this will also do an normalization on the values before comparing
	 * them, so even though they are not exactly the same perhaps, they still have the same meaning
	 * and true will be returned.
	 * NOTE/TODO: arrays basically empty but with missing elements (so they are considered invalid)
	 *            are not considered empty right now.
	 *
	 * @todo: make this deprecated as soon as we use objects representing property values...
	 *
	 * @param Array value1
	 * @param Array value2 [optional] if not given, this will check whether value1 is empty
	 * @return bool true for equal/empty, false if not
	 */
	valueCompare: function( value1, value2 ) {
		var emptyCheck = !$.isArray( value2 );

		if( this._interfaces.length !== value1.length
			|| !emptyCheck && value1.length !== value2.length
		) {
			return false;
		}

		for( var i in value1 ) {
			// check for equal arrays with same entries in same order
			var val2 = emptyCheck ? null : value2[ i ];
			if ( ! this._interfaces[ i ].valueCompare( value1[ i ], val2 ) ) {
				return false
			}
		}
		return true;
	},

	/**
	 * reacting on interface input
	 *
	 * @param relatedInterface wikibase.ui.PropertyEditTool.EditableValue.Interface
	 */
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
		if( event.which === $.ui.keyCode.ENTER ) {
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

	/**
	 * Removes all traces of this ui element from the DOM, so the represented value is still visible but not interactive
	 * anymore.
	 */
	destroy: function() {
		this.preserveEmptyForm = false; // will cause stopEditing() to completely erase all structure
		                                // TODO/FIXME: not the nicest way of doing this!
		this.stopEditing( false );
		if( this._toolbar !== null) {
			this._toolbar.destroy();
			this._toolbar = null;
		}
		this._getToolbarParent().remove();
		for ( var i in this._interfaces ) { // remove span added in _buildInterfaces()
			this._interfaces[i]._subject.parent().empty().text( this._interfaces[i]._subject.text() );
		}
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * determines whether to keep an empty form when leaving edit mode
	 * @var bool
	 */
	preserveEmptyForm: true,

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
	 * Callback called after the element was removed
	 */
	onAfterRemove: null
};
