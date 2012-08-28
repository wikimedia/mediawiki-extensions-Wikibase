/**
 * JavasScript for creating and managing a tooltip within the 'Wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
( function( mw, wb, $, undefined ) {
"use strict";
var $PARENT = wb.ui.Base;

/**
 * A generic tooltip, using jQuery.tipsy internally.
 * @constructor
 * @see wb.ui.Base
 * @since 0.1
 *
 * @event hide: Triggered after the tooltip was hidden from a previously visible state.
 *        (1) jQuery.Event
 *
 * @event clickOutside: Triggered when clicking outside of the tooltip's bubble.
 *        (1) jQuery.Event
 */
wb.ui.Tooltip = wb.utilities.inherit( $PARENT, {
	/**
	 * @const
	 * Class which marks the tooltip within the site html.
	 */
	UI_CLASS: 'wb-ui-tooltip',

	/**
	 * @var jQuery element the tooltip should be attached to
	 */
	_subject: null,

	/**
	 * @var Tipsy tipsy tooltip element
	 */
	_tipsy: null,

	/**
	 * @var Object tipsy tooltip configuration vars
	 */
	_tipsyConfig: null,

	/**
	 * @var bool used to determine if tooltip message is currently visible or not
	 */
	_isVisible: false,

	/**
	 * @var bool used to determine if tooltip should react on hovering or not
	 */
	_permanent: false,

	/**
	 * @var bool basically defines if the tooltip will appear in standard or error color schema
	 */
	_error: null,

	/**
	 * @var jQuery storing DOM content that should be displayed as tooltip bubble content
	 */
	_DomContent: null,

	/**
	 * initializes ui element, called by the constructor
	 *
	 * @param jQuery subject tooltip will be attached to this node
	 * @param string|object tooltipContent (may contain HTML markup), may also be an object describing an API error
	 * @param object tipsyConfig (optional) custom tipsy tooltip configuration
	 */
	init: function( subject, tooltipContent, tipsyConfig ) {
		$PARENT.prototype.init.apply( this, arguments );

		if ( typeof tooltipContent == 'string' ) {
			this._subject.attr( 'title', tooltipContent );
		} else {
			/* init tipsy with some placeholder since the tooltip message would not show without the title attribute
			being set; however, setting a complex HTML structure cannot be done via the title tag, so the content is
			stored in a custom variable that will be injected when the message is triggered to show */
			this._subject.attr( 'title', '.' );
			if ( typeof tooltipContent == 'object' && tooltipContent.code !== undefined ) {
				this._error = tooltipContent;
			} else {
				this._DomContent = tooltipContent;
			}
		}
		if (  tipsyConfig !== undefined ) {
			this._tipsyConfig = tipsyConfig;
		}
		if ( this._tipsyConfig == null || typeof this._tipsyConfig.gravity == undefined ) {
			this._tipsyConfig = {};
			this.setGravity( 'ne' );
		}
		this._initTooltip();

		jQuery.data( this._subject[0], 'wikibase.ui.tooltip', this );

		// reposition tooltip when resizing the browser window
		$( window ).off( '.wikibase.ui.tooltip' );
		$( window ).on( 'resize.wikibase.ui.tooltip', function( event ) {
			$( '[original-title]' ).each( function( i, node ) {
				if (
					$( node ).data( 'wikibase.ui.tooltip' ) !== undefined
					&& $( node ).data( 'wikibase.ui.tooltip' )._isVisible
				) {
					var tooltip = $( node ).data( 'wikibase.ui.tooltip' );
					if ( tooltip._permanent ) {
						tooltip._isVisible = false;
						tooltip.show( tooltip._permanent ); // trigger show() to reposition
					}
				}
			} );
		} );
	},

	/**
	 * Initializes the tooltip for the given element.
	 * This should normally be called directly by the constructor.
	 *
	 * @param jQuery parent element
	 */
	_initTooltip: function() {
		this._subject.tipsy( {
			'gravity': this._tipsyConfig.gravity,
			'trigger': 'manual',
			'html': true
		} );
		this._tipsy = this._subject.data( 'tipsy' );
		this._toggleEvents( true );
	},

	/**
	 * construct DOM structure for an error tooltip
	 *
	 * @param object error error code and messages
	 * @return jQuery
	 */
	_buildErrorTooltip: function() {
		var content = (
			$( '<div/>', {
				'class': 'wb-error wb-tooltip-error',
				text: this._error.shortMessage
			} )
		);
		if ( this._error.message != '' ) { // append detailed error message
			content.addClass( 'wb-tooltip-error-top-message' );
			content = content.after(
				$( '<a/>', {
					'class': 'wb-tooltip-error-details-link',
					href: 'javascript:void(0);'
				} )
				.on( 'click', function( event ) {
					$( this ).parent().find( '.wb-tooltip-error-details' ).slideToggle();
				} )
				.toggle(
					function() {
						$( $( this ).children()[0] ).removeClass( 'ui-icon-triangle-1-e' );
						$( $( this ).children()[0] ).addClass( 'ui-icon-triangle-1-s' );
					},
					function() {
						$( $( this ).children()[0] ).removeClass( 'ui-icon-triangle-1-s' );
						$( $( this ).children()[0] ).addClass( 'ui-icon-triangle-1-e' );
					}
				)
				.append( $( '<span/>', {
					'class': 'ui-icon ui-icon-triangle-1-e'
				} ) )
				.append( $( '<span/>', {
					text: mw.msg( 'wikibase-tooltip-error-details' )
				} ) )
			)
			.after( $( '<div/>', {
				'class': 'wb-tooltip-error-details',
				text: this._error.message
			} ) )
			.after( $( '<div/>', {
				'class': 'wb-clear'
			} ) );
		}

		return content;
	},

	/**
	 * toogle tooltip events to achive a permanent state or hover functionality
	 *
	 * @param bool activate
	 */
	_toggleEvents: function( activate ) {
		if ( activate ) {
			// only attach events when not yet attached to prevent memory leak
			if (
				this._subject.data( 'events' ) === undefined
				|| (
					this._subject.data( 'events' ).mouseover === undefined
					&& this._subject.data( 'events' ).mouseout === undefined
				)
			) {
				this._subject.on( 'mouseover', jQuery.proxy( function() { this.show(); }, this ) );
				this._subject.on( 'mouseout', jQuery.proxy( function() { this.hide(); }, this ) );
			}
		} else {
			this._subject.off( 'mouseover' );
			this._subject.off( 'mouseout' );
		}
	},

	/**
	 * query whether hover events are attached
	 */
	_hasEvents: function() {
		if( this._subject.data( 'events' ) === undefined ) {
			return false;
		} else {
			return (
				this._subject.data( 'events' ).mouseover !== undefined &&
				this._subject.data( 'events' ).mouseout !== undefined
			);
		}
	},

	/**
	 * Returns whether the tooltip is displayed currently.
	 *
	 * @return bool
	 */
	isVisible: function() {
		return this._isVisible;
	},

	/**
	 * show tooltip
	 *
	 * @param boolean permanent whether tooltip should be displayed permanently until hide() is being
	 *        called explicitly. false by default.
	 */
	show: function( permanent ) {
		if ( !this._isVisible ) {
			this._tipsy.show();
			if ( this._error != null ) {
				this._tipsy.$tip.addClass( 'wb-error' );

				// hide error tooltip when clicking outside of it
				this._tipsy.$tip.on( 'mousedown', function( event ) { // catching events of all mouse buttons
					event.stopPropagation();
				} );
				$( window ).one( 'mousedown', $.proxy( function( event ) {
					$( this ).triggerHandler( 'clickOutside' );
				}, this ) );

				// will lose inner click event on resizing (Details link) when not re-constructed on show
				this._tipsy.$tip.find( '.tipsy-inner' ).empty().append( this._buildErrorTooltip() );
			} else if ( this._DomContent != null ) {
				this._tipsy.$tip.find( '.tipsy-inner' ).empty().append( this._DomContent );
			}
			this._isVisible = true;
		}
		if( permanent === true ) {
			this._toggleEvents( false );
			this._permanent = true;
		}
	},

	/**
	 * hide tooltip
	 */
	hide: function() {
		this._permanent = false;
		if ( this._isVisible ) {
			this._tipsy.$tip.off( 'click' );
			this._tipsy.hide();
			this._isVisible = false;
			$( this ).triggerHandler( 'hide' ); // call event
		}
	},

	/**
	 * set where the tooltip message shall appear
	 *
	 * @param String gravity
	 */
	setGravity: function( gravity ) {
		// flip horizontal direction in rtl language
		if ( document.documentElement.dir == 'rtl' ) {
			if ( gravity.search( /e/ ) != -1) {
				gravity = gravity.replace( /e/g, 'w' );
			} else {
				gravity = gravity.replace( /w/g, 'e' );
			}
		}
		this._tipsyConfig.gravity = gravity;
		if ( this._tipsy != null ) {
			this._tipsy.options.gravity = gravity;
		}
	},

	/**
	 * set tooltip message / HTML content
	 *
	 * @param jQuery|string content
	 */
	setContent: function( content ) {
		this._DomContent = null;
		if ( typeof content == 'string' ) {
			this._tipsy.$element.attr( 'original-title', content );
		} else {
			this._DomContent = content;
		}
	},

	/**
	 * destroy object
	 */
	destroy: function() {
		if ( this._isVisible ) {
			this.hide();
		}
		this._toggleEvents( false );
		this._tipsyConfig = null;
		this._tipsy = null;

		$PARENT.prototype.destroy.apply( this, arguments );
	}
} );


