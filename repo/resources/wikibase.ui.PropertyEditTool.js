/**
 * JavaScript for 'Wikibase' edit forms
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
( function( mw, wb, $, undefined ) {
'use strict';
var $PARENT = wb.ui.Base;

/**
 * Module for 'Wikibase' extensions user interface functionality.
 * @constructor
 * @see wikibase.ui.Base;
 * @since 0.1
 */
wb.ui.PropertyEditTool = wb.utilities.inherit( $PARENT, {
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
	 * @var wikibase.ui.Toolbar
	 */
	_toolbar: null,

	/**
	 * The editable value for the properties data value
	 * @var wikibase.ui.PropertyEditTool.EditableValue[]
	 */
	_editableValues: null,

	/**
	 * @see wb.ui._init()
	 */
	_init: function( subject ) {
		var self = this;

		this._editableValues = [];

		this._initEditToolForValues();
		this._initToolbar();

		// call for first rendering of additional stuff of the view:
		this._onRefreshView( 0 );

		// disabling all actions when starting an edit mode
		$( wb )
		.on( 'startItemPageEditMode',
			function( event, origin ) {
				self._setState( self.STATE.DISABLED, origin );
			}
		)
		// re-enabling all actions then stoping an edit mode
		.on( 'stopItemPageEditMode',
			function( event, origin ) {
				self.enable();
			}
		)
		/**
		 * highlight whole PropertyEditTool context if there may no additionally EditableValues be
		 * added (in that case, PropertyEditTool is a container for a fixed set of EditableValues
		 * that is being edited as a whole)
		 */
		.on( 'startItemPageEditMode',
			function( event, origin ) {
				if( self.hasValue( origin ) ) {
					subject.addClass( self.UI_CLASS + '-ineditmode' );
				}
			}
		)
		.on( 'stopItemPageEditMode',
			function( event, origin, wasPending ) {
				subject.removeClass( self.UI_CLASS + '-ineditmode' );
				if(
					self.allowsMultipleValues
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

		if( this.allowsMultipleValues || this.allowsFullErase ) {
			// only add 'add' button if we can have several values
			this._toolbar.btnAdd = new wb.ui.Toolbar.Button( mw.msg( 'wikibase-add' ) );
			$( this._toolbar.btnAdd ).on( 'action', $.proxy( function( event ) {
				this.enterNewValue();
			}, this ) );

			this._toolbar.innerGroup.addElement( this._toolbar.btnAdd );

			if ( this.allowsMultipleValues ) {
				// enable button only if this is not full yet, overwrite function directly
				var self = this;
				this._toolbar.btnAdd.setState = function( state ) {
					var origState = state;
					if( state === this.STATE.ENABLED ) {
						if( self.isFull() ) {
							// full list, don't enable 'add' button, show hint
							self._subject
							.find( 'tfoot td.wb-sitelinks-placeholder' )
							.text( self.fullListMessage );
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
		if( this.allowsMultipleValues ) {
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
		this._editableValues.push( editableValue );

		// message to be displayed for empty input:
		editableValue.inputPlaceholder = mw.msg( 'wikibase-' + this.getPropertyName() + '-edit-placeholder' );

		var editableValueToolbar = this._buildSingleValueToolbar( editableValue );

		// initialize editable value and give appropriate toolbar on the way:
		editableValue.init( valueElem, editableValueToolbar );

		var self = this;
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
		var elemIndex = this.getIndexOf( editableValue );

		// remove EditableValue from list of managed values:
		this._editableValues.splice( elemIndex, 1 );

		if( elemIndex >= this._editableValues.length ) {
			elemIndex = -1; // element removed from end
		}
		this._onRefreshView( elemIndex );

		// enables 'add' button again if it was disabled because of full list:
		this._toolbar.btnAdd[ this.isInAddMode() ? 'disable' : 'enable' ]();
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
	 * @return wikibase.ui.Toolbar
	 */
	_buildSingleValueToolbar: function( editableValue ) {
		var toolbar = new wb.ui.Toolbar();

		// give the toolbar a edit group with basic edit commands:
		var editGroup = new wb.ui.Toolbar.EditGroup();
		editGroup.displayRemoveButton = this.allowsMultipleValues; // remove button if we have a list
		editGroup.init( editableValue );

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
	 * @param value Object optional, initial value
	 * @return newValue wikibase.ui.PropertyEditTool.EditableValue
	 */
	enterNewValue: function( value ) {
		var newValueElem = this._newEmptyValueDOM(); // get DOM for new empty value
		newValueElem.addClass( 'wb-pending-value' );

		this._getValuesParent().append( newValueElem );
		var newValue = this._initSingleValue( newValueElem );

		if( this.allowsMultipleValues && !this.allowsFullErase ) { // on allowsFullErase, add button will be hidden when not in use
			this._toolbar.btnAdd.disable(); // disable 'add' button...
		}

		if( value ) {
			newValue.setValue( value );
		}

		this._onRefreshView( this.getIndexOf( newValue ) );
		newValue.setFocus();

		return newValue;
	},

	/**
	 * Handler called only the first time a new value was added and saved or cancelled.
	 *
	 * @param wikibase.ui.PropertyEditTool.EditableValue newValue new editable value object
	 * @param bool save whether save has been tiggered
	 * @param bool wasPending whether value is a completely new value not yet stored in the database
	 */
	_newValueHandler_onAfterStopEditing: function( newValue, save, wasPending ) {
		this._toolbar.btnAdd.enable(); // ...until stop editing new item
		if( save ) {
			this._onRefreshView( $.inArray( newValue, this._editableValues ) );
		}
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
		// Initialize counter where required:
		this._updateCounters();

		// set 'even' and 'uneven' css classes to containing values:
		if( fromIndex < 0 ) {
			return; // element at the end was removed, no update requiredy
		}
		for( var i = fromIndex; i < this._editableValues.length; i++ ) {
			var isEven = ( i % 2 ) != 0;
			var val = this._editableValues[ i ];

			val._subject
			.addClass( isEven ? 'even' : 'uneven' )
			.removeClass( isEven ? 'uneven' : 'even' );

			var valIndexParent = val._getIndexParent();
			if( valIndexParent !== null ) {
				valIndexParent.text( i + 1 + '.' );
			}
		}
	},

	/**
	 * This will refresh all counters which display the number of values managed by this.
	 */
	_updateCounters: function() {
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
		var numberOfPendingValues = this.getPendingValues().length;
		var numberOfValues = this.getValues().length;

		var msg = numberOfPendingValues < 1
				? mw.msg( 'wikibase-propertyedittool-counter', numberOfValues )
				: mw.msg(
						'wikibase-propertyedittool-counter-pending',
						numberOfValues + numberOfPendingValues,
						numberOfValues,
						'__3__' // can't insert html here since it would be escaped!
				);

		// replace __3__ with a span we can grab next
		msg = $( ( '<div>' + msg + '</div>' ).replace( /__3__/g, '<span/>' ) );
		var msgSpan = msg.find( 'span' );

		if( msgSpan.length > 0 ) {
			msgSpan.addClass( this.UI_CLASS + '-counter-pending' );
			msgSpan.attr( 'title', mw.msg( 'wikibase-propertyedittool-counter-pending-tooltip', numberOfPendingValues ) );
			msgSpan.text( mw.msg( 'wikibase-propertyedittool-counter-pending-pendingsubpart', numberOfPendingValues ) );
			msgSpan.tipsy( {
				'gravity': 'ne'
			} );
		}

		return msg.contents();
	},

	/**
	 * Creates the DOM structure for a new empty value which can be appended to the list of values.
	 *
	 * @return jQuery
	 */
	_newEmptyValueDOM: function() {
		return $( '<span><span class="wb-value" /></span>' );
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
	 * @var string
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
	},


	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * If true, the tool will manage several editable values and offer a remove and add command
	 * @var bool
	 */
	allowsMultipleValues: true,

	/**
	 * determines whether it is possible to fully erase all values (displaying an add button when completely empty)
	 * @var bool
	 */
	allowsFullErase: false,

	/**
	 * If multiple values are allowed, this message will be displayed if the collection is considered full.
	 * @var String
	 */
	fullListMessage: mw.message( 'wikibase-propertyedittool-full' )
} );

// add disable/enable functionality overwriting required functions
wb.utilities.ui.StateExtension.useWith( wb.ui.PropertyEditTool, {
	/**
	 * Determines the state (disabled, enabled or mixed) of all edit tool elements (editable values and toolbar).
	 * @see wb.utilities.ui.StateExtension.getState
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
			} )
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
	 * @see wb.utilities.ui.StateExtension._setState
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
