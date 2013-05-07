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
 * @option {Object} [messages] Strings used within the widget.
 *         Messages should be specified using mwMsgOrString(<resource loader module message key>,
 *         <fallback message>) in order to use the messages specified in the resource loader module
 *         (if loaded).
 *         messages['show options'] {String} (optional) Label of the link showing any additional
 *         contents.
 *         Default value: 'show options'
 *         messages['hide options'] {String} (optional) Label of the link hiding any additional
 *         contents.
 *         Default value: 'hide options'
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
	 * Whether loaded in MediaWiki context.
	 * @type {boolean}
	 */
	var IS_MW_CONTEXT = ( typeof mw !== 'undefined' && mw.msg );

	/**
	 * Whether actual inputextender resource loader module is loaded.
	 * @type {boolean}
	 */
	var IS_MODULE_LOADED = (
		IS_MW_CONTEXT
		&& $.inArray( 'jquery.ui.inputextender', mw.loader.getModuleNames() ) !== -1
	);

	/**
	 * Returns a message from the MediaWiki context if the input extender module has been loaded.
	 * If it has not been loaded, the corresponding string defined in the options will be returned.
	 *
	 * @param {String} msgKey
	 * @param {String} string
	 * @return {String}
	 */
	function mwMsgOrString( msgKey, string ) {
		return ( IS_MODULE_LOADED ) ? mw.msg( msgKey ) : string;
	}

	$.widget( 'ui.inputextender', {
		/**
		 * Additional options
		 * @type {Object}
		 */
		options: {
			content: [],
			extendedContent: [],
			initCallback: null,
			hideWhenInputEmpty: true,
			messages: {
				'show options': mwMsgOrString( 'valueview-inputextender-showoptions', 'show options' ),
				'hide options': mwMsgOrString( 'valueview-inputextender-hideoptions', 'hide options' )
			}
		},

		/**
		 * The widget parent's node.
		 * @type {jQuery}
		 */
		$parent: null,

		/**
		 * Container node wrapping the widget's whole DOM structure.
		 * @type {jQuery}
		 */
		$container: null,

		/**
		 * Container node containing the input element and the extender.
		 * @type {jQuery}
		 */
		$inputContainer: null,

		/**
		 * Node of the link to extended the extenders additional content.
		 * @type {jQuery}
		 */
		$extender: null,

		/**
		 * Node containing all the extension content.
		 * @type {jQuery}
		 */
		$contentContainer: null,

		/**
		 * Node of the default/"fixed" extension content.
		 * @type {jQuery}
		 */
		$content: null,

		/**
		 * Node of the additional extension content shown/hidden by the extender link.
		 * @type {jQuery}
		 */
		$extendedContent: null,

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

			this.$parent = this.element.parent();

			if( !this.$parent.length ) {
				throw new Error( 'Input extender widget needs to be in the DOM when initializing.' );
			}

			this.$container = $( '<div/>' )
			.addClass( this.widgetBaseClass )
			.appendTo( this.$parent );

			this.$inputContainer = $( '<div />' )
			.addClass( this.widgetBaseClass + '-inputcontainer' )
			.append( this.element.addClass( this.widgetBaseClass + '-input' ).detach() )
			.appendTo( this.$container );

			this.$extender = $( '<a/>' )
			.addClass( this.widgetBaseClass + '-extender' )
			.attr( 'href', 'javascript:void(0);' )
			.text( this.options.messages['show options'] )
			.appendTo( this.$inputContainer )
			.on( 'click', function( event ) {
				clearTimeout( self._blurTimeout );
				self._toggleExtension();
			} )
			.on( 'keydown', function( event ) {
				if( event.keyCode === $.ui.keyCode.ENTER ) {
					clearTimeout( self._blurTimeout );
					self._toggleExtension();
				}
			} )
			.on( 'focus', function( event ) {
				clearTimeout( self._blurTimeout );
			} )
			.hide();

			this.$contentContainer = $( '<div/>' )
			.addClass( this.widgetBaseClass + '-contentcontainer ui-widget-content' )
			.appendTo( this.$container )
			.on( 'click.' + this.widgetName, function( event ) {
				clearTimeout( self._blurTimeout );
			} );

			this.$content = $( '<div/>' )
			.addClass( this.widgetBaseClass + '-content' )
			.appendTo( this.$contentContainer );

			this.$extendedContent = $( '<div/>' )
			.addClass( this.widgetBaseClass + '-extendedcontent' )
			.appendTo( this.$contentContainer );

			this.element.add( this.$extender )
			.on( 'focus.' + this.widgetName, function( event ) {
				if( !self.options.hideWhenInputEmpty || self.element.val() !== '' ) {
					clearTimeout( self._blurTimeout );
					self.showContent();
				}
			} )
			// TODO: Do not hide when tabbing into the inputextender's contents
			.on( 'blur.' + this.widgetName, function( event ) {
				self._blurTimeout = setTimeout( function() {
					self.hideContent( function() {
						self._toggleExtension( { forceHide: true } );
					} );
				}, 150 );
			} );

			if( this.options.hideWhenInputEmpty ) {
				this.element.eachchange( function( event, oldValue ) {
					if( self.element.val() === '' && !self.$extendedContent.is( ':visible' ) ) {
						self.hideContent();
					} else if ( oldValue === '' ) {
						self.showContent();
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
							widget.hideContent( function() {
								widget._toggleExtension( { forceHide: true } );
							} );
						}
					} );
				} );
			}

			this._draw();

			if( $.isFunction( this.options.initCallback ) ) {
				this.options.initCallback();
			}

			this.$contentContainer.hide();
			this.$extendedContent.hide();
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			var $input = this.element.detach();
			this.$container.remove();
			this.$parent.append( $input );
			if( $( ':' + this.widgetBaseClass ).length === 0 ) {
				$( 'html' ).off( '.' + this.widgetName );
			}
			$.Widget.prototype.destroy.call( this );
		},

		/**
		 * Draws the widget according to its current state.
		 */
		_draw: function() {
			var self = this;

			this.$content.empty();

			// Only show the extender when there are any additional options to extend:
			this.$extender[ ( this.options.extendedContent.length ) ? 'show' : 'hide' ]();

			$.each( this.options.content, function( i, $node ) {
				self.$content.append( $node );
			} );

			$.each( this.options.extendedContent, function( i, $node ) {
				self.$extendedContent.append( $node );
			} );
		},

		/**
		 * Toggles the visibility of the additional options.
		 *
		 * @param {Object|undefined} customOptions
		 */
		_toggleExtension: function( customOptions ) {
			var self = this,
				options = {
					moveFocus: true,
					forceHide: false
				};

			$.extend( options, customOptions );

			function hideExtendedContent() {
				self.$extendedContent.slideUp( 150, function() {
					self.$extender.text( self.options.messages['show options'] );
					self._trigger( 'toggle' );
				} );
			}

			if( options.forceHide ) {
				hideExtendedContent();
				return;
			}

			if( this.$extendedContent.is( ':visible' ) ) {
				this.showContent( hideExtendedContent );
			} else {
				this.showContent( function() {
					self.$extendedContent.slideDown( 150, function() {
						self.$extender.text( self.options.messages['hide options'] );
						self._trigger( 'toggle' );
					} );
				} );
			}

		},

		/**
		 * Shows all the extension contents.
		 *
		 * @param {Function} [callback] Invoked as soon as the contents are visible.
		 */
		showContent: function( callback ) {
			this.$contentContainer.stop( true, true ).fadeIn( 150, function() {
				if( $.isFunction( callback ) ) {
					callback();
				}
			} );
		},

		/**
		 * Hides all the extension contents.
		 *
		 * @param {Function} [callback] Invoked as soon as the contents are hidden.
		 */
		hideContent: function( callback ) {
			this.$contentContainer.stop( true, true ).fadeOut( 150, function() {
				if( $.isFunction( callback ) ) {
					callback();
				}
			} );
		}

	} );

} )( jQuery );
