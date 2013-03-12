/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing a list of snaks (wikibase.Snak objects).
 * @since 0.4
 *
 * @option value {wb.SnakList|null} The list of snaks displayed by this view. This should only be
 *         set initially. If this is null, the view will start edit mode upon initialization.
 *         Default: null
 *
 * @option helpMessage {string} End-user message explaining how to use the snaklistview widget. The
 *         message is most likely to be used inside the tooltip of the toolbar corresponding to
 *         the snaklistview.
 *         Default:  mw.msg( 'wikibase-claimview-snak-new-tooltip' )
 *
 * @event startediting: Triggered when starting the snaklistview's edit mode.
 *        (1) {jQuery.Event}
 *
 * @event afterstartediting: Triggered after having started the snaklistview's edit mode.
 *        (1) {jQuery.Event}
 *
 * @event stopediting: Triggered when stopping the snaklistview's edit mode.
 *        (1) {jQuery.Event}
 *        (2) {boolean} If true, the value from before edit mode has been started will be reinstated
 *            (basically a cancel/save switch).
 *
 * @event afterstopediting: Triggered after having stopped the snaklistview's edit mode.
 *        (1) {jQuery.Event}
 *        (2) {boolean} If true, the value from before edit mode has been started will be reinstated
 *            (basically a cancel/save switch).
 *
 * @event remove: Triggered when removing the snak list/snaklistview.
 *        (1) {jQuery.Event} event
 *
 * @event change: Triggered whenever the snaklistview's content is changed.
 *        (1) {jQuery.Event} event
 *
 * @event toggleerror: Triggered when an error occurred or is resolved.
 *        (1) {jQuery.Event} event
 *        (2) {wb.RepoApiError|undefined} wb.RepoApiError object if an error occurred, undefined if
 *            the current error state is resolved.
 */
