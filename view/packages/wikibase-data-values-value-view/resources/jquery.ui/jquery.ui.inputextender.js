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
				collision: 'none'
			}
		},

		/**
		 * The input extension's node.
		 * @type {jQuery}
		 */
		$extension: null,

		/**
		 * Caches the timeout when the actual "blur" action should kick in.
		 * @type {Object}
		 */
		_blurTimeout: null,

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
				clearTimeout( self._blurTimeout );
				event.stopPropagation();
				self.showExtension();
			} )
			.appendTo( $( 'body' ) );

			this.element
			.on( 'focus.' + this.widgetName, function( event ) {
				if( !self.options.hideWhenInputEmpty || self.element.val() !== '' ) {
					clearTimeout( self._blurTimeout );
					self.showExtension();
				}
			} )
			// TODO: Allow direct tabbing into the extension
			.on( 'blur.' + this.widgetName, function( event ) {
				self._blurTimeout = setTimeout( function() {
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
			if( $( ':' + this.widgetBaseClass ).length === 1 ) {
				$( 'html' ).on( 'click.' + this.widgetName, function( event ) {
					// Loop through all widgets and hide content when having clicked out of it:
					var $widgetNodes = $( ':' + self.widgetBaseClass );
					$widgetNodes.each( function( i, widgetNode ) {
						var widget = $( widgetNode ).data( self.widgetName );
						if(
							$( event.target ).closest( widget.$container ).length === 0
							&& !widget.element.is( ':focus' )
						) {
							widget.hideExtension();
						}
					} );
				} );
			}

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
			if( $( ':' + this.widgetBaseClass ).length === 0 ) {
				$( 'html' ).off( '.' + this.widgetName );
			}
			$.Widget.prototype.destroy.call( this );
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
			// Element needs to be visible to use jquery.ui.position.
			if( !this.$extension.is( ':visible' ) ) {
				this.$extension.show();
				this.$extension.position( $.extend( {
					of: this.element
				}, this.options.position ) );
				this.$extension.hide();
			}

			this.$extension.stop( true, true ).fadeIn( 150, function() {
				if( $.isFunction( callback ) ) {
					callback();
				}
			} );
		},

		/**
		 * Hides the extension.
		 *
		 * @param {Function} [callback] Invoked as soon as the contents are hidden.
		 */
		hideExtension: function( callback ) {
			this.$extension.stop( true, true ).fadeOut( 150, function() {
				if( $.isFunction( callback ) ) {
					callback();
				}
			} );
		}

	} );

} )( jQuery );
