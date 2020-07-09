( function () {
	'use strict';

	var PARENT = $.ui.TemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * View for displaying and editing a list of `datamodel.Statement` objects by using
	 * `jQuery.wikibase.statementview` widgets.
	 *
	 * @see jQuery.wikibase.statementview
	 * @see datamodel.Statement
	 * @see datamodel.StatementList
	 * @class jQuery.wikibase.statementlistview
	 * @extends jQuery.ui.TemplatedWidget
	 * @uses jQuery.wikibase.listview
	 * @uses jQuery.wikibase.listview.ListItemAdapter
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {datamodel.StatementList} [options.value]
	 *        The list of `Statement`s to be displayed by this view.
	 * @param {Function} options.getListItemAdapter
	 */
	/**
	 * @event afterstartediting
	 * Triggered when edit mode has been started for one of the `statementview` widgets managed by the
	 * `statementlistview`.
	 * @param {jQuery.Event} event
	 */
	/**
	 * @event afterstopediting
	 * Triggered when one of the `statementview` widgets managed by the `statementlistview` has
	 * successfully stopped edit mode.
	 * @param {jQuery.Event} event
	 * @param {boolean} dropValue If true, the value from before edit mode has been started will be
	 *        reinstated (basically, a cancel/save switch).
	 */
	/**
	 * @event afterremove
	 * Triggered after one of the `statementview` widgets managed by the `statementlistview` was removed
	 * from the `statementlistview`.
	 * @param {jQuery.Event} event
	 */
	/**
	 * @event toggleerror
	 * Triggered when one of the `statementview` widgets managed by the `statementlistview` produces an
	 * error.
	 * @param {jQuery.Event} event
	 */
	$.widget( 'wikibase.statementlistview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			template: 'wikibase-statementlistview',
			templateParams: [
				'', // listview widget
				'' // toolbar
			],
			templateShortCuts: {
				$listview: '.wikibase-statementlistview-listview'
			},
			value: null,
			getListItemAdapter: null
		},

		/**
		 * @type {jQuery.wikibase.listview}
		 * @private
		 */
		_listview: null,

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} if a required option is not specified properly.
		 */
		_create: function () {
			if ( !this.options.getListItemAdapter
				|| ( this.options.value && !( this.options.value instanceof datamodel.StatementList ) )
			) {
				throw new Error( 'Required option not specified properly' );
			}

			PARENT.prototype._create.call( this );

			this._createListView();

			var self = this,
				lia = this._listview.listItemAdapter(),
				afterStartEditingEvent
					= lia.prefixedEvent( 'afterstartediting.' + this.widgetName ),
				afterStopEditingEvent = lia.prefixedEvent( 'afterstopediting.' + this.widgetName ),
				toggleErrorEvent = lia.prefixedEvent( 'toggleerror.' + this.widgetName );

			this.element
			.on( afterStartEditingEvent, function ( event ) {
				// Forward "afterstartediting" event for higher components (e.g. statementgrouplistview)
				// to recognize that edit mode has been started.
				self._trigger( 'afterstartediting' );
			} )
			.on( afterStopEditingEvent, function ( event, dropValue ) {
				var $statementview = $( event.target ),
					statementview = lia.liInstance( $statementview );

				// Cancelling edit mode or having stopped edit mode after saving an existing (not
				// pending) statement.
				if ( dropValue || !statementview || statementview.value() ) {
					self._trigger( 'afterstopediting', null, [ dropValue ] );
				}
			} )
			.on( toggleErrorEvent, function ( event, error ) {
				self._trigger( 'toggleerror', null, [ error ] );
			} );

			var $containerWrapper = this.element.children( '.wikibase-toolbar-wrapper' );
			if ( $containerWrapper.length === 0 ) {
				$containerWrapper = mw.wbTemplate( 'wikibase-toolbar-wrapper', '' ).appendTo( this.element );
			}

			this._statementAdder = this.options.getAdder(
				this.enterNewItem.bind( this ),
				$containerWrapper,
				mw.msg( 'wikibase-statementlistview-add' ),
				mw.msg( 'wikibase-statementlistview-add-tooltip' )
			);
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		destroy: function () {
			this._listview.destroy();
			if ( this._statementAdder ) {
				this._statementAdder.destroy();
				this._statementAdder = null;
			}
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Creates the `listview` widget managing the `statementview` widgets.
		 *
		 * @private
		 */
		_createListView: function () {
			this.$listview.listview( {
				listItemAdapter: this.options.getListItemAdapter( this._remove.bind( this ) ),
				value: this.options.value ? this.options.value.toArray() : null
			} );

			this._listview = this.$listview.data( 'listview' );
		},

		/**
		 * Sets the widget's value or gets the widget's current value (including pending items). (The
		 * value the widget was initialized with may be retrieved via `.option( 'value' )`.)
		 *
		 * @param {datamodel.StatementList} [statementList]
		 * @return {datamodel.StatementList|undefined}
		 */
		value: function ( statementList ) {
			if ( statementList !== undefined ) {
				return this.option( 'value', statementList );
			}

			var statements = [],
				lia = this._listview.listItemAdapter();

			this._listview.items().each( function () {
				var statementview = lia.liInstance( $( this ) ),
					statement = statementview.value();
				if ( statement ) {
					statements.push( statement );
				}
			} );

			return new datamodel.StatementList( statements );
		},

		/**
		 * Adds a new, pending `statementview` to the `statementlistview`.
		 *
		 * @see jQuery.wikibase.listview.enterNewItem
		 *
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {jQuery} return.done.$statementview
		 */
		enterNewItem: function () {
			return this._listview.enterNewItem();
		},

		/**
		 * Removes a `statementview` widget.
		 *
		 * @param {jQuery.wikibase.statementview} statementview
		 */
		_remove: function ( statementview ) {
			this._listview.removeItem( statementview.element );
			this._trigger( 'afterremove' );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value' && !!value ) {
				if ( !( value instanceof datamodel.StatementList ) ) {
					throw new Error( 'value needs to be a datamodel.StatementList instance' );
				}
				this._listview.value( value.toArray() );
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' ) {
				this._listview.option( key, value );
				if ( this._statementAdder ) {
					this._statementAdder[ value ? 'disable' : 'enable' ]();
				}
			}

			return response;
		},

		/**
		 * @inheritdoc
		 */
		focus: function () {
			var $items = this._listview.items();

			if ( $items.length ) {
				this._listview.listItemAdapter().liInstance( $items.first() ).focus();
			} else {
				this.element.trigger( 'focus' );
			}
		}

	} );

}() );
