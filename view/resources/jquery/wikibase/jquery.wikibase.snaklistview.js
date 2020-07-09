( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * View for displaying and editing a `datamodel.SnakList` object.
	 *
	 * @see datamodel.SnakList
	 * @class jQuery.wikibase.snaklistview
	 * @extends jQuery.ui.EditableTemplatedWidget
	 * @uses jQuery.wikibase.listview
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {datamodel.SnakList} [value=new datamodel.SnakList()]
	 *        The `SnakList` to be displayed by this view.
	 * @param {Function} options.getListItemAdapter
	 * @param {boolean} [singleProperty=true]
	 *        If `true`, it is assumed that the widget is filled with `Snak`s featuring a single common
	 *        property.
	 * @param {Function} removeCallback A function that removes this snaklistview
	 */
	/**
	 * @event afterstartediting
	 * Triggered after having started the widget's edit mode.
	 * @param {jQuery.Event} event
	 */
	/**
	 * @event afterstopediting
	 * Triggered after having stopped the widget's edit mode.
	 * @param {jQuery.Event} event
	 * @param {boolean} If `true`, the widget's value was reset to the one from before edit mode was
	 *        started.
	 */
	/**
	 * @event change
	 * Triggered whenever the widget's content is changed.
	 * @param {jQuery.Event} event
	 */
	$.widget( 'wikibase.snaklistview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			template: 'wikibase-snaklistview',
			templateParams: [
				'' // listview widget
			],
			templateShortCuts: {
				$listview: '.wikibase-snaklistview-listview'
			},
			value: null,
			singleProperty: false,
			getListItemAdapter: null,
			removeCallback: null
		},

		/**
		 * Short-cut to the `listview` widget used by the `snaklistview` to manage the `snakview`
		 * widgets.
		 *
		 * @property {jQuery.wikibase.listview}
		 * @private
		 */
		_listview: null,

		/**
		 * Short-cut to the `ListItemAdapter` in use with the `listview` widget used to manage the
		 * `snakview` widgets.
		 *
		 * @property {jQuery.wikibase.listview.ListItemAdapter}
		 * @private
		 */
		_lia: null,

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} if a required option is not specified properly.
		 */
		_create: function () {
			this.options.value = this.options.value || new datamodel.SnakList();

			if ( !this.options.getListItemAdapter || !( this.options.value instanceof datamodel.SnakList ) ) {
				throw new Error( 'Required option not specified properly' );
			}

			PARENT.prototype._create.call( this );

			if ( !this.options.value.length ) {
				this.$listview.addClass( 'wikibase-snaklistview-listview-new' );
			}

			this._createListView();
		},

		/**
		 * (Re-)creates the `listview` widget managing the `snakview` widgets.
		 *
		 * @private
		 */
		_createListView: function () {
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
				listItemAdapter: this.options.getListItemAdapter( function ( snakview ) {
					self._listview.removeItem( snakview.element );
					if ( self.value().length === 0 ) {
						self.options.removeCallback();
					} else {
						self._trigger( 'change' );
					}
				} ),
				value: this.options.value.toArray()
			} );

			if ( $listviewParent ) {
				this.$listview.appendTo( $listviewParent );
			}

			this._listview = this.$listview.data( 'listview' );
			this._lia = this._listview.listItemAdapter();
			this._updatePropertyLabels();

			this.$listview
			.off( '.' + this.widgetName )
			.on(
				this._lia.prefixedEvent( 'change.' ) + this.widgetName
				// FIXME: Remove all itemremoved events, see https://gerrit.wikimedia.org/r/298766.
				+ ' listviewitemremoved.' + this.widgetName,
				function ( event ) {
					// Forward the "change" event to external components (e.g. the edit toolbar).
					self._trigger( 'change' );
				}
			);
		},

		/**
		 * Updates the visibility of the `snakview`s' `Property` labels. (Effective only if the
		 * `singleProperty` option is set.)
		 *
		 * @private
		 */
		_updatePropertyLabels: function () {
			if ( this.options.singleProperty ) {
				this._listview.value().forEach( function ( snakview, index ) {
					var operation = index ? 'hidePropertyLabel' : 'showPropertyLabel';
					snakview[ operation ]();
				} );
			}
		},

		/**
		 * Starts the widget's edit mode.
		 *
		 * @return {Object} jQuery.Promise
		 *         No resolved parameters.
		 *         Rejected parameters:
		 *         - {Error}
		 */
		_startEditing: function () {
			return this._listview.startEditing();
		},

		/**
		 * Stops the widget's edit mode.
		 *
		 * @param {boolean} [dropValue=false] If `true`, the widget's value will be reset to the one from
		 *        before edit mode was started
		 */
		_stopEditing: function ( dropValue ) {
			if ( dropValue ) {
				// If the whole item was pending, remove the whole list item. This has to be
				// performed in the widget using the snaklistview.

				// Re-create the list view to restore snakviews that have been removed during
				// editing:
				this._createListView();
			} else {
				this._listview.value().forEach( function ( snakview ) {
					snakview.stopEditing( dropValue );

					// After saving, the property should not be editable anymore.
					snakview.options.locked.property = true;
				} );
			}
			return $.Deferred().resolve().promise();
		},

		/**
		 * Sets a new `SnakList` or returns the current `SnakList` (including pending `Snaks` not yet
		 * committed).
		 *
		 * @param {datamodel.SnakList} [snakList]
		 * @return {datamodel.SnakList|undefined|null}
		 */
		value: function ( snakList ) {
			if ( snakList !== undefined ) {
				return this.option( 'value', snakList );
			}

			var snaks = [];

			if ( !this._listview.value().every( function ( snakview ) {
				var snak = snakview.snak();
				snaks.push( snak );
				return snak;
			} ) ) {
				return null;
			}

			return new datamodel.SnakList( snaks );
		},

		/**
		 * Adds a new empty `snakview` to the `listview` with edit mode started instantly.
		 *
		 * @see jQuery.wikibase.listview.enterNewItem
		 *
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {jQuery} return.done.$snakview
		 */
		enterNewItem: function () {
			var self = this;
			return this._listview.enterNewItem().done( function () {
				self.startEditing();
			} );
		},

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			this._listview.destroy();
			this._listview = null;
			this._lia = null;
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @inheritdoc
		 *
		 * @throws {Error} when trying to set the value to something other than a
		 *         `datamodel.SnakList` instance.
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value' ) {
				if ( !( value instanceof datamodel.SnakList ) ) {
					throw new Error( 'value has to be an instance of datamodel.SnakList' );
				}
				this._listview.value( value.toArray() );
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' ) {
				this._listview.option( key, value );
			}

			return response;
		},

		/**
		 * @inheritdoc
		 */
		focus: function () {
			var $items = this._listview.items();

			if ( $items.length ) {
				this._lia.liInstance( $items.first() ).focus();
			} else {
				this.element.trigger( 'focus' );
			}
		}

	} );

}( wikibase ) );
