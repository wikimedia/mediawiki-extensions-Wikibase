/**
 * Input extender widget
 *
 * The input extender extends an input element with additional contents displayed underneath the.
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @option {jQuery[]} [content] Default/"fixed" extender contents that always should be visible as
 *         long as the extension itself is visible.
 *
 * @option {jQuery[]} [extendedContent] Additional content that should only be displayed after
 *         clicking on the extender link.
 *
 * @option [messages] {Object} Strings used within the widget.
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
 * @dependency jQuery.Widget
 */
( function( $ ) {
	'use strict';

	/**
	 * Whether loaded in MediaWiki context.
	 * @type {boolean}
	 */
	var IS_MW_CONTEXT = ( typeof mw !== 'undefined' && mw.msg );

	/**
	 * Whether actual entity selector resource loader module is loaded.
	 * @type {boolean}
	 */
	var IS_MODULE_LOADED = (
		IS_MW_CONTEXT
		&& $.inArray( 'jquery.wikibase.entityselector', mw.loader.getModuleNames() ) !== -1
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
			.data( this.widgetName, this )
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
				self._toggleExtension();
			} )
			.hide();

			this.$contentContainer = $( '<div/>' )
			.addClass( this.widgetBaseClass + '-contentcontainer ui-widget-content' )
			.appendTo( this.$container )
			.hide();

			this.$content = $( '<div/>' )
			.addClass( this.widgetBaseClass + '-content' )
			.appendTo( this.$contentContainer );

			this.$extendedContent = $( '<div/>' )
			.addClass( this.widgetBaseClass + '-extendedcontent' )
			.appendTo( this.$contentContainer )
			.hide();

			this.element.add( this.$extender )
			.on( 'focus.' + this.widgetName, function( event ) {
				self.showContent();
			} )
			.on( 'blur.' + this.widgetName, function( event ) {
				self.hideContent();
			} );

			this._draw();
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			var $input = this.element.detach();
			this.$container.remove();
			this.$parent.append( $input );
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
		 */
		_toggleExtension: function() {
			var self = this;

			if( this.$extendedContent.is( ':visible' ) ) {
				this.$extendedContent.slideUp( 150, function() {
					self.$extender.text( self.options.messages['show options'] );
					self._trigger( 'toggle' );
				} );
			} else {
				this.element.focus();
				this.$extendedContent.slideDown( 150, function() {
					self.$extender.text( self.options.messages['hide options'] );
					self._trigger( 'toggle' );
				} );
			}

		},

		/**
		 * Shows all the extension contents.
		 *
		 * @param {Function} [callback] Invoked as soon as the contents are visible.
		 */
		showContent: function( callback ) {
			this.$contentContainer.fadeIn( 150, function() {
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
			this.$contentContainer.fadeOut( 150, function() {
				if( $.isFunction( callback ) ) {
					callback();
				}
			} );
		}

	} );

} )( jQuery );
