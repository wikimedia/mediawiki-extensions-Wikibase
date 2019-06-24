import { selectLinks, filterLinksByHref } from '@/mediawiki/selectLinks';

describe( 'selectLinks', () => {

	it( 'find relevant links in mark-up', () => {
		document.body.innerHTML = `
		<div>
		 <a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P31">
			a link to be selected
		 </a>
		 <a title="Page with sitelink to item">
			a link to be not selected
		 </a>
		</div>`;

		const actualSelectedLinks = selectLinks();

		expect( actualSelectedLinks.length ).toBe( 1 );
		expect( actualSelectedLinks[ 0 ].text.trim() ).toBe( 'a link to be selected' );
	} );

	it( 'filters the links by their href', () => {
		const linkToBeSelected = document.createElement( 'a' );
		linkToBeSelected.href = 'https://www.wikidata.org/wiki/Q4115189#P31';
		linkToBeSelected.textContent = 'a link to be selected';
		const linkToBeNotSelected = document.createElement( 'a' );
		linkToBeNotSelected.href = '/mediawiki/index.php?title=Page_with_sitelink_to_item';
		linkToBeNotSelected.textContent = 'a link to be not selected';

		const actualFilteredLinks = filterLinksByHref( [
			linkToBeSelected,
			linkToBeNotSelected,
		] );

		expect( actualFilteredLinks.length ).toBe( 1 );
		expect( actualFilteredLinks[ 0 ].text.trim() ).toBe( 'a link to be selected' );
	} );

} );
