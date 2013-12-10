/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, $, vv ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * Valueview expert based on StringValue expert but with a jQuery suggester loaded for offering
	 * the user auto compleation features.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.StringValue
	 *
	 * TODO: Implement this as an "extension" for the StringValue expert. This could be done by
	 *  adding a system for extensions which get initialized in addition to a specific expert.
	 *  Those extensions would also require registration, this should probably be done by introducing
	 *  a more complex format for registering an expert plus extensions to an expert factory.
	 */
	vv.experts.SuggestedStringValue = vv.expert( 'suggestedstringvalue', PARENT, {
		/**
		 * @see Query.valueview.experts.StringValue._init
		 */
		_init: function() {
			PARENT.prototype._init.call( this );

			var notifier = this._viewNotifier,
				$input = this.$input;

			// Initialize Commons Media suggestion dropdown on top of string input field:
			$input.suggester( this._options.suggesterOptions );

			// Since we're using the input auto expand, we have to update the position of the
			// dropdown whenever the input box expands vertically:
			$input.eachchange( function( event, oldValue ) {
				// TODO/OPTIMIZE: only reposition when necessary, i.e. when expanding vertically
				$input.data( 'suggester' ).repositionMenu();
			} );

			$input.on( 'suggesterresponse suggesterclose', function( event, response ) {
				notifier.notify( 'change' ); // here in addition to 'eachchange' from StringValue expert
				$input.data( 'inputAutoExpand' ).expand();
			} );
		},

		/**
		 * @see Query.valueview.experts.StringValue.draw
		 */
		draw: function() {
			PARENT.prototype.draw.call( this );

			// Make sure suggester is closed in non-edit mode:
			if( !this._viewState.isInEditMode() ) {
				this.$input.data( 'suggester' ).close();
			}
		}
	} );

}( dataValues, jQuery, jQuery.valueview ) );
