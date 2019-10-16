( function () {
	'use strict';

	require( './jquery.ui.TemplatedWidget.js' );
	var PARENT = $.ui.TemplatedWidget;

	/**
	 * Content container that can be closed/hidden.
	 * @class jQuery.ui.closeable
	 * @extends jQuery.ui.TemplatedWidget
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} [options]
	 * @param {jQuery|null} [options.$content=null]
	 * @param {string} [options.cssClass='']
	 *        Single css class name or space-separated list of multiple class names.
	 */
	/**
	 * @event update
	 * Triggered whenever the widget's content is updated using the setContent() function.
	 * @param {jQuery.Event} event
	 */
	$.widget( 'ui.closeable', PARENT, {
		/**
		 * @inheritdoc
		 */
		options: {
			template: 'ui-closeable',
			templateParams: [
				'' // content
			],
			templateShortCuts: {
				$content: '.ui-closeable-content',
				$close: '.ui-closeable-close'
			},
			$content: null,
			cssClass: ''
		},

		/**
		 * @inheritdoc
		 */
		_create: function () {
			var self = this;

			PARENT.prototype._create.call( this );

			this.option( '$content', this.options.$content );
			this.option( 'cssClass', this.options.cssClass );

			this.$close.on( 'click.' + this.widgetName, function () {
				self.option( '$content', null );
			} );
		},

		/**
		 * Updates the widget's content.
		 *
		 * @param {jQuery|null} [$content=null]
		 * @param {string|null} [cssClasses=null]
		 */
		setContent: function ( $content, cssClasses ) {
			this.option( '$content', $content || null );
			this.option( 'cssClass', cssClasses || '' );
			this._trigger( 'update' );
		},

		/**
		 * @inheritdoc
		 */
		_setOption: function ( key, value ) {
			if ( key === '$content' ) {
				this.$content.empty();

				if ( !value ) {
					this.element.hide();
				} else {
					this.$content.append( value );
					this.element.show();
				}
			} else if ( key === 'cssClass' ) {
				value = value || '';
				this.element.removeClass( this.options.cssClass );
				this.element.addClass( value );
			}

			return PARENT.prototype._setOption.apply( this, arguments );
		}
	} );

}() );
