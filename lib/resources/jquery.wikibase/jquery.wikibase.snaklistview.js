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
 *
 * @option helpMessage {string} End-user message explaining how to use the snaklistview widget. The
 *         message is most likely to be used inside the tooltip of the toolbar corresponding to
 *         the snaklistview.
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
	 * (Additional) default options
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
	 * Whether the snaklistview is currently in edit mode.
	 * @type {boolean}
	 */
	_isInEditMode: false,

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
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this;

		this._snakList = this.option( 'value' );

		PARENT.prototype._create.call( this );

		if ( !this.option( 'value' ) ) {
			this.$listview.addClass( 'wb-snaklistview-listview-new' );
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
			value: ( this.option( 'value' ) ) ? this.option( 'value' ).toArray() : null
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
		.on( self._lia.prefixedEvent( 'change' ), function( event ) {
			// Forward the "change" event to external components (e.g. the edit toolbar).
			self._trigger( 'change' );
		} )
		.on( self._lia.prefixedEvent( 'stopediting' ),
			function( event, dropValue, newSnak ) {
				if ( !self.isValid() && !dropValue ) {
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
			var self = this;//,

			$.each( this._listview.items(), function( i, item ) {
				self._lia.liInstance( $( item ) ).startEditing();
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
		// don't stop edit mode or trigger event if not in edit mode currently:
		initially: function( e, dropValue ) {
			if( !this.isInEditMode() || !this.isValid() && !dropValue && !this.__continueStopEditing ) {
				e.cancel();
			}

			this.element.removeClass( 'wb-error' );
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue ) {
			var self = this;

			this.disable();

			if ( dropValue ) {
				// If the whole item was pending, remove the whole list item. This has to be
				// performed in the widget using the snaklistview.

				// Stop edit mode for all snaks.
				self.__continueSnakviewStopEditing = true;
				$.each( this._listview.items(), function( i, item ) {
					self._lia.liInstance( $( item ) ).stopEditing( dropValue );
				} );
				self.__continueSnakviewStopEditing = false;

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
						self._lia.liInstance( $( item ) ).stopEditing( dropValue );
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
			} else if ( self.__continueStopEditing ) {
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
	 * Short-cut for stopEditing( false ). Exits edit mode and restores the value from before the
	 * edit mode has been started.
	 * @since 0.4
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	cancelEditing: function() {
		return this.stopEditing( true ); // stop editing and drop value
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
	 * Returns whether all of the snalistview's snaks are currently valid.
	 *
	 * @return {boolean}
	 */
	isValid: function() {
		var self = this,
			isValid = true;

		$.each( this._listview.items(), function( i, item ) {
			if (
				!self._lia.liInstance( $( item ) ).isValid()
				// This is temporary while just having a single list item:
				|| self._lia.liInstance( $( item ) ).isInitialSnak()
			) {
				isValid = false;
				return false;
			}
		} );

		return isValid;
	},

	/**
	 * Will insert a new list member into the list. The new list member will be a widget of the type
	 * displayed in the list, but without value, so the user can specify a value.
	 * @since 0.4
	 */
	enterNewItem: function() {
		var $newLi = this._listview.addItem();
		this.startEditing();
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
			self._lia.liInstance( $( item ) ).disable();
		} );
	},

	/**
	 * Enables the snaklistview.
	 * @since 0.4
	 */
	enable: function() {
		var self = this;
		$.each( this._listview.items(), function( i, item ) {
			// Item might be about to be removed.
			if ( self._lia.liInstance( $( item ) ) ) {
				self._lia.liInstance( $( item ) ).enable();
			}
		} );
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
		// TODO: The value should not be set from outside after the initialization because
		// currently, the widget lacks a mechanism to update the value. However, as long as
		// referenceview inherits from snaklistview, the easiest way to pass the snakList in the
		// "create" function to the snaklistview is to overwrite the value option (which is a
		// wb.Reference object) with the snak list of the wb.Reference object.
		//if( key === 'value' ) {
		//	throw new Error( 'Can not set value after initialization' );
		//}
		$.Widget.prototype._setOption.call( this, key, value );
	}

} );

}( mediaWiki, wikibase, jQuery ) );
