/**
 * JavaScript for 'Wikibase' edit forms
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
 *
 * @since 0.1
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
	 * @var wikibase.ui.Toolbar
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
		this._editableValues = [];

		this._subject = $( subject );
		this._subject.addClass( this.UI_CLASS );

		this._initEditToolForValues();
		this._initToolbar();

		// call for first rendering of additional stuff of the view:
		this._onRefreshView( 0 );

		// disabling all actions when starting an edit mode
		$( wikibase ).on(
			'startItemPageEditMode',
			$.proxy(
				function( event, origin ) {
					this.disable( origin );
				}, this
			)
		);

		// re-enabling all actions then stoping an edit mode
		$( wikibase ).on(
			'stopItemPageEditMode',
			$.proxy(
				function( event, origin ) {
					this.enable();
				}, this
			)
		);

		/**
		 * highlight whole PropertyEditTool context if there may no additionally EditableValues be
		 * added (in that case, PropertyEditTool is a container for a fixed set of EditableValues
		 * that is being edited as a whole)
		 */
		$( wikibase ).on(
			'startItemPageEditMode',
			$.proxy(
				function( event, origin ) {
					if( this.hasValue( origin ) ) {
						this._subject.addClass( this.UI_CLASS + '-ineditmode' );
					}
				}, this
			)
		);
		$( wikibase ).on(
			'stopItemPageEditMode',
			$.proxy(
				function( event, origin, wasPending ) {
					this._subject.removeClass( this.UI_CLASS + '-ineditmode' );
					if(
						this.allowsMultipleValues
						&& typeof wasPending !== 'undefined'
						&& this.hasValue( origin )
					) {
						/* focus "add" button after adding a value to a multi-value property to
						instantly allow adding another value */
						this._toolbar.btnAdd.setFocus();
					}
				}, this
			)
		);

	},

	/**
	 * Initializes a toolbar for the whole property edit tool. By default this is just a command
	 * to add more values.
	 */
	_initToolbar: function() {
		this._toolbar = new window.wikibase.ui.Toolbar();
		this._toolbar.innerGroup = new window.wikibase.ui.Toolbar.Group();
		this._toolbar.addElement( this._toolbar.innerGroup );

		if( this.allowsMultipleValues || this.allowsFullErase ) {
			if ( this.allowsMultipleValues ) {
				// toolbar group for buttons:
				this._toolbar.lblFull = new window.wikibase.ui.Toolbar.Label(
						'&nbsp;- ' + mw.message( 'wikibase-propertyedittool-full' ).escaped()
				);
			}

			// only add 'add' button if we can have several values
			this._toolbar.btnAdd = new window.wikibase.ui.Toolbar.Button( mw.msg( 'wikibase-add' ) );
			$( this._toolbar.btnAdd ).on( 'action', $.proxy( function( event ) {
				this.enterNewValue();
			}, this ) );

			this._toolbar.innerGroup.addElement( this._toolbar.btnAdd );

			if ( this.allowsMultipleValues ) {
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
					return window.wikibase.ui.Toolbar.Button.prototype.setDisabled.call( this, disable );
				};
				this._toolbar.btnAdd.setDisabled( false ); // will run the code above
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
	 * returns the toolbar of this PropertyEditTool
	 *
	 * @return wikibase.ui.Toolbar
	 */
	getToolbar: function() {
		return this._toolbar;
	},

	/**
	 * Returns the node the toolbar should be appended to
	 *
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
		this._editableValues.push( editableValue );

		// message to be displayed for empty input:
		editableValue.inputPlaceholder = mw.msg( 'wikibase-' + this.getPropertyName() + '-edit-placeholder' );

		var editableValueToolbar = this._buildSingleValueToolbar( editableValue );

		// initialiye editable value and give appropriate toolbar on the way:
		editableValue._init( valueElem, editableValueToolbar );

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
	 * @return wikibase.ui.Toolbar
	 */
	_buildSingleValueToolbar: function( editableValue ) {
		var toolbar = new window.wikibase.ui.Toolbar();

		// give the toolbar a edit group with basic edit commands:
		var editGroup = new window.wikibase.ui.Toolbar.EditGroup();
		editGroup.displayRemoveButton = this.allowsMultipleValues; // remove button if we have a list
		editGroup._init( editableValue );

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

	destroy: function() {
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

		this._subject.append( newValueElem );
		var newValue = this._initSingleValue( newValueElem );

		if ( !this.allowsFullErase ) { // on allowsFullErase, add button will be hidden when not in use
			this._toolbar.btnAdd.setDisabled( true ); // disable 'add' button...
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
		this._toolbar.btnAdd.setDisabled( false ); // ...until stop editing new item
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
		return $( '<span/>' );
	},

	/**
	 * Returns all EditableValue objects managed by this.
	 *
	 * @param bool getPendingValues if set to true, also pending values not yet stored will be returned.
	 * @return wikibase.ui.PropertyEditTool.EditableValue[]
	 */
	getValues: function( getPendingValues ) {
		if( getPendingValues ) {
			return this._editableValues.slice();
		}

		var values = new Array();
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
		var values = new Array();
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

	/**
	 * Disable this property edit tool.
	 *
	 * @param wikibase.ui.EditableValue skip editable value to not disable (usually the one that
	 *                                       triggered starting edit mode)
	 * @return bool whether disabling was successful for all elements
	 */
	disable: function( skip ) {
		var success = true;
		if ( this._toolbar !== null ) {
			success = success && this._toolbar.disable();
		}
		if ( this._editableValues !== null ) {
			$.each( this._editableValues, function( i, editableValue ) {
				if ( editableValue !== skip ) {
					success = success && editableValue.disable();
				}
			} );
		}
		return success;
	},

	/**
	 * Enable this property edit tool.
	 *
	 * @return bool whether enabling was successful for all elements
	 */
	enable: function() {
		var success = true;
		if ( this._toolbar !== null ) {
			success = success && this._toolbar.enable();
		}
		if ( this._editableValues !== null ) {
			$.each( this._editableValues, function( i, editableValue ) {
				success = success && editableValue.enable();
			} );
		}
		return success;
	},

	/**
	 * Returns whether this property edit tool is disabled.
	 *
	 * @return bool true if disabled
	 */
	isDisabled: function() {
		return ( this.getElementsState() === wikibase.ui.ELEMENT_STATE.DISABLED );
	},

	/**
	 * Returns whether this property edit tool is enabled.
	 *
	 * @return bool true if enabled
	 */
	isEnabled: function() {
		return ( this.getElementsState() === wikibase.ui.ELEMENT_STATE.ENABLED );
	},

	/**
	 * Get state (disabled, enabled or mixed) of all edit tool elements (editable values and toolbar).
	 *
	 * @return number whether all elements are enabled (true), disabled (false) or have mixed states
	 */
	getElementsState: function() {
		var disabled = true, enabled = true;

		// check editableValues
		$.each( this._editableValues, function( i, editableValue ) {
			if ( editableValue.isDisabled() ) {
				enabled = false;
			} else if ( !editableValue.isDisabled() ) {
				disabled = false;
			}
		} );
		// check toolbar
		if ( this.allowsMultipleValues && !this.isFull() ) { // disabled anyhow independently
			enabled = enabled && this._toolbar.isEnabled();
			disabled = disabled && this._toolbar.isDisabled();
		}

		if ( disabled === true ) {
			return wikibase.ui.ELEMENT_STATE.DISABLED;
		} else if ( enabled === true ) {
			return wikibase.ui.ELEMENT_STATE.ENABLED;
		} else {
			return wikibase.ui.ELEMENT_STATE.MIXED;
		}
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
	allowsFullErase: false
};
