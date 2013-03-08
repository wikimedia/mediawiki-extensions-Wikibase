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
 * @option value {wb.Reference|null} The reference displayed by this view. This can only be set
 *         initially, the value function doesn't work as a setter in this view. If this is null,
 *         the view will start edit mode upon initialization.
 *
 * @option helpMessage {string} End-user message explaining how to use the referenceview widget. The
 *         message is most likely to be used inside the tooltip of the toolbar corresponding to
 *         the referenceview.
 *
 * @event startediting: Triggered when starting the referenceview's edit mode.
 *        (1) {jQuery.Event}
 *
 * @event afterstartediting: Triggered after having started the referenceview's edit mode.
 *        (1) {jQuery.Event}
 *
 * @event stopediting: Triggered when stopping the referenceview's edit mode.
 *        (1) {jQuery.Event}
 *        (2) {boolean} If true, the value from before edit mode has been started will be reinstated
 *            (basically a cancel/save switch).
 *
 * @event afterstopediting: Triggered after having stopped the referenceview's edit mode.
 *        (1) {jQuery.Event}
 *        (2) {boolean} If true, the value from before edit mode has been started will be reinstated
 *            (basically a cancel/save switch).
 *
 * @event remove: Triggered when removing the reference/referenceview.
 *        (1) {jQuery.Event} event
 *
 * @event change: Triggered whenever the referenceview's content is changed.
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
	 * Reference object represented by this view.
	 * @type {wb.Reference}
	 */
	_reference: null,

	/**
	 * @type {wb.listview}
	 */
	_listview: null,

	/**
	 * Whether the referenceview is currently in edit mode.
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this;
		this._reference = this.option( 'value' );

		PARENT.prototype._create.call( this );

		if ( !this._reference ) {
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
							// when editing existing reference, don't allow changing property!
							property: !!value
						}
					};
				}
			} ),
			value: ( this.option( 'value' ) ) ? this.option( 'value' ).getSnaks().toArray() : null
		} )
		.on( 'listviewitemadded', function( event, value, $newLi ) {
			// Listen to all the snakview "change" events to be able to determine whether the
			// reference itself is valid.
			$newLi.on( self._listview.listItemAdapter().prefixedEvent( 'change' ), function( event ) {
				// Forward the "change" event to external components (e.g. the edit toolbar).
				self._trigger( 'change' );
			} );
		} );

		this._listview = this.$listview.data( 'listview' );

		this.$listview
		.on( self._listview.listItemAdapter().prefixedEvent( 'change' ), function( event ) {
			// Forward the "change" event to external components (e.g. the edit toolbar).
			self._trigger( 'change' );
		} )
		.on( self._listview.listItemAdapter().prefixedEvent( 'stopediting' ),
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
	 * Returns whether the reference is valid according to its current contents.
	 *
	 * @return {boolean}
	 */
	isValid: function() {
		var self = this,
			isValid = true;

		$.each( this._listview.items(), function( i, item ) {
			if (
				!self._listview.listItemAdapter().liInstance( $( item ) ).isValid()
				// This is temporary while just having a single list item:
				|| self._listview.listItemAdapter().liInstance( $( item ) ).isInitialSnak()
			) {
				isValid = false;
				return false;
			}
		} );

		return isValid;
	},

	/**
	 * Starts the reference's edit mode.
	 * @since 0.4
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	startEditing: $.NativeEventHandler( 'startEditing', {
		// don't start edit mode or trigger event if in edit mode already:
		initially: function( e ) {
			if( this.isInEditMode() ) {
				e.cancel();
			}
		},
		// start edit mode if event doesn't prevent default:
		natively: function( e ) {
			var self = this;//,

			$.each( this._listview.items(), function( i, item ) {
				self._listview.listItemAdapter().liInstance( $( item ) ).startEditing();
			} );

			this.element.addClass( 'wb-edit' );
			this._isInEditMode = true;

			this._trigger( 'afterstartediting' );
		}
	} ),

	/**
	 * Exits the reference's edit mode.
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
			if( !this.isInEditMode() || !this.isValid() && !dropValue ) {
				e.cancel();
			}

			this.__continueStopEditing = true;

			this.element.removeClass( 'wb-error' );
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue ) {
			var self = this;

			this.disable();

			if ( dropValue ) {
				// If the whole item was pending, remove the whole list item. This is performed
				// in the parent statementview widget which manages the list of references.

				// Stop edit mode for all snaks.
				self.__continueSnakviewStopEditing = true;
				$.each( this._listview.items(), function( i, item ) {
					self._listview.listItemAdapter().liInstance( $( item ) ).stopEditing( dropValue );
				} );
				self.__continueSnakviewStopEditing = false;

				self.enable();
				self.element.removeClass( 'wb-edit' );
				self._isInEditMode = false;

				self._trigger( 'afterstopediting', null, [ dropValue ] );
			} else if ( this._reference !== null ) {
				// editing an existing reference
				self._saveReferenceApiCall()
				.done( function( savedReference, pageInfo ) {
					self.__continueSnakviewStopEditing = true;
					$.each( self._listview.items(), function( i, item ) {
						self._listview.listItemAdapter()
							.liInstance( $( item ) ).stopEditing( dropValue );
					} );
					self.__continueSnakviewStopEditing = false;

					self.enable();

					self._reference = savedReference;

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
				// Adding a new reference is managed in statementview and will end up in here after
				// having performed the API call adding the reference.
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
	 * Removes the snaklistview.
	 */
	remove: $.NativeEventHandler( 'remove', {
		// TODO: Removing should probably be managed by the object containing the snaklistview.
		// ($.snaklistview may be used stand-alone)
		initially: function( e ) {
			// Do not trigger event if not in edit mode.
			if( !this.isInEditMode() ) {
				e.cancel();
			}
		},
		// Start edit mode if event doesn't prevent default:
		natively: function( e ) {
			var self = this;

			this._removeReferenceApiCall()
			.done( function( pageInfo ) {
				self._trigger( 'afterremove' );
			} ).fail( function( errorCode, details ) {
				var error = wb.RepoApiError.newFromApiResponse( errorCode, details, 'remove' );

				self.enable();
				self.element.addClass( 'wb-error' );

				self._trigger( 'toggleError', null, [ error ] );
			} );
		}
	} ),

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
			self._listview.listItemAdapter().liInstance( $( item ) ).disable();
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
			if ( self._listview.listItemAdapter().liInstance( $( item ) ) ) {
				self._listview.listItemAdapter().liInstance( $( item ) ).enable();
			}
		} );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.$listview.data( 'listview' ).destroy();
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Returns the current reference represented by the view. If null is returned, than this is a
	 * fresh view where a new reference is being constructed.
	 * @since 0.4
	 *
	 * @return wb.Reference|null
	 */
	value: function() {
		return this._reference;
	},

	/**
	 * @see jQuery.widget._setOption
	 * We are using this to disallow changing the value option afterwards.
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}
		$.Widget.prototype._setOption.call( key, value );
	},

	/**
	 * Will insert a new list member into the list. The new list member will be a Widget of the type
	 * displayed in the list, but without value, so the user can specify a value.
	 *
	 * @since 0.4
	 */
	enterNewItem: function() {
		var $newLi = this._listview.addItem();
		this.startEditing();
	}

} );

}( mediaWiki, wikibase, jQuery ) );
