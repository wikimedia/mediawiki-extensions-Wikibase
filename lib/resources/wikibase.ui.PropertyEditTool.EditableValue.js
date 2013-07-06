/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater <mediawiki@snater.com>
 */
( function( mw, wb, $ ) {
'use strict';
/* jshint camelcase: false */

var PARENT = wb.ui.Base;

/**
 * Acts as a values representation in the DOM and offers an input interface for the value when in
 * edit mode as well as a static DOM for merely displaying the value. One editable value can contain
 * several wb.ui.PropertyEditTool.EditableValue.Interface instances, depending on the value's
 * structure and requirement. Serves functionality to start and stop editing the value and will
 * trigger its interfaces to transform from the static DOM into an editable form. It also holds
 * necessary information to make an API call to change the represented value on the server's side.
 *
 * NOTE: This is pretty much deprecated in the sense that new widgets should not use this but
 *       instead use the DataValue and DataType extension's data objects and their jQuery.valueview.
 *
 * NOTE: For initializing a new EditableValue, the wb.ui.PropertyEditTool.EditableValue.newFromDom()
 *       factory usually is the easier way of doing so.
 *
 * NOTE: TODO: the EditableValue's toolbar is optional in the constructor. BUT, currently the
 *       EditableValue still has a twisted relation with its toolbar. If it is not set either here
 *       or in setToolbar(), it is very likely to break.
 *
 * @see http://meta.wikimedia.org/wiki/Wikidata/Notes/JavaScript_ui_implementation
 *
 * @param {jQuery} subject DOM node the UI element will be initialized upon.
 * @param {Object} options (see @option documentations below)
 * @param {wb.ui.PropertyEditTool.EditableValue.Interface[]} interfaces
 * @param {jQuery.wikibase.toolbar} [toolbar] Shouldn't be initialized yet
 *
 * @constructor
 * @abstract
 * TODO: make this non-abstract and get rid of inherited versions, use strategy/factory patterns
 * @extends wb.ui.Base
 * @since 0.2 (available in 0.1 but different constructor)
 *
 * @option valueLanguageContext {string} If the EditableValue's value is a multilingual value, which
 *         is represented by a different value in each language, then this option should be set to
 *         the language code of the language the value is given in.
 *
 * @option inputHelpMessageKey {string} Message key of the help tooltip message available in the
 *         toolbar while in edit mode. The first parameter of the message is the language name of
 *         the value if the value is a multilingual value.
 *
 * TODO: afterStopEditing should also be triggered when left edit mode via cancel!
 * @event afterStopEditing: Triggered after having left edit mode without cancelling.
 *        (1) jQuery.Event
 *        (2) bool - save - whether save action was triggered
 *        (3) bool - wasPending - whether value is a completely new value
 *
 * @event showError: Triggered when error is displayed.
 *        (1) jQuery.Event
 *        (2) {wb.RepoApiError}
 *        (3) {Object} Object the error tooltip is attached to
 *
 * @event hideError: Triggered when displayed error is removed again.
 *        (1) jQuery.Event
 */
var SELF = wb.ui.PropertyEditTool.EditableValue = wb.utilities.inherit( PARENT,
	// Overwritten constructor:
	function( subject, options, interfaces, toolbar ) {
		// allow toolbar not to be set, even though it is still required inside in many places
		if( subject !== undefined && interfaces !== undefined ) {
			this.init( subject, options, interfaces, toolbar );
		}
	}, {
	/**
	 * @const
	 * @see wb.ui.Base.UI_CLASS
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
		SAVE_TO_REMOVE: 3,
		/**
		 * Action for information purpose only. Is only used by the jQuery.Promise returned by stopEditing().
		 * This has not to be used as an actual API action.
		 */
		NONE: false
	},

	/**
	 * specific property key within the API JSON structure.
	 * @const string
	 */
	API_VALUE_KEY: null,

	/**
	 * API for interaction with Wikibase
	 * @type {wikibase.RepoApi}
	 */
	_api: new wb.RepoApi(),

	/**
	 * Element representing the editable value. This element will either hold the value or the input
	 * box in case it is activated for edit.
	 * @type {jQuery}
	 */
	_subject: null,

	/**
	 * This is true if the input interface is initialized at the time.
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * Holds the input element in case this is in edit mode
	 * @type {null|jQuery}
	 */
	_inputElem: null,

	/**
	 * The toolbar controlling the editable value.
	 * @type {jQuery.wikibase.toolbar}
	 */
	_toolbar: null,

	/**
	 * If this is true, it means that the value has not been stored to the database at all. So if
	 * in edit mode and pressing cancel, the element will be removed
	 * @type {boolean}
	 */
	_pending: false,

	/**
	 * Array holding all the interfaces which are part of the editable value.
	 * @type {wikibase.ui.PropertyEditTool.EditableValue.Interface[]}
	 */
	_interfaces: null,

	/**
	 * Defines whether to propagate events triggered by the editable value's interfaces. The default
	 * state should always be "true". For example, an interface might already be initialized in
	 * a edit-like mode when there is no value yet (e.g. an empty label). Typing in the interfaces
	 * input element shall trigger events like "starItemPageEditMode". However, when starting edit
	 * mode in a dedicated way by clicking an "edit" button, there is no need to have an interface
	 * trigger any events that actually should be triggered by the editable value.
	 * @type {boolean}
	 */
	_propagateInterfaceEvents: true,

	/**
	 * @see wikibase.ui.Base._options
	 * @type {Object}
	 */
	_options: {
		valueLanguageContext: mw.config.get( 'wgUserLanguage' ),
		inputHelpMessageKey: null
	},

	/**
	 * @see wb.ui.Base._init()
	 *
	 * @param {jQuery} subject
	 * @param {Object} options
	 * @param {*[]} interfaces
	 * @param {jQuery.wikibase.toolbar} toolbar
	 */
	_init: function( subject, options, interfaces, toolbar ) {
		// TODO: this has always been a sucker: // TODO: Why? Solve or document properly.
		this._pending = subject.hasClass( 'wb-pending-value' );

		if( !$.isArray( interfaces ) ) {
			interfaces = [ interfaces ];
		}
		this._bindInterfaces( interfaces );
		this._interfaces = interfaces;

		if( toolbar !== undefined ) {
			this.setToolbar( toolbar );
		}
	},

	/**
	 * Binds the interfaces handled by this editable value via some events.
	 *
	 * @param {wb.ui.PropertyEditTool.EditableValue.Interface[]} interfaces
	 */
	_bindInterfaces: function( interfaces ) {
		var self = this;
		$.each( interfaces, function( index, elem ) {
			self._bindSingleInterface( elem );
		} );
	},

	/**
	 * Does the initialization for a single editable value interface. Basically this will bind the
	 * used events and set needed options.
	 *
	 * @param {wikibase.ui.PropertyEditTool.EditableValue.Interface} singleInterface
	 */
	_bindSingleInterface: function( singleInterface ) {
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
	 * Allows to set the toolbar of this EditableValue.
	 *
	 * @param {jQuery.wikibase.toolbar} toolbar
	 */
	setToolbar: function( toolbar ) {
		var self = this,
			tbParent = this._getToolbarParent();

		// TODO: remove existing toolbar
		this._toolbar = toolbar;

		tbParent.addClass( this.UI_CLASS + '-toolbarparent wb-editsection' );
		toolbar.element.appendTo( tbParent );

		// react on clicks on the toolbar buttons
		$( this._toolbar.$editGroup )
		.on( 'toolbareditgroupedit', function( event, callback ) {
			// The default event would trigger toolbar edit mode as well, but startEditing()
			// requires that the mode has been changed already in order to detect wheter to trigger
			// a startItemPageEditMode event. This refers to initially having one or more empty
			// values whose edit modes are started instantly when loading the page.
			// (see this.startEditing())
			event.preventDefault(); // no need to trigger edit mode after startEditing() because ...
			self._toolbar.$editGroup.data( 'toolbareditgroup' ).toEditMode(); // ... it is required by startEditing()
			if( self.startEditing() ) {
				callback();
			}
		} )
		.on( 'toolbareditgroupremove', function( event ) {
			event.preventDefault();
			self.remove();
		} )
		.on( 'toolbareditgroupsave', function( event, callback ) {
			event.preventDefault();
			self.stopEditing( true ).done( function() {
				callback();
			} );
		} )
		.on( 'toolbareditgroupcancel', function( event, callback ) {
			event.preventDefault();
			self.stopEditing( false ).done( function() {
				if( !self.isInEditMode() ) {
					callback();
				}
			} );
		} );

		// set the tooltip message
		this._toolbar.$editGroup.data( 'toolbareditgroup' ).setTooltip( this.getInputHelpMessage() );

		// TODO: this should really be in the constructor, move it after having the toolbar
		//       separated from the EditableValue via event bindings.
		if( this.isEmpty() || this.isPending() ) {
			// enable editing from the beginning if there is no value yet or pending value...
			this._toolbar.$editGroup.data( 'toolbareditgroup' ).$btnEdit.trigger( 'click' );
			this.removeFocus(); // ...but don't set focus there for now
		}
	},

	/**
	 * Returns the toolbar of this EditableValue.
	 *
	 * @return {jQuery.wikibase.toolbar}
	 */
	getToolbar: function() {
		return this._toolbar;
	},

	/**
	 * Returns whether there is a toolbar which allows the user interaction with this EditableValue.
	 *
	 * @return {boolean}
	 */
	hasToolbar: function() {
		return !!this._toolbar;
	},

	/**
	 * Returns the node the toolbar should be appended to
	 *
	 * @return jQuery
	 */
	_getToolbarParent: function() {
		// create new span within the structure where we can append the toolbar later:
		if( this.__toolbarParent ) {
			return this.__toolbarParent;
		}

		var editSection = this._subject.find( '.wb-editsection' );
		this.__toolbarParent = editSection.length > 0
			? editSection
			: $( '<span/>' ).appendTo( this._subject );

		return this.__toolbarParent;
	},

	/**
	 * Removes the value from the data store via the API. Also removes the values representation from the dom stated
	 * differently.
	 *
	 * @return jQuery.Promise in case the remove function has been called before and is still running, the promise from
	 *         the ongoing remove will be returned again; the promise will hold additional information
	 * @see $.PersistentPromisor()
	 */
	remove: $.PersistentPromisor( function() {
		var promise, action;

		var degrade = $.proxy( function() {
			if( !this.preserveEmptyForm ) {
				$( wb ).triggerHandler( 'stopItemPageEditMode', [ this, this.isPending() ] );
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

		if( this.isPending() || ( this.isEmpty() && this.isNew() ) ) {
			// no API call necessary since value hasn't been stored yet...
			degrade();
			promise = $.Deferred().resolve().promise(); // ...return new promise nonetheless
			action = this.API_ACTION.NONE;
		} else {
			action = this.preserveEmptyForm ? this.API_ACTION.SAVE_TO_REMOVE : this.API_ACTION.REMOVE;

			// store deferred so we can return it when this is called again while still running
			promise = this.performApiAction( action )
				.done( degrade )
				.promise();
		}
		promise.promisor = {
			apiAction: action
		};
		return promise;
	} ),

	/**
	 * Saves the current value by sending it to the server. In case the current value is invalid, this will trigger a
	 * remove instead but will preserve the form to insert a new value.
	 *
	 * @return jQuery.Promise
	 * @see $.PersistentPromisor()
	 */
	save: $.PersistentPromisor( function() {
		var promise = null;

		if( ! this.isValid() ) { // remove instead! Save equals remove in this case!
			promise = this.remove().promise();
			promise.promisor = promise.promisor || {};
			promise.promisor.apiAction = this.API_ACTION.SAVE_TO_REMOVE;
		} else {
			var wasPending = this.isPending();

			var deferred = this.performApiAction( this.API_ACTION.SAVE ) // returns deferred
				.done( $.proxy( function() {
					this._reTransform( true );
					this._pending = false; // not pending anymore after saved once
					this._subject.removeClass( 'wb-pending-value' );
				}, this ) );

			promise = deferred.promise();
			promise.promisor = {
				apiAction: this.API_ACTION.SAVE,
				wasPending: wasPending
			};
		}
		return promise;
	} ),

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
		this._subject.addClass( this.UI_CLASS + '-ineditmode wb-edit' );

		// There is no need to propagate any event if the editable value itself is already aware of
		// the action triggering the event itself. See comment at this._propagateInterfaceEvents
		// declaration.
		this._propagateInterfaceEvents = false;

		$.each( this._interfaces, function( index, elem ) {
			elem.startEditing();
		} );

		this._propagateInterfaceEvents = true;

		/**
		 * only propagate start of edit mode (disabling other actions) when editable value is not
		 * disabled itself; this refers to initially having multiple empty values whose edit modes
		 * are started instantly when loading the page
		 */
		if ( !this._toolbar.isDisabled() ) {
			$( wb ).triggerHandler( 'startItemPageEditMode', this );
		}

		return true;
	},

	/**
	 * Destroys the edit box and displays the original text or the inputs new value.
	 *
	 * @param bool save whether to save the new user given value
	 * @return jQuery.Promise
	 */
	stopEditing: $.PersistentPromisor( function( save ) {
		// create promise which will ONLY be returned in case nothing is saved AND not pending
		var promise = $.Deferred().resolve().promise();

		promise.promisor = {};
		promise.promisor.apiAction = this.API_ACTION.NONE;

		if( !this.isInEditMode() ) {
			return promise;
		}

		if ( !save ) {
			this._reTransform( false );
			if( this.isPending() || ( this.preserveEmptyForm && this.isEmpty() && this.isNew() ) ) { // cancel pending edit...
				promise = this.remove(); // not yet existing value, no state to go back to -> do not trigger 'afterStopEditing' here!
			} else { // cancel...
				this._resetToolbar( promise.promisor.apiAction );
				$( wb ).triggerHandler( 'stopItemPageEditMode', [ this, this.isPending() ] );
				return promise;
			}
		} else {
			promise = this.save(); // save... (will call all the API stuff)
		}

		var wasPending = ( promise.promisor.wasPending !== undefined )
			? promise.promisor.wasPending
			: this.isPending();

		// store deferred so we can return it when this is called again while still running
		return promise
		.done(
			$.proxy( function() {
				this.triggerHandler( 'afterStopEditing', [ save, wasPending ] );

				// toolbar might have been destroyed already by removing the last value or
				// aborting (cancelling) the process of adding a pending value
				if ( this._toolbar !== null ) {
					this._resetToolbar( promise.promisor.apiAction );
				}

				$( wb ).triggerHandler( 'stopItemPageEditMode', [ this, wasPending ] );
			}, this )
		);

	} ),

	/**
	 * Resets the toolbar to non-edit mode.
	 *
	 * @param {number} apiAction (see this.API_ACTION)
	 */
	_resetToolbar: function( apiAction ) {
		if( apiAction === undefined // initialize
			|| apiAction === this.API_ACTION.SAVE
			|| ( apiAction === this.API_ACTION.NONE && ( !this.preserveEmptyForm || !this.isEmpty() ) )
		) {
			this._toolbar.$editGroup.data( 'toolbareditgroup' ).toNonEditMode();
		}
	},

	/**
	 * Removes the input element(s) from DOM.
	 *
	 * @param {boolean} save Whether the current value should be kept. If false, the initial value
	 *        will be restored.
	 */
	_reTransform: function( save ) {
		if( ! this.isInEditMode() ) {
			return false;
		}
		$.each( this._interfaces, function( index, elem ) {
			elem.stopEditing( save );
		} );

		var editGroup = this._toolbar.$editGroup.data( 'toolbareditgroup' );
		editGroup.$btnSave.data( 'wbbutton' ).removeTooltip();
		editGroup.$tooltipAnchor.data( 'wblabel' ).getTooltip().hide();

		this._isInEditMode = false; // out of edit mode after interfaces are converted back to HTML
		this._subject.removeClass( this.UI_CLASS + '-ineditmode wb-edit' );

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
	 * Performs one of the actions available in the this.API_ACTION enum and handles all API related
	 * stuff.
	 *
	 * @param {Number} apiAction see this.API_ACTION enum for all available actions
	 * @return {jQuery.Promise}
	 */
	performApiAction: function( apiAction ) {
		// we have to build our own deferred since the jqXHR object returned by api.proxy() is just
		// referring to the success of the ajax call, not to the actual success of the API request
		// (which could have failed depending on the return value).
		var deferred = $.Deferred(),
			self = this,
			waitMessage = mw.msg(
				'wikibase-'
					+ ( apiAction === this.API_ACTION.REMOVE ? 'remove' : 'save' )
					+ '-inprogress'
			);

		var waitMsg = $( '<span/>' )
		.addClass( this.UI_CLASS + '-waitmsg' )
		.append( // inner span to adjust font-size/line-height when appended to heading
			$( '<span/>' ).text( waitMessage )
		)
		.appendTo( this._getToolbarParent() ).hide();

		deferred
		.done( function( response ) {
			var didSaveAction = apiAction === self.API_ACTION.SAVE
				|| apiAction === self.API_ACTION.SAVE_TO_REMOVE;

			if( didSaveAction ) {
				var responseVal = self._getValueFromApiResponse( response.entity );

				// set normalized value from response if supported by API module
				if( responseVal !== null ) {
					// When saving, this is the 2nd time we set the value but this time right
					// (normalized)! The first time happens in _reTransform where we make sure all
					// interfaces stop editing.
					self.setValue( responseVal );
				}

				if( mw.config.get( 'wbEntityId' ) === null ) {
					// if the 'save' process will create a new item, trigger the event!
					$( window.wikibase ).triggerHandler( 'newItemCreated', response.entity );
				}
			}

			self._setRevisionIdFromApiResponse( response.entity );

			// fade out wait text
			// TODO: Fade delay should be an option.
			waitMsg.fadeOut( 400, function() {
				self._subject.removeClass( self.UI_CLASS + '-waiting' );

				if( didSaveAction ) {
					waitMsg.remove();
					// only re-display toolbar if value wasn't removed
					self._toolbar.element.fadeIn( 300 );
				}
			} );
		} )
		.fail( function( textStatus, response ) {
			// remove and show immediately since we need nodes for the tooltip!
			self._subject.removeClass( self.UI_CLASS + '-waiting' );
			waitMsg.remove();
			self.enable(); // re-enabling actions and input box when saving has failed
			self._toolbar.element.show();
			if ( apiAction === self.API_ACTION.REMOVE ) {
				/**
				 * re-enable all actions when removing fails since it is just using edit mode for
				 * disabling all actions while the remove action is being processed
				 */
				$( wb ).triggerHandler( 'stopItemPageEditMode', [ self, self.isPending() ] );
			}

			var editGroup = self._toolbar.$editGroup.data( 'toolbareditgroup' ),
				action = ( apiAction === self.API_ACTION.SAVE ) ? 'save' : 'remove',
				anchor = ( apiAction === self.API_ACTION.REMOVE )
					? editGroup.$btnRemove.data( 'wbbutton' )
					: editGroup.$btnSave.data( 'wbbutton' ),
				error = wb.RepoApiError.newFromApiResponse( textStatus, response, action );
			self.showError( error, anchor );
		} );

		// disabling input box during saving (success will stop edit mode, so no re-enabling is
		// necessary in that case)
		this.disable();

		// "force" disabling the toolbar; required due to the special treatment of empty labels /
		// descriptions (see this._setState()) which prevents the toolbar from being enabled when
		// saving an empty value
		this._toolbar.disable();

		this._toolbar.element.fadeOut( 200, $.proxy( function() {
			waitMsg.fadeIn( 200 );
			// do the actual API request and trigger jQuery.Deferred stuff:
			this.triggerApi( deferred, apiAction );
		}, this ) );
		this._subject.addClass( this.UI_CLASS + '-waiting' );

		// add additional info to promise:
		var promise = deferred.promise();
		promise.promisor = {};
		promise.promisor.apiAction = apiAction;

		return promise;
	},

	/**
	 * Extracts a value usable for this from an API response returned after saving the current state.
	 * Returns null in case the API module doesn't return any normalized value. This will fai an error if the given
	 * response is not compatible.
	 *
	 * @param array response
	 * @return array|null // TODO should be a DataValue object
	 */
	_getValueFromApiResponse: function( response ) {
		return ( this.API_VALUE_KEY !== null )
			? [ response[ this.API_VALUE_KEY ][ this.getValueLanguageContext() ].value ]
			: null;
	},

	/**
	 * Extracts the returned revision id from the API response and saves it to the revision store
	 *
	 * @param array response
	 */
	_setRevisionIdFromApiResponse: function( response ) {
		return true;
	},

	/**
	 * Triggers the API request.
	 *
	 * @param {jQuery.Deferred} deferred Handling the returning AJAX request.
	 * @param {number} apiAction See this.API_ACTION enum for all available actions.
	 */
	triggerApi: function( deferred, apiAction ) {
		this.queryApi( apiAction )
		.done( function( response ) {
			deferred.resolve( response );
		} )
		.fail( function( textStatus, response ) {
			deferred.reject( textStatus, response );
		} );
	},

	/**
	 * Returns the EditableValue's language context (language code) defined in the options if the
	 * EditableValue is multi-lingual. This does not necessarily have to be the language the
	 * messages are displayed in. Returns null in case the value is not bound to any language (e.g.
	 * site-links are the same in all languages).
	 *
	 * @since 0.4
	 *
	 * @return string|null Language code or null if value not bound to any specific language.
	 */
	getValueLanguageContext: function() {
		return this._options.valueLanguageContext;
	},

	/**
	 * Handles UI presentation of API errors.
	 * @see jQuery.NativeEventHandler
	 * @triggers showError
	 *
	 * @param {wb.RepoApiError} error
	 * @param {Object} anchor Object the error tooltip shall be attached to
	 */
	showError: $.NativeEventHandler( 'showError', function( event, error, anchor ) {
		var self = this;

		this._subject.addClass( 'wb-error' );

		anchor.setTooltip( error ).show( true );
		anchor.getTooltip().on( 'hide', function() {
			self._subject.removeClass( 'wb-error' );
			// TODO: consider jQuery.Event.preventDefault()
			self.triggerHandler( 'hideError', [ error ] );
		} );

		this.setFocus(); // re-focus input
	} ),

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
	 * TODO: this method only works for site links and therefore should be replaced by this.isNew()
	 * @deprecated
	 *
	 * @return bool
	 */
	isPending: function() {
		return this._pending;
	},

	/**
	 * Returns the current value
	 * // TODO: should return an object representing a data value
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
	 * // TODO: should take an object representing a data value
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
		var langName = wb.getLanguageNameByCode( this.getValueLanguageContext() );
		return this._options.inputHelpMessageKey
			? mw.msg( this._options.inputHelpMessageKey, langName )
			: '';
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
	 * Determines if this value is a new value that is not yet stored
	 * TODO: this method should completely replace this.isPending() which only works for site links
	 * TODO: re-evaluate if above does actually make sense. We have two cases:
	 *       - 1: entirely new value, no value set before (new, e.g. newly added site-link)
	 *       - 2: changed value but save() not yet called (pending?)
	 *
	 * @return bool true if this is a new value, not stored in the database so far
	 */
	isNew: function() {
		return this.valueCompare( this.getInitialValue(), null );
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
	 * Checks whether a certain value would be valid for this editable value.
	 *
	 * @todo: we might want to move this into a data value/type representing prototype later.
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
	 * FIXME/TODO: arrays basically empty but with missing elements (so they are considered invalid)
	 *             are not considered empty right now.
	 *
	 * @todo: mark this deprecated as soon as we use objects representing property values...
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
				return false;
			}
		}
		return true;
	},

	/**
	 * Reacts on interface input.
	 *
	 * @param {wikibase.ui.PropertyEditTool.EditableValue.Interface} relatedInterface
	 */
	_interfaceHandler_onInputRegistered: function( relatedInterface ) {
		if( ! relatedInterface.isInEditMode() ) {
			return;
		}
		var value = this.getValue(),
			isInvalid = !this.validate( value );

		// can't save if invalid input (except it is empty, in that case save == remove) OR same as before
		var disableSave = ( isInvalid && !this.isEmpty() ) || this.valueCompare( this.getInitialValue(), value );

		/*
		when having an empty input box, edit mode is automatically started when entering the first
		character; therefore, edit mode has to be stopped automatically (disabling cancel, save is
		disabled anyway when the input box is empty) when emptying the box again while the box was
		empty initially; apart from that, pending values may always be cancelled removing
		corresponding form input from the DOM etc.
		*/
		var disableCancel = !this.isInEditMode() && !this.isEmpty() ||
			!this.isPending() && this.isEmpty() && this.isNew();

		var editGroup = this._toolbar.$editGroup.data( 'toolbareditgroup' );
		editGroup.$btnSave.data( 'wbbutton' )[ disableSave ? 'disable' : 'enable' ]();
		editGroup.$btnCancel.data( 'wbbutton' )[ disableCancel ? 'disable' : 'enable' ]();

		/**
		 * Propagate stopping of edit mode (enabling other actions) when all editable value actions
		 * are disabled. This happens for empty values whose edit modes are triggered directly
		 * during page loading.
		 */
		if ( disableSave && disableCancel && this.preserveEmptyForm ) {
			if ( this._propagateInterfaceEvents || this.isNew() ) {
				$( wb ).triggerHandler( 'stopItemPageEditMode', [ this, false ] );
			}
			this._propagateInterfaceEvents = true;
		} else if ( this.isNew() ) {
			if ( this._propagateInterfaceEvents ) {
				$( wb ).triggerHandler( 'startItemPageEditMode', this );
				this._propagateInterfaceEvents = false;
			}
		}

	},

	/**
	 * Interface's onKeyUp event handler.
	 * (ESC key does not react on onKeyPressed)
	 *
	 * @param {wikibase.ui.PropertyEditTool.EditableValue.Interface} relatedInterface
	 * @param {jQuery.Event} event
	 */
	_interfaceHandler_onKeyUp: function( relatedInterface, event ) {
		if( event.which === $.ui.keyCode.ESCAPE ) {
			this._toolbar.$editGroup.data( 'toolbareditgroup' ).$btnCancel.trigger( 'click' );
		}
	},

	/**
	 * Interface's onKeyPressed event handler (more user friendly regarding keyboard input
	 * handling).
	 *
	 * @param {wikibase.ui.PropertyEditTool.EditableValue.Interface} relatedInterface
	 * @param {jQuery.Event} event
	 */
	_interfaceHandler_onKeyPressed: function( relatedInterface, event ) {
		if( event.which === $.ui.keyCode.ENTER ) {
			var editGroup = this._toolbar.$editGroup.data( 'toolbareditgroup' );
			if(
				// value same as before...
				this.valueCompare( this.getInitialValue(), this.getValue() )
			) {
				if( this.isNew() && !this.isValid() ) {
					// If invalid, the user probably changed the value but valueCompare() says the
					// value is equal because both values are invalid in case initial value was
					// empty.
					return;
				}
				// Value not modified yet, cancel button not available but enter should also stop
				// edit mode - simulating click on cancel link:
				editGroup.$btnCancel.trigger( 'click' );
			} else {
				// Try to save the value:
				editGroup.$btnSave.trigger( 'click' );
			}
		}
	},

	/**
	 * Interface's onFocus event handler.
	 *
	 * @param {wikibase.ui.PropertyEditTool.EditableValue.Interface} relatedInterface
	 * @param {jQuery.Event} event
	 */
	_interfaceHandler_onFocus: function( relatedInterface, event ) { },

	/**
	 * Interface's onBlur event handler.
	 *
	 * @param {wikibase.ui.PropertyEditTool.EditableValue.Interface} relatedInterface
	 * @param {jQuery.Event} event
	 */
	_interfaceHandler_onBlur: function( relatedInterface, event ) { },

	/**
	 * Removes all traces of this ui element from the DOM, so the represented value is still visible but not
	 * interactive anymore.
	 *
	 * @see wb.ui._destroy()
	 */
	_destroy: function() {
		this._reTransform( false );
		if ( this.isPending() ) {
			this._subject.empty().remove();
		}
		if( this._toolbar !== null) {
			var $toolbar = this._toolbar.element;
			this._toolbar.destroy();
			$toolbar.remove();
			this._toolbar = null;
		}
		this._getToolbarParent().remove();
		for ( var i in this._interfaces ) { // remove span added in _buildInterfaces()
			this._interfaces[i].destroy();
		}
		this._interfaces = null;
		var span = this._subject.children( 'span' ); // span that was inserted in _buildInterfaces()
		span.contents().detach().appendTo( this._subject );
		span.remove();
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
	 * Callback called after the element was removed
	 */
	onAfterRemove: null
} );

// add disable/enable functionality overwriting required functions
wb.utilities.ui.StatableObject.useWith( SELF, {
	/**
	 * Determines the state (disabled, enabled or mixed) of all EditableValue elements (interfaces
	 * and toolbar).
	 * @see wb.utilities.ui.StatableObject.getState
	 */
	getState: function() {
		// consider toolbars state if toolbar is set
		var state = this.hasToolbar() ? this._toolbar.getState() : undefined;

		// check interfaces
		$.each( this._interfaces, function( i, interf ) {
			var currentState = interf.getState();

			if( state !== currentState) {
				if( state === undefined ) {
					state = currentState;
				} else {
					// state of this element different from others -> mixed state
					state = this.STATE.MIXED;
					return false; // no point in checking other states, we are mixed!
				}
			}
		} );

		return state;
	},

	/**
	 * Dis- or enables the EditableValue (its toolbar and interfaces).
	 * @see wb.utilities.ui.StatableObject._setState
	 *
	 * @todo there is still the bug that calling disable() and then enable() will not re-initiate the disabled state
	 *       of certain toolbar buttons. E.g. if 'save' was disabled for some reason (e.g. initial value didn't change).
	 *       One solution could be to change the state system so all elements always keep their state but if the parent
	 *       is disabled
	 */
	_setState: function( state ) {
		var success = true;

		// propagate state to all interfaces:
		$.each( this._interfaces, function( i, interf ) {
			success = interf.setState( state ) && success;
		} );

		// propagate state to toolbar if toolbar is set
		if( this.hasToolbar() ) {
			var lockedToolbarState = this.hasLockedToolbarState();

			// do only propagate state to toolbar if the state of the toolbar is not locked by some special rule
			if( lockedToolbarState === false || lockedToolbarState === state ) {
				this._toolbar.setState( state );
			}
			success = success && ( this._toolbar.getState() === state ); // consider toolbar state in both cases
		}
		return success;
	},

	/**
	 * Returns whether and which of the toolbar states can currently not be changed by using EditableValue.setState().
	 * If this returns anything but false, EditableValue.setState() might return EditableValue.STATE_MIXED because the
	 * toolbar can't change its state at this point.
	 * It should still be possible to do EditableValue.getToolbar().setState( state ) if the state should be enforced.
	 *
	 * @since 0.2
	 *
	 * @return false|Number false if not locked or one of EditableValue.STATE's enums if a particular state is enforced.
	 */
	hasLockedToolbarState: function() {
		if( this.isInEditMode() && this.preserveEmptyForm && this.isEmpty() ) {
			// toolbar always disabled if in edit mode but empty if empty form should be preserved.
			// this is for label/description which display an empty form ready for input when empty. The toolbar though
			// should be disabled until something is entered there even though the EditableValue itself is enabled.
			return this.STATE.DISABLED;
		}
		return false;
	}

} );

/**
 * Initializes a new EditableValue from a given DOM structure by analysing the DOM and initializing
 * all the input interfaces needed by the EditableValue.
 *
 * @see wb.ui.PropertyEditTool.EditableValue
 *
 * @static
 * @since 0.2 (this basically matches the EditableValue constructor from 0.1)
 *
 * @throws Error If the given DOM structure is not compatible, e.g. if it is missing some parts.
 *
 * @param {jQuery} subject DOM node the UI element will be initialized upon. This has to be a DOM
 *        structure matching the needs of the type of EditableValue. Usually, the structure should
 *        have a single root node and one of its child nodes(!) should match '.wb-value', whose text
 *        content will then serve as the EditableValue's initial value. The same node can optionally
 *        have a class matching /wb-value-lang-([^\s]+)/ where $1 is the language code of the value's
 *        language if the value is a multilingual value. If this is not set, it will default to the
 *        'wgUserLanguage' config var.
 * @param {Object} options (see wb.ui.PropertyEditTool.EditableValue constructor documentation)
 *        Some options might be filled by the factory, depending on the given DOM. All options given
 *        explicitly will not be extracted from the DOM though.
 * @param {wb.ui.Toolbar} [toolbar] Shouldn't be initialized yet
 * @param {Function} [Constructor] Force the factory to create an instance of the given constructor.
 * @return wikibase.ui.PropertyEditTool.EditableValue
 */
SELF.newFromDom = function( subject, options, toolbar, /** internal */ Constructor ) {
	var $subject = $( subject ),
		$interfaceParent = $subject.find( '.wb-value' ).first(),
		simpleInterface = new wb.ui.PropertyEditTool.EditableValue.Interface( $interfaceParent, this );

	options = options || {};
	options.valueLanguageContext =
		options.valueLanguageContext || SELF.getValueLanguageContextFromDom( $interfaceParent );

	Constructor = Constructor || SELF;

	return new Constructor( $subject, options, simpleInterface, toolbar );
};

/**
 * Will extract a value suitable for a EditableValue's 'valueLanguageContext' from a given DOM
 * structure. If the given DOM structure has a node with a 'wb-value' class, this will assume that
 * the node's text is the EditableValue's value. If the same node has a class matching
 * /wb-value-lang-([^\s]+)/, then $1 will be assumed the value's language and will be returned by
 * this function.
 *
 * @static
 * @since 0.4
 *
 * @param {jQuery} $subject
 * @return String
 */
SELF.getValueLanguageContextFromDom = function( $subject ) {
	var $value = $subject.hasClass( 'wb-value' ) ? $subject : $subject.find( '.wb-value' ),
		valueLang = ( $value.attr( 'class' ) || '' ).match( /(?:^|\s)wb-value-lang-[^\s]+/g );

	return valueLang
		? valueLang[0].replace( /\s*wb-value-lang-/g, '' )
		: mw.config.get( 'wgUserLanguage' );
};

} )( mediaWiki, wikibase, jQuery );
