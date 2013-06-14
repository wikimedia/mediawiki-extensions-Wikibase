/**
 * Valueview preview widget
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @option {jQuery} [$input] Input element node. If specified, the preview will not be updated when
 *         the input element is cleared, e.g. if the input will be hidden, it is not necessary to
 *         update the value.
 *
 * @dependency jQuery.Widget
 * @dependency mediaWiki
 */
( function( $, mw ) {
	'use strict';

	$.widget( 'valueview.preview', {

		/**
		 * Additional options.
		 * @type {Object}
		 */
		options: {
			$input: null
		},

		/**
		 * The node of the previewed value.
		 * @type {jQuery}
		 */
		$value: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			this.element
			.addClass( this.widgetBaseClass )
			.append(
				$( '<div/>' )
				.addClass( this.widgetBaseClass + '-label' )
				.text( mw.msg( 'valueview-preview-label' ) )
			);

			this.$value = $( '<div/>' )
			.addClass( this.widgetBaseClass + '-value' )
			.appendTo( this.element );

			this.update( null );
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			this.$value.remove();

			this.element
			.children( this.widgetBaseClass + '-label' )
			.removeClass( this.widgetBaseClass );

			$.Widget.prototype.destroy.call( this );
		},

		/**
		 * Updates the previewed value.
		 *
		 * @param {string|null} value
		 */
		update: function( value ) {
			// No need to update the preview when the input value is clear(ed) since the preview
			// will be hidden anyway.
			if( this.options.$input && this.options.$input.val() === '' ) {
				return;
			}

			if( value === null ) {
				this.$value
				.addClass( this.widgetBaseClass + '-novalue' )
				.text( mw.msg( 'valueview-preview-novalue' ) );
			} else {
				this.$value
				.removeClass( this.widgetBaseClass + '-novalue' )
				.text( value );
			}
		},

		/**
		 * Shows a spinner symbol instead of any preview.
		 */
		showSpinner: function() {
			this.$value.empty().append( $( '<span/>' ).addClass( 'mw-small-spinner' ) );
		}

	} );

} )( jQuery, mediaWiki );
