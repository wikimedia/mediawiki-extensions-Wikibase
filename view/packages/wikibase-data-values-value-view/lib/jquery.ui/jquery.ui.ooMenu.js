( function () {
'use strict';

/**
 * jQuery.ui.ooMenu provides an object-oriented menu structure. Menu items are managed using
 * specific objects instead of DOM elements.
 * (uses `jQuery.util.getscrollbarwidth`, `util.inherit`)
 *
 * @class jQuery.ui.ooMenu
 * @extends jQuery.Widget
 * @uses jQuery.util
 * @uses util
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} [options]
 * @param {jQuery.ui.ooMenu.Item[]} [options.items=[]]
 *        List of items to display.
 * @param {jQuery.ui.ooMenu.CustomItem[]} [options.customItems=[]]
 *        List of custom items.
 * @param {Function|null} [options.manipulateLabel=null]
 *        Function applied to each label before rendering, expects {string} to be returned.
 *        Function parameter:
 *
 * - {string} options.manipulateLabel.label
 *
 * @param {number|null} [options.maxItems=10]
 *        Maximum number of visible items. If there are more items, scrollbars will be shown. Set
 *        to "null" to never have scrollbars on the menu.
 */
/**
 * @event focus
 * Triggered when focusing/activating an item.
 * @param {jQuery.Event} event
 * @param {jQuery.ui.ooMenu.Item} item
 */
/**
 * @event blur
 * Triggered when blurring/deactivating an item.
 * @param {jQuery.Event} event
 */
/**
 * @event selected
 * Triggered when selecting an item.
 * @param {jQuery.Event} event
 * @param {jQuery.ui.ooMenu.Item|null} item
 */
$.widget( 'ui.ooMenu', {

	/**
	 * @see jQuery.Widget.options
	 * @protected
	 * @readonly
	 */
	options: {
		items: [],
		customItems: null,
		manipulateLabel: null,
		maxItems: 10
	},

	/**
	 * @see jQuery.Widget._create
	 * @protected
	 */
	_create: function() {
		this.options.customItems = this.options.customItems || [];

		this.element.addClass( 'ui-ooMenu ui-widget ui-widget-content' );

		this._refresh();
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		this.element
		.removeClass( 'ui-ooMenu ui-widget ui-widget-content' )
		.empty();

		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * @param key
	 * @param value
	 * @see jQuery.Widget._setOption
	 * @protected
	 * @throws {Error} when trying to set `items` or `customItems` option with improper values.
	 */
	_setOption: function( key, value ) {
		if ( key === 'items' || key === 'customItems' ) {
			if ( !Array.isArray( value ) ) {
				throw new Error( key + ' needs to be an array' );
			}

			for ( var i = 0; i < value.length; i++ ) {
				if ( key === 'items' && !( value[i] instanceof $.ui.ooMenu.Item )
					|| key === 'customItems' && !( value[i] instanceof $.ui.ooMenu.CustomItem )
				) {
					throw new Error( key + ' may only feature specific instances' );
				}
			}
		}

		$.Widget.prototype._setOption.apply( this, arguments );

		if ( key === 'items' || key === 'customItems' ) {
			this._refresh();
		} else if ( key === 'maxItems' ) {
			this.scale();
		}
	},

	/**
	 * Updates the menu content.
	 *
	 * @protected
	 */
	_refresh: function() {
		this.element.empty();
		this.element.scrollTop( 0 );
		for ( var i = 0; i < this.options.items.length; i++ ) {
			this._appendItem( this.options.items[i] );
		}

		for ( var j = 0; j < this.options.customItems.length; j++ ) {
			if ( this._evaluateVisibility( this.options.customItems[j] ) ) {
				this._appendItem( this.options.customItems[j] );
			}
		}

		this.scale();
	},

	/**
	 * Evaluates whether a custom item is supposed to be visible or not.
	 *
	 * @protected
	 *
	 * @param {jQuery.ui.ooMenu.CustomItem} customItem
	 * @return {boolean}
	 */
	_evaluateVisibility: function( customItem ) {
		return customItem.getVisibility( this );
	},

	/**
	 * Appends an item to the menu.
	 *
	 * @protected
	 *
	 * @param {jQuery.ui.ooMenu.Item} item
	 */
	_appendItem: function( item ) {
		var self = this;

		var label = this.options.manipulateLabel
			? this.options.manipulateLabel( item.getLabel() )
			: item.getLabel();

		var $a = $( '<a/>' )
			.attr( 'tabindex', -1 )
			.html( label );

		if ( item.getLink() ) {
			$a.attr( 'href', item.getLink() );
		}

		var $item = $( '<li/>' )
			.addClass( 'ui-ooMenu-item' )
			.attr( 'dir', 'auto' )
			.data( 'ui-ooMenu-item', item )
			.append( $a );

		if ( item instanceof $.ui.ooMenu.CustomItem ) {
			$item.addClass( 'ui-ooMenu-customItem' );

			if ( typeof item.getAction() === 'function' ) {
				$item.addClass( 'ui-ooMenu-customItem-action' );
			}

			if ( item.getCssClass() ) {
				$item.addClass( item.getCssClass() );
			}
		}

		$item
		.on( 'mouseenter.ooMenu', function() {
			self.activate( item );
		} )
		.on( 'mouseleave.ooMenu', function() {
			self.deactivate();
		} )
		.on( 'mousedown.ooMenu', function( e ) {
			if ( !( e.which !== 1 || e.altKey || e.ctrlKey || e.shiftKey || e.metaKey ) ) {
				self.select( e );
			}
		} );

		$item.appendTo( this.element );
	},

	/**
	 * Returns whether the menu currently features visible items.
	 *
	 * @param {boolean} [includeCustomItems]
	 * @return {boolean}
	 */
	hasVisibleItems: function( includeCustomItems ) {
		if ( this.options.items.length ) {
			return true;
		}

		if ( !includeCustomItems ) {
			return false;
		}

		for ( var i = 0; i < this.options.customItems.length; i++ ) {
			if ( this._evaluateVisibility( this.options.customItems[i] ) ) {
				return true;
			}
		}

		return false;
	},

	/**
	 * Scales the menu's height to the height of maximum list items and takes care of the menu width
	 * not reaching out of the browser viewport.
	 */
	scale: function() {
		this.element
		.width( 'auto' )
		.height( 'auto' )
		.css( 'overflowY', 'visible' );

		// Constrain height:
		if ( this.options.maxItems ) {
			var $children = this.element.children();

			if ( $children.length > this.options.maxItems ) {
				var fixedHeight = 0;

				for ( var i = 0; i < this.options.maxItems; i++ ) {
					fixedHeight += $children.eq( i ).outerHeight();
				}

				this.element.width( this.element.outerWidth() + $.util.getscrollbarwidth() );
				this.element.height( fixedHeight );
				this.element.css( 'overflowY', 'scroll' );
			}
		}

		// Constrain width if menu reaches out of the browser viewport:
		if ( this.element.offset().left + this.element.outerWidth( true ) > $( window ).width() ) {
			this.element.width(
				$( window ).width()
					- this.element.offset().left
					- ( this.element.outerWidth( true ) - this.element.width() )
					- 20 // safe space
			);
		}
	},

	/**
	 * Returns the currently active item.
	 *
	 * @return {jQuery.ui.ooMenu.Item|null}
	 */
	getActiveItem: function() {
		var $item = this.element.children( '.ui-state-hover' );
		return !$item.length ? null : $item.data( 'ui-ooMenu-item' );
	},

	/**
	 * Activates/focuses a specific item.
	 *
	 * @param {jQuery.ui.ooMenu.Item|jQuery} item
	 *
	 * @throws {Error} if the item is not specified correctly.
	 */
	activate: function( item ) {
		var $item;

		if ( item instanceof $.ui.ooMenu.Item ) {
			$item = this.element.children( '.ui-ooMenu-item' ).filter( function() {
				return $( this ).data( 'ui-ooMenu-item' ) === item;
			} );
		} else if ( item instanceof $ && item.data( 'ui-ooMenu-item' ) ) {
			$item = item;
		} else {
			throw new Error( 'Need $.ui.ooMenu.Item instance or menu item jQuery object to '
				+ 'activate' );
		}

		this.element.children( '.ui-state-hover' ).removeClass( 'ui-state-hover' );

		var offset = $item.offset().top - this.element.offset().top,
			scroll = this.element.scrollTop(),
			elementHeight = this.element.height();

		if ( offset < 0 ) {
			this.element.scrollTop( scroll + offset );
		} else if ( offset >= elementHeight ) {
			this.element.scrollTop( scroll + offset - elementHeight + $item.height() );
		}

		$item.addClass( 'ui-state-hover' );

		$( this ).trigger( 'focus', [$item.data( 'ui-ooMenu-item' )] );
	},

	/**
	 * Deactivates the menu (resets activated item).
	 */
	deactivate: function() {
		if ( this._isActive() ) {
			this.element.children( '.ui-state-hover' ).removeClass( 'ui-state-hover' );
			$( this ).trigger( 'blur' );
		}
	},

	/**
	 * Returns whether there is an active menu item.
	 *
	 * @protected
	 *
	 * @return {boolean}
	 */
	_isActive: function() {
		return !!this.element.children( '.ui-state-hover' ).length;
	},

	/**
	 * Moves focus to the next item.
	 */
	next: function() {
		this._move( 'next', this.element.children( '.ui-ooMenu-item:first' ) );
	},

	/**
	 * Moves focus to the previous item.
	 */
	previous: function() {
		this._move( 'prev', this.element.children( '.ui-ooMenu-item:last' ) );
	},

	/**
	 * Moves focus in a specific direction.
	 *
	 * @protected
	 *
	 * @param {string} direction Either "next" or "prev".
	 * @param {jQuery} $edge
	 */
	_move: function( direction, $edge ) {
		if ( !this.element.children().length ) {
			return;
		}

		var $active = this.element.children( '.ui-state-hover' );

		if ( !$active.length ) {
			this.activate( $edge );
			return;
		}

		var $nextItem = $active[direction + 'All']( '.ui-ooMenu-item' ).eq( 0 );

		if ( $nextItem.length ) {
			this.activate( $nextItem );
		} else {
			this.activate( $edge );
		}
	},

	/**
	 * Selects an item.
	 *
	 * @param event
	 */
	select: function( event ) {
		var $item = this.element.children( '.ui-state-hover' );

		var item = !$item.length ? null : $item.data( 'ui-ooMenu-item' );

		if ( item instanceof $.ui.ooMenu.CustomItem ) {
			var action = item.getAction();
			if ( typeof action === 'function' ) {
				action();
			}
		}

		var selectedEvent = $.Event( 'selected', {
			originalEvent: event || null
		} );

		$( this ).trigger( selectedEvent, [item] );
	}
} );

/**
 * Default menu item.
 *
 * @class jQuery.ui.ooMenu.Item
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string|jQuery} label The label to display in the menu.
 * @param {string|null} [value] The value to display in the input element if the item is selected.
 *        If no value is specified, the label text will be used.
 * @param {string|null} [link=null] Optional URL the item shall link to.
 *
 * @throws {Error} if any required parameter is not specified properly.
 */
var Item = function( label, value, link ) {
	if ( !label ) {
		throw new Error( 'Label needs to be specified' );
	}

	this._label = label;
	this._value = value || ( label instanceof $ ? label.text() : label );
	this._link = link || null;
};

$.extend( Item.prototype, {
	/**
	 * @property {jQuery|string}
	 * @protected
	 */
	_label: null,

	/**
	 * @property {string}
	 * @protected
	 */
	_value: null,

	/**
	 * @property {string|null}
	 * @protected
	 */
	_link: null,

	/**
	 * @return {jQuery}
	 */
	getLabel: function() {
		return this._label instanceof String
			? $( document.createTextNode( this._label ) )
			: this._label;
	},

	/**
	 * @return {string}
	 */
	getValue: function() {
		return this._value;
	},

	/**
	 * @return {string|null}
	 */
	getLink: function() {
		return this._link;
	}
} );

/**
 * Customizable menu item.
 *
 * @class jQuery.ui.ooMenu.CustomItem
 * @extends jQuery.ui.ooMenu.Item
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string|jQuery} label
 * @param {Function|boolean|null} [visibility=null]
 *        Function to determine the item's visibility or boolean defining static visibility. If
 *        "null" or omitted, the item will always be visible. Function expects {boolean} to be
 *        returned. Function parameter:
 *
 * - {jQuery.ui.ooMenu} [visibility.menu]
 *
 * @param {Function|null} [action=null]
 * @param {string|null} [cssClass=null]
 * @param {string|null} [link=null]
 *
 * @throws {Error} if any required parameter is not specified properly.
 */
var CustomItem = function( label, visibility, action, cssClass, link ) {
	if ( !label ) {
		throw new Error( 'Label needs to be specified' );
	}

	this._label = label;
	this.setVisibility( visibility );
	this.setAction( action );
	this.setCssClass( cssClass );
	this._link = link || null;
};

CustomItem = util.inherit(
	Item,
	CustomItem,
	{
		/**
		 * @property {Function|boolean|null}
		 * @protected
		 */
		_visibility: null,

		/**
		 * @property {Function|null}
		 * @protected
		 */
		_action: null,

		/**
		 * @property {string}
		 * @protected
		 */
		_cssClass: null,

		/**
		 * @inheritdoc
		 */
		getValue: function() {
			return '';
		},

		/**
		 * @param menu
		 * @return {Function|boolean}
		 */
		getVisibility: function( menu ) {
			if ( typeof this._visibility === 'function' ) {
				return this._visibility( menu );
			}
			return this._visibility !== false;
		},

		/**
		 * @param {Function|boolean|null} [visibility]
		 */
		setVisibility: function( visibility ) {
			this._visibility = typeof visibility === 'function' || typeof visibility === 'boolean'
				? visibility
				: null;
		},

		/**
		 * @return {Function|null}
		 */
		getAction: function() {
			return this._action;
		},

		/**
		 * @param {Function|null} [action]
		 */
		setAction: function( action ) {
			this._action = typeof action === 'function' ? action : null;
		},

		/**
		 * @return {string}
		 */
		getCssClass: function() {
			return this._cssClass;
		},

		/**
		 * @param {string|null} [cssClass]
		 */
		setCssClass: function( cssClass ) {
			this._cssClass = typeof cssClass === 'string' ? cssClass : '';
		},

		/**
		 * @param {string} [link]
		 */
		setLink: function( link ) {
			this._link = link || null;
		}
	}
);

$.extend( $.ui.ooMenu, {
	Item: Item,
	CustomItem: CustomItem
} );

} )( jQuery, util );
