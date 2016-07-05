( function( wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying and editing a `wikibase.datamodel.SnakList` object.
 * @see wikibase.datamodel.SnakList
 * @class jQuery.wikibase.snaklistview
 * @extends jQuery.ui.TemplatedWidget
 * @uses jQuery.wikibase.listview
 * @since 0.4
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.SnakList} [value=new wikibase.datamodel.SnakList()]
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
	 * @property {jQuery.wikibase.listview}
	 * @private
	 */
	_listview: null,

	/**
	 * Short-cut to the `ListItemAdapter` in use with the `listview` widget used to manage the
	 * `snakview` widgets.
	 * @property {jQuery.wikibase.listview.ListItemAdapter}
	 * @private
	 */
	_lia: null,

	/**
	 * Whether the `snaklistview` currently is in edit mode.
	 * @property {boolean} [_isInEditMode=false]
	 */
	_isInEditMode: false,

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		this.options.value = this.options.value || new wb.datamodel.SnakList();

		if ( !this.options.getListItemAdapter || !( this.options.value instanceof wb.datamodel.SnakList ) ) {
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
			listItemAdapter: this.options.getListItemAdapter( function( snakview ) {
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
		.on( this._lia.prefixedEvent( 'change.' ) + this.widgetName, function( event ) {
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
	 * @since 0.5
	 */
	_updatePropertyLabels: function() {
		if ( this.options.singleProperty ) {
			this._listview.value().forEach( function ( snakview, index ) {
				var operation = index ? 'hidePropertyLabel' : 'showPropertyLabel';
				snakview[operation]();
			} );
		}
	},

	/**
	 * Starts the widget's edit mode.
	 */
	startEditing: function() {
		if ( this._isInEditMode ) {
			return;
		}

		this._listview.startEditing();

		this.element.addClass( 'wb-edit' );
		this._isInEditMode = true;

		this._trigger( 'afterstartediting' );
	},

	/**
	 * Stops the widget's edit mode.
	 *
	 * @param {boolean} [dropValue=false] If `true`, the widget's value will be reset to the one from
	 *        before edit mode was started
	 */
	stopEditing: function( dropValue ) {
		if ( !this._isInEditMode ) {
			return;
		}

		this.element.removeClass( 'wb-error' );
		this.disable();

		if ( dropValue ) {
			// If the whole item was pending, remove the whole list item. This has to be
			// performed in the widget using the snaklistview.

			// Re-create the list view to restore snakviews that have been removed during
			// editing:
			this._createListView();
		} else {
			this._listview.value().forEach( function( snakview ) {
				snakview.stopEditing( dropValue );

				// After saving, the property should not be editable anymore.
				snakview.options.locked.property = true;
			} );
		}

		this.enable();

		this.element.removeClass( 'wb-edit' );
		this._isInEditMode = false;

		this._trigger( 'afterstopediting', null, [ dropValue ] );
	},

	/**
	 * Cancels editing. (Short-cut for `stopEditing( true )`.)
	 */
	cancelEditing: function() {
		return this.stopEditing( true ); // stop editing and drop value
	},

	/**
	 * Sets a new `SnakList` or returns the current `SnakList` (including pending `Snaks` not yet
	 * committed).
	 *
	 * @param {wikibase.datamodel.SnakList} [snakList]
	 * @return {wikibase.datamodel.SnakList|undefined|null}
	 */
	value: function( snakList ) {
		if ( snakList !== undefined ) {
			return this.option( 'value', snakList );
		}

		var valid = true;
		var snaks = [];

		this._listview.value().forEach( function( snakview ) {
			var snak = snakview.snak();
			if ( !snak ) {
				valid = false;
			} else {
				snaks.push( snak );
			}
		} );

		return valid ? new wb.datamodel.SnakList( snaks ) : null;
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
	enterNewItem: function() {
		var self = this;
		return this._listview.enterNewItem().done( function() {
			self.startEditing();
		} );
	},

	/**
	 * @return {boolean}
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		this._listview.destroy();
		this._listview = null;
		this._lia = null;
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @inheritdoc
	 *
	 * @throws {Error} when trying to set the value to something other than a
	 *         `wikibase.datamodel.SnakList` instance.
	 */
	_setOption: function( key, value ) {
		if ( key === 'value' ) {
			if ( !( value instanceof wb.datamodel.SnakList ) ) {
				throw new Error( 'value has to be an instance of wikibase.datamodel.SnakList' );
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
	focus: function() {
		var $items = this._listview.items();

		if ( $items.length ) {
			this._lia.liInstance( $items.first() ).focus();
		} else {
			this.element.focus();
		}
	}

} );

}( wikibase, jQuery ) );