$.widget( 'wikibase.snaklistview', PARENT, {
	widgetBaseClass: 'wb-snaklistview',

	/**
	 * (Additional) default options.
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-snaklistview',
		templateParams: [
			'', // listview widget
			''  // edit section DOM
		],
		templateShortCuts: {
			'$listview': '.wb-snaklistview-listview'
		},
		value: null,
		helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' )
	},

	/**
	 * The list of snaks represented by this widget. This variable is not updated while in edit
	 * mode and does not contain temporary snaks. To get the temporary snaks, value() should be
	 * used. As soon as the snaklistview's save operation via stopEditing() is performed, this
	 * variable gets updated.
	 * @type {wb.SnakList}
	 */
	_snakList: null,

	/**
	 * Shortcut to the listview widget used by the snaklistview to manage the snakview widgets.
	 * @type {jQuery.wikibase.listview}
	 */
	_listview: null,

	/**
	 * Shortcut to the list item adapter in use with the listview widget used to manage the
	 * snakview widgets.
	 * @type {jquery.wikibase.listview.ListItemAdapter}
	 */
	_lia: null,

	/**
	 * Whether the snaklistview is currently in edit mode.
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		this._snakList = this.option( 'value' );

		PARENT.prototype._create.call( this );

		if ( !this.option( 'value' ) ) {
			this.$listview.addClass( 'wb-snaklistview-listview-new' );
		}

		this.createListView();
	},

	/**
	 * (Re-)creates the listview widget managing the snakview widgets.
	 * @since 0.4
	 */
	createListView: function() {
		var self = this;

		// Re-create listview widget if it exists already
		if ( this._listview ) {
			this._listview.destroy();
			this.$listview.empty();
		}

		this.$listview.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.snakview,
				listItemWidgetValueAccessor: 'value',
				newItemOptionsFn: function( value ) {
					return {
						value: value || {
							property: null,
							snaktype: wb.PropertyValueSnak.TYPE
						},
						locked: {
							// Do not allow changing the property when editing existing an snak.
							property: !!value
						}
					};
				}
			} ),
			value: ( this._snakList ) ? this._snakList.toArray() : null
		} );

		this._listview = this.$listview.data( 'listview' );
		this._lia = this._listview.listItemAdapter();

		this.$listview
		.on( 'listviewitemadded', function( event, value, $newLi ) {
			// Listen to all the snakview "change" events to be able to determine whether the
			// snaklistview itself is valid.
			$newLi.on( self._lia.prefixedEvent( 'change' ), function( event ) {
				// Forward the "change" event to external components (e.g. the edit toolbar).
				self._trigger( 'change' );
			} );
		} )
		.on( self._lia.prefixedEvent( 'change' ) + ' listviewitemremoved', function( event ) {
			// Forward the "change" event to external components (e.g. the edit toolbar).
			self._trigger( 'change' );
		} )
		.on( self._lia.prefixedEvent( 'stopediting' ),
			function( event, dropValue, newSnak ) {
				if (
					!self.isValid() && !this.isInitialValue()
					&& !dropValue && !self.__continueSnakviewStopEditing
				) {
					event.preventDefault();
					return;
				}
				if ( !self.__continueSnakviewStopEditing ) {
					event.preventDefault();
					self.stopEditing( dropValue );
				}
			}
		);
	},

	/**
	 * Saves the snaks represented by this snaklistview.
	 *
	 * @params {function} Callback to be processed after saving was successful
	 * @return {jQuery.Promise}
	 */
	_saveSnakList: wb.utilities.abstractMember,

	/**
	 * Starts the snaklistview's edit mode by starting the edit mode of all the list's snakviews.
	 * @since 0.4
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	startEditing: $.NativeEventHandler( 'startEditing', {
		initially: function( e ) {
			if( this.isInEditMode() ) {
				e.cancel();
			}
		},
		natively: function( e ) {
			var self = this;

			$.each( this._listview.items(), function( i, item ) {
				var snakview = self._lia.liInstance( $( item ) );

				snakview.startEditing();

				self._createRemoveToolbar( $( item ) );
			} );

			this.element.addClass( 'wb-edit' );
			this._isInEditMode = true;

			this._trigger( 'afterstartediting' );
		}
	} ),

	/**
	 * Exits the snaklistview's edit mode.
	 * @since 0.4
	 *
	 * @param {boolean} [dropValue] If true, the value from before edit mode has been started will
	 *        be reinstated - basically a cancel/save switch. "false" by default. Consider using
	 *        cancelEditing() instead.
	 * @return {undefined} (allows chaining widget calls)
	 */
	stopEditing: $.NativeEventHandler( 'stopEditing', {
		initially: function( e, dropValue ) {
			if(
				!this.isInEditMode()
				|| !this.isValid() && !this.isInitialValue()
				&& !dropValue && !this.__continueStopEditing
			) {
				e.cancel();
			}

			this.element.removeClass( 'wb-error' );
		},
		natively: function( e, dropValue ) {
			var self = this;

			this.disable();

			if ( dropValue ) {
				// If the whole item was pending, remove the whole list item. This has to be
				// performed in the widget using the snaklistview.

				self.__continueSnakviewStopEditing = false;

				// Re-create the list view to restore snakviews that have been removed during
				// editing:
				self.createListView();

				self.enable();
				self.element.removeClass( 'wb-edit' );
				self._isInEditMode = false;

				self._trigger( 'afterstopediting', null, [ dropValue ] );
			} else if ( this.option( 'value' ) !== null ) {
				// Editing an existing snak list.
				self._saveSnakList()
				.done( function( savedObject, pageInfo ) {
					self.__continueSnakviewStopEditing = true;
					$.each( self._listview.items(), function( i, item ) {
						var $item = $( item ),
							snakview = self._lia.liInstance( $item );

						snakview.stopEditing( dropValue );

						if ( $item.data( 'removetoolbar' ) ) {
							$item.data( 'removetoolbar' ).destroy();
							$item.removeData( 'removetoolbar' );
							$item.children( '.wb-ui-toolbar' ).remove();
						}

						// After saving, the property should not be editable anymore.
						snakview.options.locked.property = true;
					} );
					self.__continueSnakviewStopEditing = false;

					self.enable();

					self.element.removeClass( 'wb-edit' );
					self._isInEditMode = false;

					// Transform toolbar and snak view after save complete
					self._trigger( 'afterstopediting', null, [ dropValue ] );
				} )
				.fail( function( errorCode, details ) {
					var error = wb.RepoApiError.newFromApiResponse(
							errorCode, details, 'save'
						);

					self.enable();
					self.element.addClass( 'wb-error' );

					self._trigger( 'toggleError', null, [ error ] );

					self.__continueStopEditing = false;
				} );
			} else {
				self.__continueStopEditing = false;
				// Creating a new snaklistview is managed in the object using the snaklistview (e.g.
				// in the statementview). Creating a snaklistview will end up in here after having
				// performed the API call adding the snaklistview's subject (e.g. the reference) to
				// the permanent part of the list (by saving the subject to the database).
				self.element.removeClass( 'wb-edit' );
				this._isInEditMode = false;
				self._trigger( 'afterstopediting', null, [ dropValue ] );
			}
		}
	} ),

	/**
	 * Short-cut for stopEditing(false). Exits edit mode and restores the value from before the
	 * edit mode has been started.
	 * @since 0.4
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	cancelEditing: function() {
		return this.stopEditing( true ); // stop editing and drop value
	},

	/**
	 * Creates a link to remove a snakview from the list. The snakview will just be removed
	 * visually which, however, involves dropping the snak permanently when saving the snak list.
	 * @since 0.4
	 *
	 * @param {jQuery} $item List item node
	 */
	_createRemoveToolbar: function( $item ) {
		var self = this,
			$toolbarParent = mw.template( 'wb-toolbar', '' ).appendTo( $item ),
			toolbar = new wb.ui.Toolbar();

		toolbar.innerGroup = new wb.ui.Toolbar.Group();
		toolbar.btnRemove = new wb.ui.Toolbar.Button( mw.msg( 'wikibase-remove' ) );
		toolbar.innerGroup.addElement( toolbar.btnRemove );
		toolbar.addElement( toolbar.innerGroup );

		$( toolbar.btnRemove ).on( 'action', function( event ) {
			self._listview.removeItem( $item );
		} );

		toolbar.appendTo(
			$( '<div/>' ).addClass( 'wb-editsection' ).appendTo( $toolbarParent )
		);

		$item.data( 'removetoolbar', toolbar );
	},

	/**
	 * Sets/Returns the current list of snaks represented by the view. If there are no snaks, null
	 * will be returned.
	 * @since 0.4
	 *
	 * @param {wb.SnakList} [snakList] New snak list to be set
	 * @return {wb.SnakList|null}
	 */
	value: function( snakList ) {
		if ( snakList ) {
			if ( !( value instanceof wb.SnakList ) ) {
				throw new Error( 'Value has to be an instance of wikibase.SnakList' );
			}
			this._snakList = snakList;
			return this._snakList;
		} else {
			var self = this,
				snaks = [];

			$.each( this._listview.items(), function( i, item ) {
				var liInstance = self._lia.liInstance( $( item ) );
				if ( liInstance.snak() ) {
					snaks.push( liInstance.snak() );
				}
			} );

			return ( snaks.length > 0 ) ? new wb.SnakList( snaks ): null;
		}
	},

	/**
	 * Returns whether all of the snaklistview's snaks are currently valid and the currently listed
	 * snaks are not the same than those set initially.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isValid: function() {
		var self = this,
			isValid = true;

		if ( this._listview.items().length === 0 ) {
			return false;
		}

		$.each( this._listview.items(), function( i, item ) {
			var snakview = self._lia.liInstance( $( item ) );

			if ( !snakview.isValid() ) {
				isValid = false;
				return false;
			}
		} );

		return isValid;
	},

	/**
	 * Returns whether the current snaks of the listview are the same than the ones the snaklistview
	 * got initialized with.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isInitialValue: function() {
		var self = this,
			snakList = new wb.SnakList();

		if ( !this.isValid() || !this._snakList ) {
			return false;
		}

		$.each( this._listview.items(), function( i, item ) {
			var snakview = self._lia.liInstance( $( item ) );
			snakList.addSnak( snakview.snak() );
		} );

		return this._snakList.equals( snakList );
	},

	/**
	 * Will insert a new list member into the list. The new list member will be a widget of the type
	 * displayed in the list, but without value, so the user can specify a value.
	 * @since 0.4
	 */
	enterNewItem: function() {
		var $newLi = this._listview.addItem();

		this._createRemoveToolbar( $newLi );

		this.startEditing();

		// Since the new snakview will be initialized empty which invalidates the snaklistview,
		// external components using the snaklistview will be noticed via the "change" event.
		this._trigger( 'change' );
	},

	/**
	 * Returns whether the snaklist is editable at the moment.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Disables the snaklistview.
	 * @since 0.4
	 */
	disable: function() {
		var self = this;
		$.each( this._listview.items(), function( i, item ) {
			var $item = $( item );
			self._lia.liInstance( $item ).disable();
			if ( $item.data( 'removetoolbar' ) ) {
				$item.data( 'removetoolbar' ).disable();
			}
		} );
		this.element.data( 'addtoolbar' ).toolbar.disable();
	},

	/**
	 * Enables the snaklistview.
	 * @since 0.4
	 */
	enable: function() {
		var self = this;
		$.each( this._listview.items(), function( i, item ) {
			var $item = $( item );

			// Item might be about to be removed not being a list item instance.
			if ( self._lia.liInstance( $item ) ) {
				self._lia.liInstance( $item ).enable();
				if ( $item.data( 'removetoolbar' ) ) {
					$item.data( 'removetoolbar' ).enable();
				}
			}
		} );

		// "add" toolbar might be remove already.
		if ( this.element.data( 'addtoolbar' ) ) {
			this.element.data( 'addtoolbar' ).toolbar.enable();
		}
	},

	/**
	 * @see jQuery.widget.destroy
	 */
	destroy: function() {
		this._listview.destroy();
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @see jQuery.widget._setOption
	 */
	_setOption: function( key, value ) {
		// The value should not be set from outside after the initialization because
		// currently, the widget lacks a mechanism to update the value.
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}
		$.Widget.prototype._setOption.call( this, key, value );
	}

} );

}( mediaWiki, wikibase, jQuery ) );
