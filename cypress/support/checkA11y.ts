/**
 * Check accessibility using axe, failing the test on violations.
 *
 * @param {string|undefined} context Usually a string selector (check only this element)
 * or undefined (check the whole document). See cy.checkA11y() for the exact type.
 * @param {object|undefined} options Options for axe.run, see {@link https://www.deque.com/axe/core-documentation/api-documentation/#api-name-axerun documentation}.
 * Can include rules to disable, via `rules: { 'rule-id': { enabled: false } }`.
 */
export function checkA11y( context = undefined, options = undefined ): void {
	cy.checkA11y( context, options, ( violations ) => {
		// log the full violations to the browser console (only visible when testing manually using cypress:open)
		// eslint-disable-next-line no-console
		console.log( violations );
		// log a summary to the cypress log (also visible when using cypress:run, including in CI)
		cy.task( 'log', `${ violations.length } accessibility violation(s) detected` );
		cy.task( 'table', violations.map( ( { id, impact, description, nodes } ) => ( {
			// to keep the table readable, log only a subset of the properties
			id,
			impact,
			description,
			nodes: nodes.length,
		} ) ) );
	} );
}
