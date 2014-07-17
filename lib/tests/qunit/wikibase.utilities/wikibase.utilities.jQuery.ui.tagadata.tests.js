/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( $, QUnit ) {
'use strict';

function newTestTagadata() {
	// Need to append the element to the DOM since jQuery's removeClass() within tagadata's
	// destroy function would cause a Firefox exclusive error.
	return $( '<ul class="test_tagadata">'
		+ '<li>A</li><li><!--empty tag--></li><li>B</li><li>C</li>'
		+ '</ul>' ).tagadata().appendTo( 'body' );
}

QUnit.module( 'wikibase.utilities.jQuery.ui.tagadata', QUnit.newWbEnvironment( {
	teardown: function() {
		$( '.test_tagadata' ).each( function() {
			$( this ).data( 'tagadata' ).destroy();
			$( this ).remove();
		} );
	}
} ) );

QUnit.test( 'jQuery.tagadata() basics', function( assert ) {
	var $tagadata = newTestTagadata(),
		tagadata = $tagadata.data( 'tagadata' );

	assert.ok(
		tagadata !== undefined,
		'"tag-a-data" initialized.'
	);

	assert.equal(
		tagadata.getTags().length,
		3,
		'Three tags attached.'
	);

	assert.ok(
		tagadata.createTag( 'foo', 'foo-class' ).hasClass( 'foo-class' ),
		'New tag created has assigned class.'
	);

	assert.equal(
		tagadata.getTags().length,
		4,
		'Tag was attached.'
	);

	assert.ok(
		tagadata.createTag( 'foo' ).stop().hasClass( 'foo-class' ) && tagadata.getTags().length === 4,
		'Creating tag which already exists returns existing tag instead of creating new one.'
	);

	assert.equal(
		tagadata.getTagLabel( tagadata.getTag( 'foo' ) ),
		'foo',
		'Created tag can be grabbed and label can be determined by getTagLabel().'
	);

	QUnit.stop();

	$tagadata.one( 'tagadatatagremoved', function( event, $tag ) {
		QUnit.start();

		assert.ok(
			true,
			'Removed tag.'
		);
	} );

	tagadata.removeTag( tagadata.getTag( 'foo' ) );

	assert.strictEqual(
		tagadata.removeTag( $( '<span/>' ) ),
		false,
		'Trying to remove non-existent tag returns "false"'
	);

	tagadata.disable();

	assert.strictEqual(
		tagadata.isDisabled(),
		true,
		'Disabled widget.'
	);

	tagadata.enable();

	assert.strictEqual(
		tagadata.isDisabled(),
		false,
		'Enabled widget.'
	);

} );

}( jQuery, QUnit ) );
