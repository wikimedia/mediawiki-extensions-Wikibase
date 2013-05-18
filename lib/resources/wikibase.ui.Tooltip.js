/**
 * JavasScript for creating and managing a tooltip within the 'Wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */
( function( mw, wb, $, undefined ) {
'use strict';
var PARENT = wb.ui.Base;

/**
 * A generic tooltip, using jQuery.tipsy internally.
 * @constructor
 * @see wb.ui.Base
 * @since 0.1
 *
 * @event hide: Triggered before the tooltip will be hidden out of a visible state.
 *        (1) jQuery.Event
 *
 * @event afterhide: Triggered after the tooltip has been hidden.
 *        (1) jQuery.Event
 *
 * @event clickOutside: Triggered when clicking outside of the tooltip's bubble.
 *        (1) jQuery.Event
 *
 * @todo add function for identifying whether tooltip represents an error. Error content and normal content handling
 *       could be handled together and more generic.
 */
wb.ui.Tooltip = wb.utilities.inherit( PARENT, {
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
	 * Initializes the ui element.
	 *
	 * @param {jQuery} subject tooltip will be attached to this node
	 * @param {Object} options
	 * @param {string|wb.RepoApiError} tooltipContent (may contain HTML markup)
	 * @param {Object} tipsyConfig (optional) custom tipsy tooltip configuration
	 */
	init: function( subject, options, tooltipContent, tipsyConfig ) {
		PARENT.prototype.init.apply( this, arguments );

		if ( typeof tooltipContent === 'string' ) {
			this._subject.attr( 'title', tooltipContent );
		} else {
			/* init tipsy with some placeholder since the tooltip message would not show without the title attribute
			being set; however, setting a complex HTML structure cannot be done via the title tag, so the content is
			stored in a custom variable that will be injected when the message is triggered to show */
			this._subject.attr( 'title', '.' );
			// TODO: Make use of a more generic Error object
			if ( tooltipContent instanceof wb.RepoApiError || tooltipContent.code ) {
				this._error = tooltipContent;
			} else {
				this._DomContent = tooltipContent;
			}
		}

		this._tipsyConfig = tipsyConfig || {};
		this.setGravity( this._tipsyConfig.gravity || 'ne' );

		this._initTooltip();

		jQuery.data( this._subject[0], 'wikibase.ui.tooltip', this );

		// reposition tooltip when resizing the browser window
		$( window ).off( '.wikibase.ui.tooltip' );
		$( window ).on( 'resize.wikibase.ui.tooltip', function() {
			$( '[original-title]' ).each( function( i, node ) {
				if (
					$( node ).data( 'wikibase.ui.tooltip' ) !== undefined &&
					$( node ).data( 'wikibase.ui.tooltip' )._isVisible
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
	 * Constructs the DOM structure displayed within an error tooltip
	 *
	 * @param object error error code and messages
	 * @return jQuery
	 */
	_buildErrorTooltip: function() {
		var $mainMessage = $( '<div>' ),
			$detailedMessage, $toggler;

		$mainMessage
			.attr( 'class', 'wb-error wb-tooltip-error' )
			.text( this._error.message );

		// append detailed error message if given, hide it behind toggle:
		if( this._error.detailedMessage ) {
			$mainMessage.addClass( 'wb-tooltip-error-top-message' );

			$detailedMessage = $( '<div>', {
				'class': 'wb-tooltip-error-details',
				html: this._error.detailedMessage
			} ).hide(); // hide detail message initially!

			$toggler = $( '<a>' )
			.addClass( 'wb-tooltip-error-details-link' )
			.text( mw.msg( 'wikibase-tooltip-error-details' ) )
			.toggler( { $subject: $detailedMessage } );

			$mainMessage = $mainMessage.after( $toggler ).after( $detailedMessage );
		}

		return $mainMessage;
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
				this._subject.data( 'events' ) === undefined ||
				(
					this._subject.data( 'events' ).mouseover === undefined &&
					this._subject.data( 'events' ).mouseout === undefined
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
			// The native Tipsy tooltip does not allow jQuery nodes to be set as content and when
			// triggering Tipsy's show() method, the $tip is removed from the DOM while the $tips
			// position is also set within the show() method. To work around that, we trigger
			// showing the tooltip before filling it with content and cache the initial position.
			// TODO: This is not the most elegant solution since the $tip might reach out of the
			// viewport.
			// The DOM content needs to be cloned since IE8 will lose the reference to the DOM
			// content when the inner HTML is removed within tipsy's native show() method.
			if ( this._DomContent ) {
				this._DomContent = this._DomContent.clone( true );
			}
			this._tipsy.show();

			var offset = this._tipsy.$tip.offset(),
				height = this._tipsy.$tip.height();

			if ( this._error !== null ) {
				this._tipsy.tip().addClass( 'wb-error' );

				// hide error tooltip when clicking outside of it
				this._tipsy.tip().on( 'mousedown', function( event ) { // catching events of all mouse buttons
					event.stopPropagation();
				} );
				$( window ).one( 'mousedown', $.proxy( function() {
					this.triggerHandler( 'clickOutside' );
				}, this ) );

				// will lose inner click event on resizing (Details link) when not re-constructed on show
				this._tipsy.tip().find( '.tipsy-inner' ).empty().append( this._buildErrorTooltip() );
			} else if ( this._DomContent !== null ) {
				this._tipsy.tip().find( '.tipsy-inner' ).empty().append( this._DomContent );
			}

			if ( this._tipsy.options.gravity.charAt( 0 ) === 's' ) {
				this._tipsy.$tip.offset(
					{ top: offset.top - this._tipsy.$tip.height() + height, left: offset.left }
				);
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
	 * @see jQuery.NativeEventHandler
	 * @triggers hide
	 */
	hide: $.NativeEventHandler( 'hide', {
		initially: function( event ) {
			this._permanent = false;
			if( !this.isVisible() ) {
				event.cancel(); // no point in doing any hiding if hidden already
			}
		},
		natively: function() {
			this._tipsy.tip().off( 'click' );
			this._tipsy.hide();
			this._isVisible = false;
			// TODO: Implement afterHide properly to be called within some callback of tipsy.hide()
			// or (probably) overwrite tipsy's hide().
			this.trigger( 'afterhide' );
		}
	} ),

	/**
	 * set where the tooltip message shall appear
	 *
	 * @param String gravity
	 */
	setGravity: function( gravity ) {
		// flip horizontal direction in rtl language
		if ( document.documentElement.dir === 'rtl' ) {
			if ( gravity.search( /e/ ) !== -1) {
				gravity = gravity.replace( /e/g, 'w' );
			} else {
				gravity = gravity.replace( /w/g, 'e' );
			}
		}
		this._tipsyConfig.gravity = gravity;
		if ( this._tipsy !== null ) {
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
		if ( typeof content === 'string' ) {
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

		PARENT.prototype.destroy.apply( this, arguments );
	}
} );

} )( mediaWiki, wikibase, jQuery );
