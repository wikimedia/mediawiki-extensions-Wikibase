/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {

'use strict';

var PARENT = $.Widget;

/**
 * Tooltip widget enhancing jQuery.tipsy.
 * @since 0.4
 *
 * @option content {string|jQuery|wb.Error} The tooltip balloon's content.
 *         (REQUIRED)
 *
 * @option permanent {boolean} Whether the tooltip shall be visible permanently (only closing with a
 *         click outside of it) or when hovering.
 *         Default: false
 *
 * @option gravity {string} Two-letter string consisting out of a combination of the letter 'n', 's'
 *         and 'e', 'w'. This specifies the direction the tooltip balloon will be anchored to. The
 *         direction is to be specified assuming an left-to-right context and is mirrored when
 *         detecting an RTL context.
 *         Default: 'nw'
 *
 * @option $anchor {jQuery} An anchor where the tooltip's tip shall point on. By default (null), it
 *         is the node the tooltip widget is initialized on.
 *         Default: null
 *
 * @event afterhide: Triggered after the tooltip has been hidden.
 *        (1) jQuery.Event
 */
$.widget( 'wikibase.wbtooltip', PARENT, {
	/**
	 * Options.
	 * @type {Object}
	 */
	options: {
		content: null,
		permanent: false,
		gravity: 'nw',
		$anchor: null
	},

	/**
	 * Tipsy tooltip plugin object.
	 * @type {Object}
	 */
	_tipsy: null,

	/**
	 * @see jQuery.Widget._create
	 * @throws {Error} when no content has been specified in the options.
	 */
	_create: function() {
		var self = this;

		PARENT.prototype._create.call( this );

		if( typeof this.options.content === 'string' ) {
			// Tipsy, in general, makes use of the "title" attribute. Therefore, when setting a
			// plain string as content, just assign it to the "title" attribute:
			this.element.attr( 'title', this.options.content );
		} else {
			// Init Tipsy with some placeholder since the tooltip message would not show without the
			// title attribute being set. However, setting a complex HTML structure cannot be done
			// via the title tag, so, the content is stored in a custom variable that will be
			// injected when the message is about to get displayed.
			this.element.attr( 'title', '.' );
		}

		// Flip horizontal gravity when in RTL context:
		var gravity = this._evaluateGravity( this.options.gravity );

		// Init tipsy:
		if( !this.element.data( 'tipsy' ) ) {
			this.element.tipsy( {
				gravity: gravity,
				trigger: 'manual', // Prevent Tipsy's native hover handling.
				html: true
			} );
		} else {
			// If Tipsy is initialised already, just overwrite the gravity:
			this.element.data( 'tipsy' ).options.gravity = gravity;
		}

		this._tipsy = this.element.data( 'tipsy' );

		this.element.addClass( this.widgetFullName );

		if( !this.options.permanent ) {
			this.element
			.off( '.' + this.widgetName )
			.on( 'mouseenter.' + this.widgetName, function( event ) {
				self.show();
			} )
			.on( 'mouseleave.' + this.widgetName, function( event ) {
				self.hide();
			} );
		}

		// Reposition tooltip when resizing the browser window:
		$( window )
		.off( '.' + this.widgetName ) // Never need that event more than once.
		.on( 'resize.' + this.widgetName, function( event ) {
			$( ':' + self.widgetFullName ).each( function( i, node ) {
				var tooltip = $( node ).data( self.widgetName );

				if(
					tooltip && tooltip._tipsy.$tip && tooltip._tipsy.$tip.is( ':visible' )
					&& tooltip.option( 'permanent' )
				) {
					tooltip._tipsy.$tip.hide();
					// Trigger show() to reposition:
					// TODO: Implement option to show tooltip without a fade animation to prevent
					//  flickering.
					tooltip.show( tooltip._permanent );
				}
			} );
		} );

	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		this._tipsy.tip().remove();
		this.element.off( 'mouseenter.' + this.widgetName + ' mouseleave.' + this.widgetName );
		this.element.removeData( 'tipsy' );
		this._tipsy = null;

		// Detach window event handler if no widget instances are left:
		if( $( ':' + this.widgetFullName ).length === 0 ) {
			$( window ).off( '.' + this.widgetName );
		}

		PARENT.prototype.destroy.apply( this, arguments );
	},

	/**
	 * Hides the tooltip balloon and destroys the tooltip object afterwards.
	 * @since 0.4
	 *
	 * @param {boolean} [remove] Whether to remove the tooltip's node from the DOM.
	 */
	degrade: function( remove ) {
		var self = this;

		this.element.one( 'wbtooltipafterhide', function( event ) {
			self.destroy();
			if( remove ) {
				self.element.remove();
			}
		} );

		this.hide();
	},

	/**
	 * Evaluates a given gravity string according to the language direction flipping the horizontal
	 * gravity in RTL context.
	 * @since 0.4
	 *
	 * @param {string} gravity
	 */
	_evaluateGravity: function( gravity ) {
		if ( document.documentElement.dir === 'rtl' ) {
			if ( gravity.search( /e/ ) !== -1) {
				gravity = gravity.replace( /e/g, 'w' );
			} else {
				gravity = gravity.replace( /w/g, 'e' );
			}
		}
		return gravity;
	},

	/**
	 * Shows the tooltip balloon.
	 * @since 0.4
	 */
	show: function() {
		var self = this;

		if( this._tipsy.$tip && this._tipsy.$tip.is( ':visible' ) ) {
			return;
		}

		// The native Tipsy tooltip does not allow jQuery nodes to be set as content and when
		// triggering Tipsy's show() method, the $tip is removed from the DOM while the $tips
		// position is also set within the show() method. To work around that, we trigger showing
		// the tooltip before filling it with content and cache the initial position.
		// TODO: This is not the most elegant solution since the $tip might reach out of the
		// viewport.
		// The DOM content needs to be cloned since IE8 will lose the reference to the DOM content
		// when the inner HTML is removed within tipsy's native show() method.
		var content = null;

		if ( this.options.content instanceof jQuery ) {
			content = this.options.content.clone( true, true );
		}

		// If a tooltip anchor is specified, use that for positioning the tip by overwriting the
		// element referenced by Tipsy. In order for Tipsy's show() method to not abort, the anchor
		// node needs to have the "title" attribute set.
		if( this.options.$anchor ) {
			this._tipsy.$element = this.options.$anchor;
			if( !this._tipsy.$element.attr( 'title' ) ) {
				this._tipsy.$element.attr( 'title', '.' );
			}
		}

		this._tipsy.show();

		this._tipsy.$tip.addClass( this.widgetFullName + '-tip' );

		var offset = this._tipsy.$tip.offset(),
			height = this._tipsy.$tip.height();

		if ( this.options.content.code ) {
			// Content is an error object.
			this._tipsy.tip().addClass( 'wb-error' );

			// If not re-constructed on showing, click event on inner element (e.g. Details link)
			// will be lost.
			content = this._buildErrorTooltip();
		}

		if( this.options.permanent ) {
			// Hide error tooltip when clicking outside of it by suppressing clicks on the $tip from
			// bubbling:
			this._tipsy.tip().on( 'mousedown.' + this.widgetName, function( event ) {
				event.stopPropagation();
			} );

			$( window ).one( 'mousedown.' + this.widgetName, function( event ) {
				// Tipsy might be destroyed already.
				if( self._tipsy ) {
					self.hide();
				}
			} );
		}

		if ( typeof this.options.content !== 'string' ) {
			this._tipsy.tip().find( '.tipsy-inner' ).empty().append( content );
		}

		// Reposition $tip since Tipsy evaluated the position before we filled it with DOM content:
		if ( this._tipsy.options.gravity.charAt( 0 ) === 's' ) {
			this._tipsy.$tip.offset(
				{ top: offset.top - this._tipsy.$tip.height() + height, left: offset.left }
			);
		}
	},

	/**
	 * Hides the tooltip balloon.
	 * @since 0.4
	 *
	 * @triggers afterhide
	 */
	hide: function() {
		if( !this._tipsy || !this._tipsy.$tip || !this._tipsy.$tip.is( ':visible' ) ) {
			return;
		}

		this._tipsy.tip().off( '.' + this.widgetName );
		this._tipsy.hide();

		// TODO: Implement afterHide properly to be called within some callback of tipsy.hide() or
		// (probably) overwrite tipsy's hide().
		this._trigger( 'afterhide' );
	},

	/**
	 * Constructs the DOM structure displayed within an error tooltip.
	 * @since 0.4
	 *
	 * @return {jQuery}
	 *
	 * @TODO: Error tooltip should be a separate tooltip derivative.
	 */
	_buildErrorTooltip: function() {
		var $message = $( '<div>' ).addClass( 'wb-error ' + this.widgetFullName + '-error' );

		var $mainMessage = $( '<div>' ).text( this.options.content.message ).appendTo( $message );

		// Append detailed error message if given; hide it behind toggle:
		if( this.options.content.detailedMessage ) {
			$mainMessage.addClass( this.widgetFullName + '-error-top-message' );

			var $detailedMessage = $( '<div>', {
				'class': this.widgetFullName + '-error-details',
				html: this.options.content.detailedMessage
			} )
			.hide();

			var $toggler = $( '<a>' )
				.addClass( this.widgetFullName + '-error-details-link' )
				.text( mw.msg( 'wikibase-tooltip-error-details' ) )
				.toggler( { $subject: $detailedMessage, duration: 'fast' } );

			$toggler.appendTo( $message );
			$detailedMessage.appendTo( $message );
		}

		return $message;
	},

	/**
	 * @see jQuery.Widget.option
	 */
	option: function( key, value ) {
		if( key === 'gravity' ) {
			// Consider language direction:
			value = this._evaluateGravity( value );
		}
		return PARENT.prototype.option.call( this, key, value );
	}

} );

} )( mediaWiki, jQuery );
