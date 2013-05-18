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
 * @event change: Triggered whenever the snaklistview's content is changed.
 *        (1) {jQuery.Event} event
 *
 * @event disable: Triggered whenever the snaklistview gets disabled.
 *        (1) {jQuery.Event} event
 *
 * @event enable: Triggered whenever the snaklistview gets enabled.
 *        (1) {jQuery.Event} event
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
			'' // listview widget
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
		.off( '.' + this.widgetName )
		.on( 'listviewitemadded.' + this.widgetName, function( event, value, $newLi ) {
			// Listen to all the snakview "change" events to be able to determine whether the
			// snaklistview itself is valid.
			$newLi.on( self._lia.prefixedEvent( 'change' ), function() {
				// Forward the "change" event to external components (e.g. the edit toolbar).
				self._trigger( 'change' );
			} );
		} )
		.on( this._lia.prefixedEvent( 'change.' ) + this.widgetName +
			' listviewitemremoved.' + this.widgetName, function() {
				// Forward the "change" event to external components (e.g. the edit toolbar).
				self._trigger( 'change' );
			}
		);

		this._attachEditModeEventHandlers();
	},

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
		natively: function() {
			var self = this;

			$.each( this._listview.items(), function( i, item ) {
				var snakview = self._lia.liInstance( $( item ) );
				snakview.startEditing();
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
			/*jshint unused:false */
			if( !this.isInEditMode() ) {
				e.cancel();
			}

			this.element.removeClass( 'wb-error' );
		},
		natively: function( e, dropValue ) {
			var self = this;

			this._detachEditModeEventHandlers();

			this.disable();

			if ( dropValue ) {
				// If the whole item was pending, remove the whole list item. This has to be
				// performed in the widget using the snaklistview.

				// Re-create the list view to restore snakviews that have been removed during
				// editing:
				this.createListView();
			} else {
				$.each( this._listview.items(), function( i, item ) {
					var $item = $( item ),
						snakview = self._lia.liInstance( $item );

					snakview.stopEditing( dropValue );

					// After saving, the property should not be editable anymore.
					snakview.options.locked.property = true;
				} );
			}

			this.enable();

			this.element.removeClass( 'wb-edit' );
			this._isInEditMode = false;

			// Transform toolbar and snak view after save complete
			this._trigger( 'afterstopediting', null, [ dropValue ] );
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
	 * Attaches event listeners that shall trigger stopping the snaklistview's edit mode.
	 * @since 0.4
	 */
	_attachEditModeEventHandlers: function() {
		/*jshint unused:false */
		var self = this;

		this.$listview.one( this._lia.prefixedEvent( 'stopediting.' + this.widgetName ),
			function( event, dropValue, newSnak ) {
				event.stopImmediatePropagation();
				event.preventDefault();
				self._detachEditModeEventHandlers();
				self._attachEditModeEventHandlers();
				self.stopEditing( dropValue );
			}
		);
	},

	/**
	 * Detaches event listeners that shall trigger stopping the snaklistview's edit mode.
	 * @since 0.4
	 */
	_detachEditModeEventHandlers: function() {
		this.$listview.off( this._lia.prefixedEvent( 'stopediting.' + this.widgetName ) );
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
		if ( snakList ) { // setter:
			if ( !( snakList instanceof wb.SnakList ) ) {
				throw new Error( 'Value has to be an instance of wikibase.SnakList' );
			}

			this._snakList = snakList;
			this.createListView();
			return this._snakList;
		}
		// getter:
		var self = this,
			snaks = [];

		$.each( this._listview.items(), function( i, item ) {
			var liInstance = self._lia.liInstance( $( item ) );
			if ( liInstance.snak() ) {
				snaks.push( liInstance.snak() );
			}
		} );

		return ( snaks.length > 0 ) ? new wb.SnakList( snaks ): null;
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

		$.each( this._listview.items(), function( i, item ) {
			var snakview = self._lia.liInstance( $( item ) );

			if ( !snakview.isValid() || !snakview.snak() ) {
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
		this._listview.addItem();

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
		} );
		this._trigger( 'disable' );
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
			}
		} );
		this._trigger( 'enable' );
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
