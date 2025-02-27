// Copied from mediawiki-core's mediawiki.action.edit.collapsibleFooter.js
( function () {
	'use strict';

	var collapsibleLists, handleOne;

	// Collapsible lists of categories and templates
	collapsibleLists = [
		{
			listSel: '.wikibase-entity-usage ul',
			togglerSel: '.wikibase-entityusage-explanation',
			storeKey: 'mwedit-state-wikibaseEntityUsage'
		}
	];

	handleOne = function ( $list, $toggler, storeKey ) {
		var collapsedVal = '0',
			expandedVal = '1',
			// Default to collapsed if not set
			isCollapsed = mw.storage.get( storeKey ) !== expandedVal;

		// Style the toggler with an arrow icon and add a tabIndex and a role for accessibility
		$toggler.addClass( 'mw-editfooter-toggler' ).prop( 'tabIndex', 0 ).attr( 'role', 'button' );
		$list.addClass( 'mw-editfooter-list' );

		$list.makeCollapsible( {
			$customTogglers: $toggler,
			linksPassthru: true,
			plainMode: true,
			collapsed: isCollapsed
		} );

		if ( isCollapsed ) {
			$toggler.addClass( 'mw-editfooter-toggler-collapsed' );
		}

		$list.on( 'beforeExpand.mw-collapsible', () => {
			$toggler.removeClass( 'mw-editfooter-toggler-collapsed' );
			mw.storage.set( storeKey, expandedVal );
		} );

		$list.on( 'beforeCollapse.mw-collapsible', () => {
			$toggler.addClass( 'mw-editfooter-toggler-collapsed' );
			mw.storage.set( storeKey, collapsedVal );
		} );
	};

	mw.hook( 'wikipage.editform' ).add( ( $editForm ) => {
		var i;
		for ( i = 0; i < collapsibleLists.length; i++ ) {
			// Pass to a function for iteration-local variables
			handleOne(
				$editForm.find( collapsibleLists[ i ].listSel ),
				$editForm.find( collapsibleLists[ i ].togglerSel ),
				collapsibleLists[ i ].storeKey
			);
		}
	} );
}() );
