import { StatementMap } from '@wmde/wikibase-datamodel-types';
import { StatementState } from '@/store/statements/StatementState';

export default function newStatementState(
	statements?: { [ key: string ]: StatementMap },
): StatementState {
	const state: StatementState = {};
	if ( statements ) {
		Object.assign( state, statements );
	}

	return state;
}
