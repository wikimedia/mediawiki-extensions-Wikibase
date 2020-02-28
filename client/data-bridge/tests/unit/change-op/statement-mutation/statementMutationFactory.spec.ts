import statementMutationFactory from '@/change-op/statement-mutation/statementMutationFactory';
import EditDecision from '@/definitions/EditDecision';
import ReplaceMutationStrategy from '@/change-op/statement-mutation/strategies/ReplaceMutationStrategy';

describe( 'statementMutationFactory', () => {
	it( 'creates a ReplaceMutationStrategy for the REPLACE decision', () => {
		const strategy = statementMutationFactory( EditDecision.REPLACE );
		expect( strategy ).toBeInstanceOf( ReplaceMutationStrategy );
	} );

	it( 'explodes for the UPDATE decision', () => {
		expect( () => {
			statementMutationFactory( EditDecision.UPDATE );
		} ).toThrowError( 'There is no implementation for the selected mutation strategy: update' );
	} );
} );
