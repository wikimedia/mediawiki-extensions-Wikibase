/**
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing a list of snaks (wb.datamodel.Snak objects).
 * @since 0.4
 *
 * @option {wb.datamodel.SnakList|null} value The list of snaks displayed by this view. This should only be
 *         set initially. If this is null, the view will start edit mode upon initialization.
 *         Default: null
 *
 * @option {boolean} singleProperty If set to true, it is assumed that the widget is filled with
 *         snakviews featuring a single property only.
 *         Default: false
 *
 * @option {string} helpMessage End-user message explaining how to use the snaklistview widget. The
 *         message is most likely to be used inside the tooltip of the toolbar corresponding to
 *         the snaklistview.
 *         Default:  mw.msg( 'wikibase-claimview-snak-new-tooltip' )
 *
 * @option {wb.store.EntityStore} entityStore
 *
 * @option {wikibase.ValueViewBuilder} valueViewBuilder
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
		singleProperty: false,
		helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' ),
		entityStore: null,
		valueViewBuilder: null
	},

	/**
	 * The list of snaks represented by this widget. This variable is not updated while in edit
	 * mode and does not contain temporary snaks. To get the temporary snaks, value() should be
	 * used. As soon as the snaklistview's save operation via stopEditing() is performed, this
	 * variable gets updated.
	 * @type {wb.datamodel.SnakList}
	 */
	_snakList: null,

	/**
	 * Shortcut to the listview widget used by the snaklistview to manage the snakview widgets.
	 * @type {$.wikibase.listview}
	 */
	_listview: null,

	/**
	 * Shortcut to the list item adapter in use with the listview widget used to manage the
	 * snakview widgets.
	 * @type {$.wikibase.listview.ListItemAdapter}
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

		this._createListView();
	},

	/**
	 * (Re-)creates the listview widget managing the snakview widgets.
	 * @since 0.4
	 */
	_createListView: function() {
		var self = this,
			$listviewParent = null;

		// Re-create listview widget if it exists already
		if ( this._listview ) {
			// Detach listview since re-creation is regarded a content reset and not an
			// initialisation. Detaching prevents bubbling of initialisation events.
			$listviewParent = this.$listview.parent();
			this.$listview.detach();
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
							snaktype: wb.datamodel.PropertyValueSnak.TYPE
						},
						locked: {
							// Do not allow changing the property when editing existing an snak.
							property: !!value
						},
						entityStore: self.option( 'entityStore' ),
						valueViewBuilder: self.option( 'valueViewBuilder' )
					};
				}
			} ),
			value: ( this._snakList ) ? this._snakList.toArray() : null
		} );

		if( $listviewParent ) {
			this.$listview.appendTo( $listviewParent );
		}

		this._listview = this.$listview.data( 'listview' );
		this._lia = this._listview.listItemAdapter();
		this._updatePropertyLabels();

		this.$listview
		.off( '.' + this.widgetName )
		.on( 'listviewitemadded.' + this.widgetName, function( event, value, $newLi ) {
			// Listen to all the snakview "change" events to be able to determine whether the
			// snaklistview itself is valid.
			$newLi.on( self._lia.prefixedEvent( 'change' ), function( event ) {
				// Forward the "change" event to external components (e.g. the edit toolbar).
				self._trigger( 'change' );
			} );
		} )
		.on( this._lia.prefixedEvent( 'change.' ) + this.widgetName
			+ ' listviewafteritemmove.' + this.widgetName
			+ ' listviewitemremoved.' + this.widgetName, function( event ) {
				// Forward the "change" event to external components (e.g. the edit toolbar).
				self._trigger( 'change' );
			}
		);

		this._attachEditModeEventHandlers();
	},

	/**
	 * Updates the visibility of the snakviews' property labels which has an effect if the
	 * "singleProperty" options is set.
	 * @since 0.5
	 */
	_updatePropertyLabels: function() {
		if( this.options.singleProperty ) {
			var $items = this._listview.items();

			for( var i = 0; i < $items.length; i++ ) {
				var operation = ( i === 0 ) ? 'showPropertyLabel' : 'hidePropertyLabel';
				this._lia.liInstance( $items.eq( i ) )[operation]();
			}
		}
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
		natively: function( e ) {
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
				this._createListView();
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
	 * @param {wb.datamodel.SnakList} [snakList] New snak list to be set
	 * @return {wb.datamodel.SnakList|null}
	 */
	value: function( snakList ) {
		if ( snakList ) { // setter:
			if ( !( snakList instanceof wb.datamodel.SnakList ) ) {
				throw new Error( 'Value has to be an instance of wikibase.datamodel.SnakList' );
			}

			var wasInEditMode = this.isInEditMode();

			this._snakList = snakList;
			this._createListView();

			if( wasInEditMode ) {
				this.startEditing();
			}

			return this._snakList;
		}
		// getter:
		var listview = this.$listview.data( 'listview' ),
			snaks = [];

		$.each( listview.items(), function( i, item ) {
			var liInstance = listview.listItemAdapter().liInstance( $( item ) );
			if ( liInstance.snak() ) {
				snaks.push( liInstance.snak() );
			}
		} );

		return ( snaks.length > 0 ) ? new wb.datamodel.SnakList( snaks ): null;
	},

	/**
	 * Returns the snaks currently represented by the snaklistview.
	 * @since 0.5
	 *
	 * @return {wb.datamodel.SnakList|null}
	 */
	getSnakList: function() {
		return this.value();
	},

	/**
	 * Returns whether all of the snaklistview's snaks are currently valid and the currently listed
	 * snaks are not the same than those set initially.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isValid: function() {
		var listview = this.$listview.data( 'listview' ),
			isValid = true;

		$.each( listview.items(), function( i, item ) {
			var snakview = listview.listItemAdapter().liInstance( $( item ) );

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
			snakList = new wb.datamodel.SnakList();

		if( !this._snakList && this._listview.items().length === 0 ) {
			return true;
		} else if ( !this.isValid() || !this._snakList ) {
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
	 * @see jQuery.widget.destroy
	 */
	destroy: function() {
		this._listview.destroy();
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		// The value should not be set from outside after the initialization because
		// currently, the widget lacks a mechanism to update the value.
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this._listview.option( key, value );
		}

		return response;
	},

	/**
	 * Moves a snak within the snak list.
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Snak} snak
	 * @param {number} toIndex
	 */
	move: function( snak, toIndex ) {
		var self = this,
			snakList;

		if( snak instanceof wb.datamodel.Snak ) {
			snakList = this.getSnakList();
			if( snakList ) {
				snakList.move( snak, toIndex );
			}
		} else if( snak instanceof wb.datamodel.SnakList ) {
			snakList = snak;
		}

		if( snakList ) {
			// Reflect new snak list order in snaklistview:
			snakList.each( function( i, snak ) {
				var $listItem = self._findListItem( snak );
				if( $listItem ) {
					self._listview.move( self._findListItem( snak ), i );
				}
			} );
			self._updatePropertyLabels();
		}
	},

	/**
	 * Moves a snak towards the top of the snak list by one step.
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Snak} snak
	 */
	moveUp: function( snak ) {
		var snakList = this.getSnakList();

		if( snakList ) {
			this.move( snakList.moveUp( snak ) );
		}
	},

	/**
	 * Moves a snak towards the bottom of the snak list by one step.
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Snak} snak
	 */
	moveDown: function( snak ) {
		var snakList = this.getSnakList();

		if( snakList ) {
			this.move( snakList.moveDown( snak ) );
		}
	},

	/**
	 * Finds a snak's snakview node within the snaklistview's listview widget.
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Snak} snak
	 * @return {jQuery|null}
	 */
	_findListItem: function( snak ) {
		var self = this,
			$snakview = null;

		this._listview.items().each( function( i, itemNode ) {
			var $itemNode = $( itemNode );

			if( self._listview.listItemAdapter().liInstance( $itemNode ).snak().equals( snak ) ) {
				$snakview = $itemNode;
				return false;
			}
		} );

		return $snakview;
	}

} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.wikibase.snaklistview.prototype.widgetBaseClass = 'wb-snaklistview';

}( mediaWiki, wikibase, jQuery ) );
