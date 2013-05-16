/**
 * Input extender widget
 *
 * The input extender extends an input element with additional contents displayed underneath the.
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @option {jQuery[]} [content] Default/"fixed" extender contents that always should be visible as
 *         long as the extension itself is visible.
 *         Default value: []
 *
 * @option {jQuery[]} [extendedContent] Additional content that should only be displayed after
 *         clicking on the extender link.
 *         Default value: []
 *
 * @option {Function} [initCallback] Function triggered after the widget has been initialized but
 *         before the widget contents get hidden initially. This may be used to init some widgets
 *         that need to be visible on initialization for measuring dimension according to their
 *         container's styles.
 *
 * @option {boolean} [hideWhenInputEmpty] Whether all of the input extender's contents shall be
 *         hidden when the associated input element is empty.
 *         Default value: true
 *
 * @event toggle: Triggered when the visibility of the extended content is toggled.
 *        (1) {jQuery.Event}
 *
 * @event animationstep: While the input extender's extension is being animated, this event is
 *        triggered on each animation step. The event forwards the parameters received from the
 *        animation's "step" callback. However, when the animation is finished, the event is
 *        triggered without the second and third parameter.
 *        (1) {jQuery.Event}
 *        (2) {number} [now]
 *        (3) {jQuery.Tween} [tween]
 *
 * @dependency jQuery.Widget
 * @dependency jQuery.eachchange
 */
( function( $ ) {
	'use strict';

	/**
	 * Caches whether the widget is used in a rtl context. This, however, depends on using an "rtl"
	 * class on the document body like it is done in MediaWiki.
	 * @type {boolean}
	 */
	var isRtl = $( 'body' ).hasClass( 'rtl' );

	$.widget( 'ui.inputextender', {
		/**
		 * Additional options
		 * @type {Object}
		 */
		options: {
			content: [],
			initCallback: null,
			hideWhenInputEmpty: true,
			position: {
				my: ( isRtl ) ? 'right top' : 'left top',
				at: ( isRtl ) ? 'right bottom' : 'left bottom',
				collision: 'none',
				offset: '-4 2'
			}
		},

		/**
		 * The input extension's node.
		 * @type {jQuery}
		 */
		$extension: null,

		/**
		 * Caches the timeout when the actual input extender animation should kick in.
		 * @type {Object}
		 */
		_animationTimeout: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this;

			this.element
			.addClass( this.widgetBaseClass + '-input' );

			this.$extension = $( '<div/>' )
			.addClass( this.widgetBaseClass + '-extension ui-widget-content' )
			.on( 'click.' + this.widgetName, function( event ) {
				clearTimeout( self._animationTimeout );
				self.showExtension();
			} )
			.on( 'toggleranimationstep.' + this.widgetName, function( event, now, tween ) {
				self._trigger( 'animationstep', null, [ now, tween ] );
			} )
			.appendTo( $( 'body' ) );

			this.element
			.on( 'focus.' + this.widgetName, function( event ) {
				if( !self.options.hideWhenInputEmpty || self.element.val() !== '' ) {
					clearTimeout( self._animationTimeout );
					self._animationTimeout = setTimeout( function() {
						self.showExtension();
					}, 150 );
				}
			} )
			// TODO: Allow direct tabbing into the extension
			.on( 'blur.' + this.widgetName, function( event ) {
				self._animationTimeout = setTimeout( function() {
					self.hideExtension();
				}, 150 );
			} )
			.on( 'keydown.' + this.widgetName, function( event ) {
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					self.hideExtension();
				}
			} );

			if( this.options.hideWhenInputEmpty ) {
				this.element.eachchange( function( event, oldValue ) {
					if( self.element.val() === '' ) {
						self.hideExtension();
					} else if ( oldValue === '' ) {
						self.showExtension();
					}
				} );
			}

			// Blurring by clicking away from the widget (one handler is sufficient):
			$( 'html' )
			.off( '.' + this.widgetName )
			.on( 'click.' + this.widgetName, function( event ) {
				// Loop through all widgets and hide content when having clicked out of it:
				var $widgetNodes = $( ':' + self.widgetBaseClass );
				$widgetNodes.each( function( i, widgetNode ) {
					var widget = $( widgetNode ).data( self.widgetName ),
						$target = $( event.target );

					// Hide the extension neither it nor the corresponding input element is
					// clicked:
					if( !$target.closest( widget.element.add( widget.$extension ) ).length ) {
						widget.hideExtension();
					}

				} );
			} );

			this._draw();

			if( $.isFunction( this.options.initCallback ) ) {
				this.options.initCallback();
			}

			$.each( this.options.content, function( i, node ) {
				$( node ).addClass( self.widgetBaseClass + '-contentnode' );
			} );

			this.$extension.hide();
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			this.$extension.remove();

			$.Widget.prototype.destroy.call( this );

			if( $( ':' + this.widgetBaseClass ).length === 0 ) {
				$( 'html' ).off( '.' + this.widgetName );
			}
		},

		/**
		 * Draws the widget.
		 */
		_draw: function() {
			var self = this;

			this.$extension.empty();

			$.each( this.options.content, function( i, $node ) {
				self.$extension.append( $node );
			} );
		},

		/**
		 * Shows the extension.
		 *
		 * @param {Function} [callback] Invoked as soon as the contents are visible.
		 */
		showExtension: function( callback ) {
			var self = this;

			// When blurring the browser viewport and an re-focusing, Chrome is firing the "focus"
			// event twice. jQuery fadeIn sets the opacity to 0 for the first fadeIn but does not
			// pick up the value when triggering fadeIn the second time.
			if( this.$extension.css( 'opacity' ) === '0' ) {
				this.$extension.css( 'opacity', '1' );
			}

			// Element needs to be visible to use jquery.ui.position.
			if( !this.$extension.is( ':visible' ) ) {
				this.$extension.show();
				this.$extension.position( $.extend( {
					of: this.element
				}, this.options.position ) );
				this.$extension.hide();
			}

			this.$extension.stop( true ).fadeIn( {
				duration: 150,
				step: function( now, tween ) {
					self._trigger( 'animationstep', null, [ now, tween ] );
				},
				complete: function() {
					if( $.isFunction( callback ) ) {
						callback();
					}
					self._trigger( 'animationstep' );
				}
			} );
		},

		/**
		 * Hides the extension.
		 *
		 * @param {Function} [callback] Invoked as soon as the contents are hidden.
		 */
		hideExtension: function( callback ) {
			var self = this;

			this.$extension.stop( true ).fadeOut( {
				duration: 150,
				step: function( now, tween ) {
					self._trigger( 'animationstep', null, [ now, tween ] );
				},
				complete: function() {
					if( $.isFunction( callback ) ) {
						callback();
					}
					self._trigger( 'animationstep' );
				}
			} );
		}

	} );

} )( jQuery );
