/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

/*jshint camelcase:false */

( function( mw, wb, $ ) {
'use strict';
var PARENT = wb.ui.Base;

/**
 * A container managing several values. When initialized for a given subject DOM element, all
 * existing values within that DOM structure will be initialized as wb.ui.PropertyEditTool.EditableValue.
 * If the PropertyEditTool is capable of managing several values, an 'add' button for adding new
 * values will be served.
 *
 * NOTE: In most cases it is not required to manage several values or to add and remove values.
 *       Several values are only managed in the wb.ui.SiteLinksEditTool.
 *
 * TODO: document the DOM structures required by the different PropertyEditTool and EditableValue
 *       variations. Offer functions to fabricate dummy DOM structures for instantiation of JS-only
 *       usage of those 'widgets'.
 *
 * @see http://meta.wikimedia.org/wiki/Wikidata/Notes/JavaScript_ui_implementation
 *
 * @constructor
 * @extends wb.ui.Base
 * @since 0.1
 *
 * @param {jQuery} subject A fitting DOM structure
 * @param {Object} options (see inline documentation in _init function)
 *
 * @option allowsMultipleValues {boolean} Defines whether this PropertyEditTool may contain more
 *         than one EditableValues.
 *         Default: true
 *
 * @option allowsFullErase {boolean} Defines whether this PropertyEditTool's EditableValues can be
 *         removed completely.
 *         Default: false
 *
 * @option fullListMessage {string} Message displayed when this PropertyEditTool may contain
 *         multiple EditableValues but the maximum number of values has been reached
 */
wb.ui.PropertyEditTool = wb.utilities.inherit( PARENT, {
	/**
	 * @const
	 * Class which marks a edit tool ui within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittool',

	/**
	 * Element the edit tool is related to.
	 * @type jQuery
	 */
	_subject: null,

	/**
	 * Contains the toolbar for the edit tool itself, not for its values or null if it doesn't have
	 * one.
	 * @type wikibase.ui.Toolbar
	 */
	_toolbar: null,

	/**
	 * The editable value for the properties data value. It should not be assumed that the order
	 * of the values in this array represents their order in the rendered view.
	 * @type wikibase.ui.PropertyEditTool.EditableValue[]
	 */
	_editableValues: null,

	/**
	 * @see wb.ui._init()
	 */
	_init: function( subject, options ) {
		// setting default options
		this._options = $.extend(
			{},
			PARENT.prototype._options, {
				allowsMultipleValues: true,
				allowsFullErase: false,
				fullListMessage: mw.message( 'wikibase-propertyedittool-full' )
			},
			options
		);

		var self = this;

		this._editableValues = [];

		this._initEditToolForValues();
		this._initToolbar();

		// call for first rendering of additional stuff of the view:
		this.refreshView();

		// disabling all actions when starting an edit mode
		$( wb )
		.on( 'startItemPageEditMode',
			function( event, origin ) {
				self._setState( self.STATE.DISABLED, origin );
			}
		)
		// re-enabling all actions then stoping an edit mode
		.on( 'stopItemPageEditMode', function() {
			self.enable();
		} )
		/**
		 * highlight whole PropertyEditTool context if there may no additionally EditableValues be
		 * added (in that case, PropertyEditTool is a container for a fixed set of EditableValues
		 * that is being edited as a whole)
		 */
		.on( 'startItemPageEditMode',
			function( event, origin ) {
				if( self.hasValue( origin ) ) {
					subject.addClass( self.UI_CLASS + '-ineditmode wb-edit' );
				}
			}
		)
		.on( 'stopItemPageEditMode',
			function( event, origin, wasPending ) {
				subject.removeClass( self.UI_CLASS + '-ineditmode wb-edit' );
				if(
					self.getOption( 'allowsMultipleValues' )
					&& wasPending !== undefined
					&& wasPending
					&& self.hasValue( origin )
				) {
					/* focus "add" button after adding a value to a multi-value property to
					instantly allow adding another value */
					self._toolbar.btnAdd.setFocus();
				}
			}
		);

	},

	/**
	 * Initializes a toolbar for the whole property edit tool. By default this is just a command
	 * to add more values.
	 */
	_initToolbar: function() {
		this._toolbar = new wb.ui.Toolbar( this.UI_CLASS );
		this._toolbar.innerGroup = new wb.ui.Toolbar.Group();
		this._toolbar.addElement( this._toolbar.innerGroup );

		if( this.getOption( 'allowsMultipleValues' ) || this.getOption( 'allowsFullErase' ) ) {
			// only add 'add' button if we can have several values
			this._toolbar.btnAdd = new wb.ui.Toolbar.Button( mw.msg( 'wikibase-add' ) );
			$( this._toolbar.btnAdd ).on( 'action', $.proxy( function( event ) {
				this.enterNewValue();
			}, this ) );

			this._toolbar.innerGroup.addElement( this._toolbar.btnAdd );

			if ( this.getOption( 'allowsMultipleValues' ) ) {
				// enable button only if this is not full yet, overwrite function directly
				var self = this;
				this._toolbar.btnAdd.setState = function( state ) {
					var origState = state;
					if( state === this.STATE.ENABLED ) {
						if( self.isFull() ) {
							// full list, don't enable 'add' button, show hint
							self._subject
							.find( 'tfoot td.wb-sitelinks-placeholder' )
							.text( self.getOption( 'fullListMessage' ) );
							state = this.STATE.DISABLED;
						}
						else if( self.isInAddMode() ) {
							// still adding new value, don't enable 'add' button!
							state = this.STATE.DISABLED;
						}
						else {
							// enabled, label with 'full' message not required
							self._subject
							.find( 'tfoot td.wb-sitelinks-placeholder' )
							.text( '' );
						}
					}
					// call original setState() with the state we want to inject
					wb.ui.Toolbar.Button.prototype.setState.call( this, state );

					// only return success if intended state is set now
					return origState === self._toolbar.btnAdd.getState();
				};
				this._toolbar.btnAdd.enable(); // will run the code above initially
			}
		}

		this._toolbar.appendTo( this._getToolbarParent() );
	},

	/**
	 * Returns whether further values can be added
	 *
	 * @return bool
	 */
	isFull: function() {
		if( this.getOption( 'allowsMultipleValues' ) ) {
			return false; // allow infinite number of values
		} else {
			return this._editableValues !== null && this._editableValues.length > 0;
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
	 * returns the toolbar of this PropertyEditTool or null if it doesn't have one.
	 *
	 * @return wikibase.ui.Toolbar|null
	 */
	getToolbar: function() {
		return this._toolbar;
	},

	/**
	 * Returns whether there is a toolbar which allows the user interaction with this PropertyEditTool.
	 *
	 * @return Boolean
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
		return this._subject;
	},

	/**
	 * Returns the node the value(s) should be appended to
	 *
	 * @return jQuery
	 */
	_getValuesParent: function() {
		return this._subject;
	},
	/**
	* Collects all values represented within the DOM already and initializes EditableValue instances
	* for them.
	*/
	_initEditToolForValues: function() {
		// gets the DOM nodes representing EditableValue
		var allValues = this._getValueElems(),
			self = this;

		if( ! this.getOption( 'allowsMultipleValues' ) ) {
			allValues = $( allValues[0] );
		}

		$.each( allValues, function( index, item ) {
			self._initSingleValue( item );
		} );
	},

	/**
	 * Takes care of initialization of a single value.
	 *
	 * @param {jQuery} valueElem
	 * @param {Object} [options]
	 * @return {wb.ui.PropertyEditTool.EditableValue the initialized} value
	 */
	_initSingleValue: function( valueElem, options ) {
		var self = this;

		options = $.extend( {
			// message to be displayed for empty input
			'inputPlaceholder': mw.msg( 'wikibase-' + this.getPropertyName() + '-edit-placeholder' )
		}, options );

		var editableValue = this.getEditableValuePrototype().newFromDom( valueElem, options );
		this._editableValues.push( editableValue );

		var editableValueToolbar = this._buildSingleValueToolbar( options );
		editableValue.setToolbar( editableValueToolbar );

		editableValue.onAfterRemove = function() {
			self._editableValueHandler_onAfterRemove( editableValue );
		};

		/**
		 * Event called after the editing process is finished. At this point the element is not in
		 * edit mode anymore.
		 * This will not be called in case the element was just created, still pending, and the editing
		 * process was cancelled.
		 *
		 * @param bool saved whether the result will be saved. If true, the result is sent to the API
		 *        already and the internal value is changed to the new value.
		 * @param bool wasPending whether the element was pending before the edit.
		 */
		$( editableValue )
		.on( 'afterStopEditing', function( event, save, wasPending ) {
			if ( save && wasPending ) {
				self._newValueHandler_onAfterStopEditing( editableValue, save, wasPending );
			}
		} )
		.on( 'showError', function( event, error ) {
			self._subject.addClass( 'wb-error' );
		} )
		.on( 'hideError', function( event, error ) {
			self._subject.removeClass( 'wb-error' );
		} );

		return editableValue;
	},

	/**
	 * Called whenever an editable value managed by this was removed.
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue
	 */
	_editableValueHandler_onAfterRemove: function( editableValue ) {
		// remove EditableValue from list of managed values:
		this._editableValues.splice( this.getIndexOf( editableValue ), 1 );

		this.refreshView();

		// enables 'add' button again if it was disabled because of full list:
		this._toolbar.btnAdd[ this.isInAddMode() ? 'disable' : 'enable' ]();
	},

	/**
	 * returns the index of an EditableValue within this collection. If the element is not part of
	 * this, -1 will be returned
	 *
	 * @param {wikibase.ui.PropertyEditTool.EditableValue} elem
	 * @return Number
	 */
	getIndexOf: function( element ) {
		return $.inArray( element, this._editableValues );
	},

	/**
	 * Builds the toolbar for a single editable value.
	 *
	 * @param {Object} [options]
	 * @return {wb.ui.Toolbar}
	 */
	_buildSingleValueToolbar: function( options ) {
		var toolbar = new wb.ui.Toolbar();

		// give the toolbar a edit group with basic edit commands:
		var editGroup = new wb.ui.Toolbar.EditGroup( $.extend( {
			// remove button if we have a list
			displayRemoveButton: this.getOption( 'allowsMultipleValues' )
		}, options ) );

		toolbar.addElement( editGroup );
		toolbar.editGroup = editGroup; // remember this

		return toolbar;
	},

	/**
	 * Returns the nodes representing the property's values. This can also return an array of jQuery
	 * objects if the value is represented by several nodes not sharing a mutual parent.
	 *
	 * @return jQuery|jQuery[]
	 */
	_getValueElems: function() {
		return this._subject.children( '.wb-property-container-value' );
	},

	_destroy: function() {
		if ( this._editableValues instanceof Array ) {
			$.each( this._editableValues, function( index, editableValue ) {
				editableValue.destroy();
			} );
			this._editableValues = null;
		}
		if ( this._toolbar !== null ) {
			this._toolbar.destroy();
			this._toolbar = null;
		}
	},

	/**
	 * Allows to enter a new value, the input interface will be available but the process can still
	 * be cancelled.
	 *
	 * @param {Object} [value] initial value
	 * @param {Object} [options] additional options
	 *        (use "prepend: true" to prepend the new value's DOM structure to the parent instead of
	 *        appending it)
	 * @return {wb.ui.PropertyEditTool.EditableValue}
	 */
	enterNewValue: function( value, options ) {
		if ( options === undefined ) {
			options = {};
		}

		var newValueElem = this._newEmptyValueDOM(); // get DOM for new empty value
		newValueElem.addClass( 'wb-pending-value' );

		if ( options.prepend === undefined || options.prepend === false ) {
			this._getValuesParent().append( newValueElem );
		} else {
			this._getValuesParent().prepend( newValueElem );
		}

		var newValue = this._initSingleValue( newValueElem, options );

		// on allowsFullErase, add button will be hidden when not in use
		if( this.getOption( 'allowsMultipleValues' ) && !this.getOption( 'allowsFullErase' ) ) {
			this._toolbar.btnAdd.disable(); // disable 'add' button...
		}

		if( value ) {
			newValue.setValue( value );
		}

		this.refreshView( newValue );
		newValue.setFocus();

		return newValue;
	},

	/**
	 * Handler called only the first time a new value was added and saved or cancelled.
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue newValue new editable value object
	 * @param bool save whether save has been triggered
	 * @param bool wasPending whether value is a completely new value not yet stored in the database
	 */
	_newValueHandler_onAfterStopEditing: function( newValue, save, wasPending ) {
		this._toolbar.btnAdd.enable(); // ...until stop editing new item
		if( save ) {
			this.refreshView( newValue );
		}
	},

	/**
	 * Called when the view changes, for example if elements are removed or added in case this is a
	 * view allowing multiple values.
	 * Usually there should be no need to call this because this is taken care of internally as
	 * long as internal functions are being used.
	 *
	 * @since 0.2
	 *
	 * @param {wb.ui.PropertyEditTool.EditableValue} [fromValue] The EditableValue which has
	 *        triggered the need to refresh the view (e.g. because of insertion or deletion).
	 *        If this is given, a more performant update can be done, only updating the DOM node
	 *        of this value and all subsequent siblings.
	 */
	refreshView: function( fromValue ) {
		if( !this.getOption( 'allowsMultipleValues' ) ) {
			return; // nothing to do, view remains the same
		}

		// Initialize counter where required:
		this.refreshCounters();

		// set 'even' and 'uneven' css classes to containing values:
		var $firstValNode,
			$refreshNodes,
			valuesClass = this.getEditableValuePrototype().prototype.UI_CLASS;

		if( fromValue === undefined
			|| !this.hasValue( fromValue ) // given value not known, perhaps removed
		) {
			// update all nodes
			$refreshNodes = this.getSubject().find( '.' + valuesClass );
			$firstValNode = $refreshNodes.first();
		} else {
			// only update given node and subsequent siblings
			$firstValNode = fromValue.getSubject();
			$refreshNodes = $firstValNode.nextAll( '.' + valuesClass ).add( $firstValNode );
		}

		var startIndex = $firstValNode.index();

		$refreshNodes.each( function( i ) {
			var isEven = ( ( startIndex + i ) % 2 ) !== 0;
			$( this )
			.addClass( isEven ? 'even' : 'uneven' )
			.removeClass( isEven ? 'uneven' : 'even' );
		} );
	},

	/**
	 * This will refresh all counters which display the number of values managed by this.
	 * Usually there should be no need to call this because this is taken care of internally as
	 * long as internal functions are being used.
	 *
	 * @since 0.2
	 */
	refreshCounters: function() {
		var counterElems = this._getCounterNodes();
		if( counterElems !== null && counterElems.length > 0 ) {
			this._getCounterNodes().empty().append( this._getFormattedCounterText() );
		}
	},

	/**
	 * Returns nodes which should serve as counters, displaying the number of nodes.
	 *
	 * @return jQuery
	 */
	_getCounterNodes: function() {
		return this._subject.find( '.' + this.UI_CLASS + '-counter' );
	},

	/**
	 * Returns a formatted string with the number of elements.
	 *
	 * @return jQuery
	 */
	_getFormattedCounterText: function() {
		var numberOfPendingValues = this.getPendingValues().length,
			numberOfValues = this.getValues().length;

			// build a nice counter, displaying fixed and pending values:
		var $counterMsg = wb.utilities.ui.buildPendingCounter(
			numberOfValues,
			numberOfPendingValues,
			'wikibase-propertyedittool-counter-entrieslabel',
			'wikibase-propertyedittool-counter-pending-tooltip'
		);

		// counter result should be wrapped by parentheses, which is another message. Since the
		// message system doesn't allow us to simply give a jQuery object, so we have to work
		// around this to with some trickery:
		var $parenthesesMsg = $(
			( '<div>' + mw.msg( 'parentheses', '__1__' ) + '</div>' ).replace( /__1__/g, '<span/>' )
		);
		$parenthesesMsg.find( 'span' ).replaceWith( $counterMsg );

		return $parenthesesMsg.contents();
	},

	/**
	 * Creates the DOM structure for a new empty value which can be appended to the list of values.
	 *
	 * @return {jQuery}
	 */
	_newEmptyValueDOM: function() {
		return mw.template( 'wb-property', '', '', '' );
	},

	/**
	 * Returns all EditableValue objects managed by this.
	 *
	 * @param bool getPendingValues if set to true, also pending values not yet stored will be returned.
	 * @return wikibase.ui.PropertyEditTool.EditableValue[]
	 */
	getValues: function( getPendingValues ) {
		if ( this._editableValues === null ) {
			return [];
		} else if( getPendingValues ) {
			return this._editableValues.slice();
		}

		var values = [];
		$.each( this._editableValues, function( index, elem ) {
			// don't collect pending elements
			if( ! elem.isPending() ) {
				values.push( elem );
			}
		} );
		return values;
	},

	/**
	 * Checks whether a given EditableValue object belongs to this PropertyEditTool object.
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue value
	 * @return bool
	 */
	hasValue: function( value ) {
		return ( $.inArray( value, this._editableValues ) !== -1 );
	},

	/**
	 * This will just return all pending values.
	 * See getValues() for getting all values or only values not pending.
	 *
	 * @return wikibase.ui.PropertyEditTool.EditableValue[]
	 */
	getPendingValues: function() {
		var values = [];
		$.each( this._editableValues, function( index, elem ) {
			if( elem.isPending() ) {
				values.push( elem );
			}
		} );
		return values;
	},

	/**
	 * Returns the related properties title
	 *
	 * @return String
	 */
	getPropertyName: function() {
		return $( this._subject.children( '.wb-property-container-key' )[0] ).attr( 'title' );
	},

	/**
	 * Defines which editable value should be used for this. Returns the constructor for creating such a value.
	 *
	 * @return wb.ui.PropertyEditTool.EditableValue
	 */
	getEditableValuePrototype: function() {
		return wb.ui.PropertyEditTool.EditableValue;
	}

} );

// add disable/enable functionality overwriting required functions
wb.utilities.ui.StatableObject.useWith( wb.ui.PropertyEditTool, {
	/**
	 * Determines the state (disabled, enabled or mixed) of all edit tool elements (editable values and toolbar).
	 * @see wb.utilities.ui.StatableObject.getState
	 */
	getState: function() {
		var state,
			toolbar = this._toolbar;

		// consider toolbars state if toolbar is set
		if( this.hasToolbar() ) {
			// (bug workaround) if toolbar empty, don't consider toolbars state since it always returns ENABLED...
			$.each( toolbar.getElements(), function( i, toolbarElem ) {
				if( toolbarElem.isStateChangeable() ) { // ... also ignore elements whose state can't be changed!
					state = toolbar.getState();
					return false;
				}
			} );
		}

		// check interfaces
		$.each( this._editableValues, function( i, editableValue ) {
			var currentState = editableValue.getState();

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
	 * Dis- or enables the PropertyEditTool (its toolbar and values).
	 * @see wb.utilities.ui.StatableObject._setState
	 *
	 * @param wb.ui.PropertyEditTool.EditableValue skip can be one value which should not be affected
	 */
	_setState: function( state, skip ) {
		var success = true;

		// propagate state to all interfaces:
		$.each( this._editableValues, function( i, editableValue ) {
			if ( editableValue !== skip ) {
				success = editableValue.setState( state ) && success;
			}
		} );

		// propagate state to toolbar if toolbar is set
		if( this.hasToolbar() ) {
			success = this._toolbar.setState( state ) && success;
		}
		return success;
	}

} );

} )( mediaWiki, wikibase, jQuery );