/**
 * extends random element (like label or interface) with a tooltip
 * @var Object
 * @since 0.1
 */
wb.ui.Tooltip.ext = {
	/**
	 * @var wikibase.ui.Tooltip tooltip attached to this label
	 */
	_tooltip: null,

	/**
	 * Attaches a tooltip message to this element
	 *
	 * @param string|wb.ui.Tooltip tooltip message to be displayed as tooltip or already built tooltip
	 */
	setTooltip: function( tooltip ) {
		// if last tooltip was visible, we make the new one visible as well
		var wasVisible = false;

		if ( this._tooltip !== null ) {
			// remove existing tooltip first!
			wasVisible = this._tooltip.isVisible();
			this.removeTooltip();
		}
		if ( typeof tooltip == 'string' ) {
			// build new tooltip from string:
			this._elem.attr( 'title', tooltip );
			this._tooltip = new wb.ui.Tooltip( this._elem, tooltip );
		} else if ( tooltip instanceof wb.ui.Tooltip ) {
			this._tooltip = tooltip;
		}
		// restore previous tooltips visibility:
		if( this._tooltip !== null ) {
			if( wasVisible ) {
				this._tooltip.show();
			} else {
				this._tooltip.hide();
			}
		}

		if ( this._tooltip._error != null ) {
			$( this._tooltip ).one( 'clickOutside', $.proxy( function( event ) {
					this.removeTooltip();
			}, this ) );
		}

	},

	/**
	 * remove a tooltip message attached to this element
	 *
	 * @return bool whether a tooltip was set
	 */
	removeTooltip: function() {
		if ( this._tooltip !== null ) {
			this._tooltip.destroy();
			this._tooltip = null;
			return true;
		}
		return false;
	},

	/**
	 * Returns the element's tooltip or null in case none is set yet
	 *
	 * @return wb.ui.Tooltip|null
	 */
	getTooltip: function() {
		return this._tooltip;
	}
}

} )( mediaWiki, wikibase, jQuery );
