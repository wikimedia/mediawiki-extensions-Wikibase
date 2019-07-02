import MwWindow from '@/@types/mediawiki/MwWindow';
import { selectLinks, filterLinksByHref } from '@/mediawiki/selectLinks';

describe( 'selectLinks', () => {

	beforeEach( () => {
		( window as MwWindow ).mw = {
			config: {
				get() {
					return {
						hrefRegExp: 'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
					};
				},
			},
			loader: {
				using: jest.fn(),
			},
			log: {
				deprecate: jest.fn(),
				error: jest.fn(),
				warn: jest.fn(),
			},
		};
	} );

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

	it( 'warns on missing hrefRegExp', () => {
		( window as MwWindow ).mw.config.get = () => {
			return {
				hrefRegExp: null,
			};
		};
		const warn = jest.fn();
		( window as MwWindow ).mw.log.warn = warn;

		const linkToBeNotSelected = document.createElement( 'a' );
		linkToBeNotSelected.href = 'https://www.wikidata.org/wiki/Q4115189#P31';
		linkToBeNotSelected.textContent = 'not selected even though href matches the usual regexp';

		const actualSelectedLinks = filterLinksByHref( [ linkToBeNotSelected ] );

		expect( actualSelectedLinks.length ).toBe( 0 );
		expect( warn.mock.calls.length ).toBe( 1 );
	} );

} );
