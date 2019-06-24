import selectLinks from '@/mediawiki/selectLinks';

test( 'find relevant links in mark-up', () => {
	document.body.innerHTML = `
		<div>
		 <a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P31">
			a link to be selected
		 </a>
		 <a href="/mediawiki/index.php?title=Page_with_sitelink_to_item" title="Page with sitelink to item">
			a link to be not selected
		 </a>
		</div>`;

	const actualSelectedLinks = selectLinks();

	expect( actualSelectedLinks.length ).toBe( 1 );
	expect( actualSelectedLinks[ 0 ].text.trim() ).toBe( 'a link to be selected' );
} );
