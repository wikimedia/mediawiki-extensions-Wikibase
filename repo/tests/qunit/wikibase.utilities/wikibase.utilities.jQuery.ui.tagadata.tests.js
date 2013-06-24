/**
 * QUnit tests for Wikibase 'tag-a-data' jQuery plugin
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function() {
	module( 'wikibase.utilities.jQuery.ui.tagadata', window.QUnit.newWbEnvironment( {
		setup: function() {
			/* need to append the element to the DOM since jQuery's removeClass() within tagadata's destroy function
			 would cause a Firefox exclusive error */
			this.subject = $( '<ul><li>A</li><li><!--empty tag--></li><li>B</li><li>C</li></ul>' ).appendTo( 'body' );

			this.$getWidget = function() {
				return this.subject.data( 'tagadata' );
			};
		},

		teardown: function() {
			this.$getWidget().destroy();
			this.subject.remove();
		}
	} ) );

	test( 'jQuery.tagadata() basics', function() {

		equal(
			this.subject.tagadata(),
			this.subject,
			'"tag-a-data" initialized, returns the original jQuery object'
		);

		equal(
			this.$getWidget().getTags().length,
			3,
			'Three tags attached'
		);

		ok(
			this.$getWidget().createTag( 'foo', 'foo-class' ).hasClass( 'foo-class' ),
			'New tag created has assigned class'
		);

		equal(
			this.$getWidget().getTags().length,
			4,
			'Tag was attached'
		);

		ok(
			this.$getWidget().createTag( 'foo' ).hasClass( 'foo-class' ) && this.$getWidget().getTags().length == 4,
			'Creating tag which already exists returns existing tag instead of creating new one'
		);

		equal(
			this.$getWidget().getTagLabel( this.$getWidget().getTag( 'foo' ) ),
			'foo',
			'Created tag can be grabbed and label can be determined by getTagLabel()'
		);

		equal(
			this.$getWidget().removeTag( this.$getWidget().getTag( 'foo' ) ),
			true,
			'Removed tag'
		);

		equal(
			this.$getWidget().removeTag( $( '<span/>' ) ),
			false,
			'Tried to remove non-existent tag, should return false'
		);

		this.$getWidget().disable();

		equal(
			this.$getWidget().isDisabled(),
			true,
			'Disabled widget'
		);

		this.$getWidget().enable();

		equal(
			this.$getWidget().isDisabled(),
			false,
			'Enabled widget'
		);

	} );

}() );
