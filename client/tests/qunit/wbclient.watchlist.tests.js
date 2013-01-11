/**
 * QUnit tests description edit tool
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseClient
 *
 * @since 0.4
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
( function( mw, $, QUnit ) {
	'use strict';

	QUnit.module( 'mw.wbclient.watchlist', QUnit.newMwEnvironment() );

	QUnit.test( 'wikibase client toggle', function ( assert ) {
		var $namespaceFilters, $env, $toggleLink, $wbToggle, $mockWatchlist,
			$sections, $watchlistSections;

		$namespaceFilters =
			'<fieldset id="mw-watchlist-options">'
			+ '<form method="post" action="/wiki/Special:Watchlist" id="mw-watchlist-form-namespaceselector"><hr />'
			+ '<p><label for="namespace">Namespace:</label>&#160;'
			+ '<select class="namespaceselector" id="namespace" name="namespace">'
			+ '<option value="" selected="">all</option>'
			+ '<option value="0">(Main)</option>'
			+ '<option value="1">Talk</option>'
			+ '<option value="2">User</option>'
			+ '<option value="3">User talk</option>'
			+ '<option value="4">Project</option>'
			+ '<option value="5">Project talk</option>'
			+ '<option value="6">File</option>'
			+ '<option value="7">File talk</option>'
			+ '<option value="8">MediaWiki</option>'
			+ '<option value="9">MediaWiki talk</option>'
			+ '<option value="10">Template</option>'
			+ '<option value="11">Template talk</option>'
			+ '<option value="12">Help</option>'
			+ '<option value="13">Help talk</option>'
			+ '<option value="14">Category</option>'
			+ '<option value="15">Category talk</option>'
			+ '</select>'
			+ '</fieldset>';

		$watchlistSections =
			'<h4>10 January 2013</h4>'
			+ '<ul class="special">'
			+ '<li class="mw-line-even mw-changeslist-line-not-watched watchlist-0-Japan"></li>'
			+ '</ul>'
			+ '<h4>9 January 2013</h4>'
            + '<ul class="special">'
            + '<li class="mw-line-odd mw-changeslist-line-not-watched watchlist-0-Japan wikibase-edit"></li>'
            + '<li class="mw-line-even mw-changeslist-line-not-watched watchlist-0-Japan wikibase-edit"></li>'
			+ '</ul>'
			+ '<h4>8 January 2013</h4>'
			+ '<ul class="special">'
			+ '<li class="mw-line-even mw-changeslist-line-not-watched watchlist-0-Japan wikibase-edit"></li>'
			+ '<li class="mw-line-even mw-changeslist-line-not-watched watchlist-0-Japan"></li>'
			+ '</ul>';

		$mockWatchlist = $( $namespaceFilters ).after( $( $watchlistSections ) );
		$env = $( '<div>' ).html( $mockWatchlist ).appendTo( 'body' );

		mw.wbclient.watchlist.addFilter();
		strictEqual( mw.wbclient.watchlist.toggleOn, false, 'Wikibase toggle set to false by default' );

		$toggleLink = mw.wbclient.watchlist.getShowHideLink( false );
		strictEqual( $toggleLink, '<a id="wb-toggle-link" href="javascript:void(0);">Show</a>',
			'Wikibase toggle link html (show)' );

		strictEqual(
			mw.wbclient.watchlist.getToggle( $toggleLink ),
			'<span id="wb-toggle"><a id="wb-toggle-link" href="javascript:void(0);">Show</a> Wikidata</span>',
			'Wikibase toggle html (show)'
		);

		$toggleLink = mw.wbclient.watchlist.getShowHideLink( true );
		strictEqual( $toggleLink, '<a id="wb-toggle-link" href="javascript:void(0);">Hide</a>',
            'Wikibase toggle link html (hide)' );

        strictEqual(
			mw.wbclient.watchlist.getToggle( $toggleLink ),
			'<span id="wb-toggle"><a id="wb-toggle-link" href="javascript:void(0);">Hide</a> Wikidata</span>',
            'Wikibase toggle html (hide)'
		);

		strictEqual(
			$( '#mw-watchlist-form-namespaceselector' ).prev().attr( 'id' ), 'wb-toggle',
				'Toggle position before namespace selector' );

		$sections = mw.wbclient.watchlist.getSections();
		strictEqual( $sections.length, 3, 'Number of watchlist date sections' );
		strictEqual( mw.wbclient.watchlist.hasWBEditsOnly( $sections[0] ), false, 'Wikibase edits only (section 1)' );
		strictEqual( mw.wbclient.watchlist.hasWBEditsOnly( $sections[1] ), true, 'Wikibase edits only (section 2)' );
		strictEqual( mw.wbclient.watchlist.hasWBEditsOnly( $sections[2] ), false, 'Wikibase edits only (section 3)' );

		mw.wbclient.watchlist.toggleOn = false;
		mw.wbclient.watchlist.toggleSections( $sections );

		strictEqual( $( $sections[0] ).prev().css( 'display' ), 'block', 'Test section 0 hiding' );
		strictEqual( $( $sections[1] ).prev().css( 'display' ), 'none', 'Test section 1 hiding' );
		strictEqual( $( $sections[2] ).prev().css( 'display' ), 'block', 'Test section 2 hiding' );

		mw.wbclient.watchlist.toggleOn = true;
		mw.wbclient.watchlist.toggleSections( $sections );

        strictEqual( $( $sections[1] ).prev().css( 'display' ), 'block', 'Test section 1 hiding' );

		$env.remove();
	});

}( mediaWiki, jQuery, QUnit ) );
