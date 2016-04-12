( function( $ ) {
	'use strict';

/**
 * Whether loaded in MediaWiki context.
 * @property {boolean}
 * @ignore
 */
var IS_MW_CONTEXT = ( typeof mediaWiki !== 'undefined' && mediaWiki.msg );

/**
 * Whether actual listrotator resource loader module is loaded.
 * @property {boolean}
 * @ignore
 */
var IS_MODULE_LOADED = (
	IS_MW_CONTEXT
		&& $.inArray( 'jquery.ui.listrotator', mediaWiki.loader.getModuleNames() ) !== -1
	);

/**
 * Returns a message from the MediaWiki context if the listrotator module has been loaded.
 * If it has not been loaded, the corresponding string defined in the options will be returned.
 * @ignore
 *
 * @param {string} msgKey
 * @param {string} string
 * @return {string}
 */
function mwMsgOrString( msgKey, string ) {
	return ( IS_MODULE_LOADED ) ? mediaWiki.msg( msgKey ) : string;
}

/**
 * Measures the maximum width of a container according to a list of strings. The width is
 * determined by the widest string.
 * @ignore
 *
 * @param {jQuery} $container
 * @param {string[]} strings
 * @return {number[]} The container's maximum width in pixel
 */
function measureMaximumStringWidths( $container, strings ) {
	var widths = [];
	$.each( strings, function( i, string ) {
		$container.empty().text( string );
		widths.push( $container.width() );
	} );
	$container.empty();
	return widths;
}

/**
 * List rotator widget
 *
 * The list rotator may be used to rotate through a list of values. The previous and next value
 * according to the currently selected value are displayed as links next to the current value. In
 * addition, clicking the current value reveals a drop-down list to directly select a value from the
 * list values.
 * (uses `jQuery.ui.menu`, `jQuery.ui.position`)
 * @class jQuery.ui.listrotator
 * @extends jQuery.Widget
 * @uses jQuery.ui
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {Object[]} options.values
 *        Array of objects containing the values to rotate.
 *        Single object structure:
 *        `{ value: actual value (being returned on value()), label: the value's label }`
 * @param {Object} [options.menu=Object]
 *        Options for the `jQuery.ui.menu` widget used as drop-down menu:
 * @param {Object} [options.menu.position=Object]
 *        Default object passed to `jQuery.ui.position` when positioning the menu. Positions will be
 *        flipped if isRtl option returns `true`.
 * @param {boolean} [options.deferInit=false]
 *        Whether to defer initializing the section widths until `initWidths()` is called
 *        "manually".
 */
/**
 * @event selected
 * Triggered when a specific value is selected.
 * @param {jQuery.Event} event
 * @param {*} value Value as specified in the `values` option.
 */
/**
 * @event auto
 * Triggered when "auto" option is selected.
 * @param {jQuery.Event} event
 */
$.widget( 'ui.listrotator', {
	/**
	 * @see jQuery.Widget.options
	 * @protected
	 * @readonly
	 */
	options: {
		values: [],
		menu: {
			position: {
				my: 'left top',
				at: 'left bottom',
				collision: 'none'
			}
		},
		deferInit: false,
		messages: {
			auto: mwMsgOrString( 'valueview-listrotator-auto', 'auto' )
		}
	},

	/**
	 * Node of the selectable "auto" option.
	 * @property {jQuery}
	 * @protected
	 * @readonly
	 */
	$auto: null,

	/**
	 * Node of the current list item section.
	 * @property {jQuery}
	 * @protected
	 * @readonly
	 */
	$curr: null,

	/**
	 * Node of the menu opening when clicking on the "current" section.
	 * @property {jQuery}
	 * @protected
	 * @readonly
	 */
	$menu: null,

	/**
	 * @see jQuery.Widget._create
	 * @protected
	 *
	 * @throws {Error} if no values are supplied.
	 */
	_create: function() {
		var self = this;

		if ( this.options.values.length === 0 ) {
			throw new Error( 'List of values required to initialize list rotator.' );
		}

		this.element.addClass( this.widgetBaseClass + ' ui-widget-content' );

		// Construct "auto" link:
		this.$auto = this._createSection( 'auto', function( event ) {
			if ( self.autoActive() ) {
				return;
			}
			self.activate( self.$auto );
			self._trigger( 'auto' );
		} )
		.addClass( 'ui-state-active' );
		this.$auto.children( 'span' ).text( this.options.messages.auto );

		// Construct the basic sections:
		this.$curr = this._createSection( 'curr', function( event ) {
			if ( !self.$menu.is( ':visible' ) ) {
				self._showMenu();
			} else {
				self._hideMenu();
			}
		} )
		.append( $( '<span/>' ).addClass( 'ui-icon ui-icon-triangle-1-s' ) );

		if ( this.$auto ) {
			this.element.append( this.$auto );
		}
		this.element.append( this.$curr );

		// Construct and initialize menu widget:
		this._createMenu();

		// Attach event to html node to detect click outside of the menu closing the menu:
		$( 'html' )
		.off( '.' + this.widgetName )
		.on( 'click.' + this.widgetName, function( event ) {
			$( ':' + self.widgetBaseClass ).each( function( i, node ) {
				var $target = $( event.target ),
					listrotator = $( node ).data( 'listrotator' );

				// Hide the menu if it is neither the "current" node nor the menu's node that
				// has been clicked.
				if ( !$target.closest( listrotator.$curr.add( listrotator.$menu ) ).length ) {
					listrotator.$menu.hide();
				}

			} );
		} );

		// Focus on first element:
		this.value( this.options.values[0].value );

		if ( !this.options.deferInit ) {
			this.initWidths();
		}

	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		var menu = this.$menu.data( 'menu' );
		if ( menu ) {
			menu.destroy();
		}

		this.$menu.remove();
		this.$auto.remove();
		this.$curr.remove();

		this.element.removeClass( this.widgetBaseClass + ' ui-widget-content' );

		$.Widget.prototype.destroy.call( this );

		// Remove event attached to the html node if no instances of the widget exist anymore:
		if ( $( ':' + this.widgetBaseClass ).length === 0 ) {
			$( 'html' ).off( '.' + this.widgetBaseClass );
		}
	},

	/**
	 * Init the section widths.
	 */
	initWidths: function() {
		// Determine the maximum width a label may have and apply that width to each section:
		var currentLabel = this.$curr.children( '.' + this.widgetBaseClass + '-label' ).text(),
			labels = [],
			currMaxWidth = 0;

		$.each( this.options.values, function( i, v ) {
			labels.push( v.label );
		} );

		var stringWidths = measureMaximumStringWidths(
			this.$curr.children( '.' + this.widgetBaseClass + '-label' ),
			labels
		);
		$.each( stringWidths, function( i, width ) {
			if ( width > currMaxWidth ) {
				currMaxWidth = width;
			}
		} );

		this.$curr.children( '.' + this.widgetBaseClass + '-label' ).width( currMaxWidth );

		// Make menu width comply to the "current" section:
		var menuSpacing = this.$menu.outerWidth() - this.$menu.width();
		this.$menu.width( this.$curr.outerWidth() - menuSpacing );

		// Reset "current" section's label:
		this.$curr.children( '.' + this.widgetBaseClass + '-label' ).text( currentLabel );
	},

	/**
	 * Creates a widget section.
	 * @protected
	 *
	 * @param {string} classSuffix
	 * @param {Function} clickCallback
	 * @return {jQuery}
	 */
	_createSection: function( classSuffix, clickCallback ) {
		return $( '<a/>' )
		.addClass( this.widgetBaseClass + '-' + classSuffix )
		.on( 'click.' + this.widgetBaseClass, function( event ) {
			event.preventDefault();
			if ( !$( this ).hasClass( 'ui-state-disabled' ) ) {
				clickCallback( event );
			}
		} )
		.append( $( '<span/>' ).addClass( this.widgetBaseClass + '-label ui-state-default' ) );
	},

	/**
	 * Create the drop-down menu assigned to the "current" section.
	 * @protected
	 */
	_createMenu: function() {
		var self = this;

		this.$menu = $( '<ul/>' )
		.addClass( this.widgetBaseClass + '-menu' )
		.appendTo( $( 'body' ) ).hide();

		$.each( this.options.values, function( i, v ) {
			self._addMenuItem( v );
		} );

		this.$menu.menu();
	},

	/**
	 * @protected
	 *
	 * @param {Object} item
	 * @return {jQuery}
	 */
	_addMenuItem: function( item ) {
		var self = this;
		return $( '<li/>' )
			.append(
				$( '<a/>' )
				.text( item.label )
				.on( 'click', function( event ) {
					event.preventDefault();
					event.stopPropagation();
					self._trigger( 'selected', null, [ self.value( item.value ) ] );
					self.$menu.hide();
				} )
			)
			.data( 'value', item.value )
			.appendTo( this.$menu );
	},

	// TODO: Change behavior: value as setter should return "this" for allowing chaining calls
	//  to the widget.
	/**
	 * Sets/Gets the widget's value. Setting the value involves setting the rotator to the
	 * specified value without any animation.
	 *
	 * @param [value] The value to assign. (Has to match a value actually existing in the widget's
	 *        options.)
	 * @return {string} Current value.
	 */
	value: function( value ) {
		// Get the current value:
		if ( value === undefined || value === this.$curr.data( 'value' ) ) {
			return this.$curr.data( 'value' );
		}

		var values = this.options.values,
			index = 0;

		this.$curr.children( '.' + this.widgetBaseClass + '-label' ).empty();

		// Retrieve the index of the new value within the list of predefined values:
		$.each( values, function( i, v ) {
			if ( value === v.value ) {
				index = i;
				return false;
			}
		} );

		// Re-construct each section:
		this.$curr
		.data( 'value', values[index].value )
		.children( '.' + this.widgetBaseClass + '-label' )
		.text( values[index].label );

		// Alter menu item states:
		this.$menu.children( 'li' ).each( function( i, li ) {
			var $li = $( li );
			$li.removeClass( 'ui-state-active' );
			if ( $li.data( 'value' ) === value ) {
				$li.addClass( 'ui-state-active' );
			}
		} );

		return value;
	},

	/**
	 * Sets a new value rotating to the new value.
	 * @protected
	 *
	 * @param {*} newValue
	 */
	_setValue: function( newValue ) {
		var self = this;

		if ( this.$curr.data( 'value' ) === newValue ) {
			// Value is set already.
			return;
		}

		this.element.one( this.widgetEventPrefix + 'selected', function( event, newValue ) {
			self.activate();
		} );

		this._trigger( 'selected', null, [ this.value( newValue ) ] );
	},

	/**
	 * Activates the widget.
	 *
	 * @param {jQuery} [$section] Section to activate. "Current" section by default.
	 */
	activate: function( $section ) {
		this.$curr.add( this.$auto ).removeClass( 'ui-state-hover ui-state-active' );

		if ( $section === undefined ) {
			$section = this.$curr;
		}

		$section.addClass( 'ui-state-active' );
	},

	/**
	 * De-activates the widget.
	 */
	deactivate: function() {
		this.$curr.add( this.$auto ).removeClass( 'ui-state-active' );
	},

	/**
	 * Returns whether the listrotator is currently set to "auto", meaning that the value
	 * returned by value() has not been chosen by the user explicitly.
	 *
	 * @return {boolean}
	 */
	autoActive: function() {
		return this.$auto.hasClass( 'ui-state-active' );
	},

	/**
	 * Shows the drop-down menu.
	 * @protected
	 */
	_showMenu: function() {
		this.$menu.show();

		function flip( string ) {
			var segments = $.map( string.split( ' ' ), function( segment ) {
				return ( segment.indexOf( 'left' ) !== -1 )
					? segment.replace( 'left', 'right' )
					: segment.replace( 'right', 'left' );
			} );
			return segments.join( ' ' );
		}

		this.$menu.position( $.extend( {
			of: this.$curr
		}, {
			my: flip( this.options.menu.position.my ),
			at: flip( this.options.menu.position.at )
		} ) );

		this.activate();
	},

	/**
	 * Hides the drop-down menu.
	 * @protected
	 */
	_hideMenu: function() {
		this.$menu.hide();
		this.activate();
	},

	/**
	 * Disables the widget.
	 */
	disable: function() {
		this.$curr.addClass( 'ui-state-disabled' );
	},

	/**
	 * Enables the widget.
	 */
	enable: function() {
		this.$curr.removeClass( 'ui-state-disabled' );
	}

} );

} )( jQuery );
