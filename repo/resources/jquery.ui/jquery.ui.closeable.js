/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 *
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {jQuery} [$content]
 *         Default: null
 *
 * @option {string} [cssClass]
 *         Single css class name or space-separated list of multiple class names.
 *         Default: ''
 */
$.widget( 'ui.closeable', PARENT, {
	/**
	 * @see jQuery.ui.EditableTemplatedWidget.options
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
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		var self = this;

		PARENT.prototype._create.call( this );

		this.option( '$content', this.options.$content );
		this.option( 'cssClass', this.options.cssClass );

		this.$close.on( 'click.' + this.widgetName, function() {
			self.option( '$content', null );
		} );
	},

	/**
	 * Updates the widget's content.
	 *
	 * @param [$content]
	 * @param [cssClasses]
	 */
	setContent: function( $content, cssClasses ) {
		this.option( '$content', $content || null );
		this.option( 'cssClass', cssClasses || null );
		this._trigger( 'update' );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === '$content' ) {
			this.$content.empty();

			if( !value ) {
				this.element.hide();
			} else {
				this.$content.append( value );
				this.element.show();
			}
		} else if( key === 'cssClass' ) {
			value = value || '';
			this.element.removeClass( this.options.cssClass );
			this.element.addClass( value );
		}

		return PARENT.prototype._setOption.apply( this, arguments );
	}
} );


}( jQuery ) );
