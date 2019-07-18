import BridgeDomElementsSelector from '@/mediawiki/BridgeDomElementsSelector';

describe( 'domBridgeElementSelector', () => {

	it( 'finds multiple relevant links in mark-up', () => {
		const validHref = 'https://www.wikidata.org/wiki/Q4115189#P31';
		const validHrefWithQueryString = 'https://www.wikidata.org/wiki/Q11235?uselang=en#P314';
		document.body.innerHTML = `
		<div>
		 <span data-bridge-edit-flow="overwrite">
		  <a rel="nofollow" class="external text" href="${validHref}">
			a link to be selected
		  </a>
		 </span>
		 <span data-bridge-edit-flow="overwrite" data-bridge-entity-id="Q123456" data-bridge-property-id="P123">
		  <button>Hi, I'm a button! I should not be selected!</button>
		 </span>
		 <span data-bridge-edit-flow="overwrite">
		  <a rel="nofollow" class="external text" href="${validHrefWithQueryString}">
			a link to be selected
		  </a>
		 </span>
		 <a rel="nofollow" class="external text" href="${validHref}">
			a link without the wrapping span, to not be selected
		 </a>
		 <a title="Page with sitelink to item">
			a link to be not selected
		 </a>
		</div>`;

		const selector = new BridgeDomElementsSelector(
			'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
		);
		const actualSelectedElementsWithData = selector.selectElementsToOverload();

		expect( actualSelectedElementsWithData.length ).toBe( 2 );

		expect( actualSelectedElementsWithData[ 0 ].entityId ).toBe( 'Q4115189' );
		expect( actualSelectedElementsWithData[ 0 ].propertyId ).toBe( 'P31' );
		expect( actualSelectedElementsWithData[ 0 ].editFlow ).toBe( 'overwrite' );

		expect( actualSelectedElementsWithData[ 1 ].entityId ).toBe( 'Q11235' );
		expect( actualSelectedElementsWithData[ 1 ].propertyId ).toBe( 'P314' );
		expect( actualSelectedElementsWithData[ 1 ].editFlow ).toBe( 'overwrite' );
	} );

	describe( 'given valid html', () => {
		it.each( [
			[
				'can parse information from href',
				{
					html: `
<span data-bridge-edit-flow="overwrite">
	<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P31">a link to be selected</a>
</span>`,
					expectedEntityId: 'Q4115189',
					expectedPropertyId: 'P31',
					editFlow: 'overwrite',
				},
			],
			[
				'ignores additional elements, as long as they are not another link',
				{
					html: `
<span data-bridge-edit-flow="overwrite">
	<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P31">a link to be selected</a>
	<button>You could also click me!</button>
</span>`,
					expectedEntityId: 'Q4115189',
					expectedPropertyId: 'P31',
					editFlow: 'overwrite',
				},
			],
			[
				'ignores additional data attributes',
				{
					html: `
<span data-bridge-edit-flow="overwrite" data-bridge-entity-id="Q123456" data-bridge-property-id="P123">
	<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P31">
		a link to be selected
	</a>
</span>`,
					expectedEntityId: 'Q4115189',
					expectedPropertyId: 'P31',
					editFlow: 'overwrite',
				},
			],
			[
				'works also on other elements than spans',
				{
					html: `<table><tr>
<td data-bridge-edit-flow="overwrite">
	<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P31">a link to be selected</a>
</td></tr></table>`,
					expectedEntityId: 'Q4115189',
					expectedPropertyId: 'P31',
					editFlow: 'overwrite',
				},
			],
		] )( '%s', ( _, { html, expectedEntityId, expectedPropertyId, editFlow } ) => {
			document.body.innerHTML = html;

			const selector = new BridgeDomElementsSelector(
				'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
			);
			const actualSelectedElementsWithData = selector.selectElementsToOverload();

			expect( actualSelectedElementsWithData.length ).toBe( 1 );

			expect( actualSelectedElementsWithData[ 0 ].entityId ).toBe( expectedEntityId );
			expect( actualSelectedElementsWithData[ 0 ].propertyId ).toBe( expectedPropertyId );
			expect( actualSelectedElementsWithData[ 0 ].editFlow ).toBe( editFlow );
		} );

	} );

	describe( 'given html without valid markup', () => {
		it.each(
			[
				[
					'skips links without a surrounding span',
					{
						html: `
<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P31">
	a link without the wrapping span, to not be selected
</a>`,
					},
				],
				[
					'skips if there are multiple links inside',
					{
						html: `
<span data-bridge-edit-flow="overwrite">
	<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P31">a link to be selected</a>
	<a rel="nofollow" class="external text" href="https://google.com">another link</a>
</span>`,
					},
				],
				[
					'skips links without a propertyId',
					{
						html: `
<span data-bridge-edit-flow="overwrite">
	<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189">link text</a>
</span>`,
					},
				],
				[
					'skips span with empty editflow',
					{
						html: `
<span data-bridge-edit-flow="" data-bridge-entity-id="Q12" data-bridge-property-id="P12">
	<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P123">link text</a>
</span>`,
					},
				],
				[
					'skips span with unknown editflow',
					{
						html: `
<span data-bridge-edit-flow="unknownEditflow" data-bridge-entity-id="Q12" data-bridge-property-id="P12">
	<a rel="nofollow" class="external text" href="https://www.wikidata.org/wiki/Q4115189#P123">link text</a>
</span>`,
					},
				],
				[
					'skips span without an unrelated link',
					{
						html: `
<span data-bridge-edit-flow="overwrite">
	<a rel="nofollow" class="external text" href="https://google.com/">link text</a>
</span>`,
					},
				],
			],
		)( '%s', ( _, { html } ) => {
			document.body.innerHTML = html;

			const selector = new BridgeDomElementsSelector(
				'https://www\\.wikidata\\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
			);
			const actualSelectedElementsWithData = selector.selectElementsToOverload();

			expect( actualSelectedElementsWithData.length ).toBe( 0 );
		} );
	} );

} );
