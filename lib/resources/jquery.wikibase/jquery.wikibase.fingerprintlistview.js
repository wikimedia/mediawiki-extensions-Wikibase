/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Displays multiple fingerprints (see jQuery.wikibase.fingerprintview).
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {Object[]} value
 *         Object representing the widget's value.
 *         Structure: [
 *           { language: <{string]>, label: <{string|null}>, description: <{string|null}> } [, ...]
 *         ]
 *
 * @option {string} entityId
 *
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @event change
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *       - {jQuery.Event}
 *
 * @event stopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *        - {Function} Callback function.
 *
 * @event afterstopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *
 * @event toggleerror
 *        - {jQuery.Event}
 *        - {Error|null}
 */
$.widget( 'wikibase.fingerprintlistview', PARENT, {
	options: {
		template: 'wikibase-fingerprintlistview',
		templateParams: [
			'' // tbodys
		],
		templateShortCuts: {},
		value: [],
		entityId: null,
		entityChangersFactory: null
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if( !$.isArray( this.options.value ) || !this.options.entityId || !this.options.entityChangersFactory ) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this._createListView();

		this.element.addClass( 'wikibase-fingerprintlistview' );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		// When destroying a widget not initialized properly, listview will not have been created.
		var listview = this.element.data( 'listview' );

		if( listview ) {
			listview.destroy();
		}

		this.element.removeClass( 'wikibase-fingerprintlistview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Creates the listview widget managing the fingerprintview widgets
	 */
	_createListView: function() {
		var self = this,
			listItemWidget = $.wikibase.fingerprintview,
			prefix = listItemWidget.prototype.widgetEventPrefix;

		// Fully encapsulate child widgets by suppressing their events:
		this.element
		.on( prefix + 'change.' + this.widgetName, function( event ) {
			event.stopPropagation();
			self._trigger( 'change' );
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			event.stopPropagation();
			self.setError( error );
		} )
		.on(
			[
				prefix + 'create.' + this.widgetName,
				prefix + 'afterstartediting.' + this.widgetName,
				prefix + 'stopediting.' + this.widgetName,
				prefix + 'afterstopediting.' + this.widgetName,
				prefix + 'disable.' + this.widgetName
			].join( ' ' ),
			function( event ) {
				event.stopPropagation();
			}
		);

		this.element
		.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: listItemWidget,
				newItemOptionsFn: function( value ) {
					return {
						value: value,
						entityId: self.options.entityId,
						entityChangersFactory: self.options.entityChangersFactory,
						helpMessage: mw.msg(
							'wikibase-fingerprintview-input-help-message',
							wb.getLanguageNameByCode( value.language )
						)
					};
				}
			} ),
			value: self.options.value || null,
			listItemNodeName: 'TBODY'
		} );
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		var listview = this.element.data( 'listview' ),
			lia = listview.listItemAdapter(),
			isValid = true;

		listview.items().each( function() {
			var fingerprintview = lia.liInstance( $( this ) );
			if( !fingerprintview.isValid() ) {
				isValid = false;
				return false;
			}
		} );

		return isValid;
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		var listview = this.element.data( 'listview' ),
			lia = listview.listItemAdapter(),
			currentValue = [];

		listview.items().each( function() {
			var fingerprintview = lia.liInstance( $( this ) );
			currentValue.push( fingerprintview.value() );
		} );

		if( currentValue.length !== this.options.value.length ) {
			return false;
		}

		// TODO: Implement and use Fingerprint in DataModelJavaScript component
		for( var i = 0; i < currentValue.length; i++ ) {
			if(
				currentValue[i].language !== this.options.value[i].language
				|| currentValue[i].label !== this.options.value[i].label
				|| currentValue[i].description !== this.options.value[i].description
				|| currentValue[i].aliases.length !== this.options.value[i].aliases.length
			) {
				return false;
			}

			var currentAliases = currentValue[i].aliases;

			for( var j = 0; j < currentAliases.length; j++ ) {
				if( $.inArray( currentAliases[j], this.options.value[i].aliases ) === -1 ) {
					return false;
				}
			}
		}

		return true;
	},

	startEditing: function() {
		if( this._isInEditMode ) {
			return;
		}

		this._isInEditMode = true;
		this.element.addClass( 'wb-edit' );

		var listview = this.element.data( 'listview' ),
			lia = listview.listItemAdapter();

		listview.items().each( function() {
			var fingerprintview = lia.liInstance( $( this ) );
			fingerprintview.startEditing();
		} );

		this._trigger( 'afterstartediting' );
	},

	/**
	 * @param {boolean} [dropValue]
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		if( !this._isInEditMode || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		}

		dropValue = !!dropValue;

		this._trigger( 'stopediting', null, [dropValue] );

		this.disable();

		var listview = this.element.data( 'listview' ),
			lia = listview.listItemAdapter(),
			expectedEvents = listview.items().length;

		this.element
		.on( 'fingerprintviewafterstopediting.fingerprintlistview', function() {
			if( --expectedEvents === 0 ) {
				self.element.off( 'fingerprintviewafterstopediting.fingerprintlistview' );
				self._afterStopEditing( dropValue );
			}
		} );

		listview.items().each( function() {
			var fingerprintview = lia.liInstance( $( this ) );
			fingerprintview.stopEditing( dropValue || fingerprintview.isInitialValue() );
		} );
	},

	/**
	 * @param {boolean} dropValue
	 */
	_afterStopEditing: function( dropValue ) {
		if( !dropValue ) {
			this.options.value = this.value();
		}
		this._isInEditMode = false;
		this.enable();
		this.element.removeClass( 'wb-edit' );
		this._trigger( 'afterstopediting', null, [dropValue] );
	},

	cancelEditing: function() {
		this.stopEditing( true );
	},

	focus: function() {
		var listview = this.element.data( 'listview' ),
			lia = listview.listItemAdapter(),
			$items = listview.items();

		if( $items.length ) {
			lia.liInstance( $items.first() ).focus();
		}
	},

	/**
	 * Applies/Removes error state.
	 *
	 * @param {Error} [error]
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	},

	/**
	 * @param {Object[]} [value]
	 * @return {Object[]|*}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			return this.option( 'value', value );
		}

		var listview = this.element.data( 'listview' ),
			lia = listview.listItemAdapter();

		value = [];

		listview.items().each( function() {
			var fingerprintview = lia.liInstance( $( this ) );
			value.push( fingerprintview.value() );
		} );

		return value;
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Impossible to set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.element.data( 'listview' ).option( key, value );
		}

		return response;
	}
} );

}( mediaWiki, wikibase, jQuery ) );
