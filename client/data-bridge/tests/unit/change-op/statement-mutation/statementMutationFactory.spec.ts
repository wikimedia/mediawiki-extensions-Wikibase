import statementMutationFactory from '@/change-op/statement-mutation/statementMutationFactory';
import EditDecision from '@/definitions/EditDecision';
import ReplaceMutationStrategy from '@/change-op/statement-mutation/strategies/ReplaceMutationStrategy';
import UpdateMutationStrategy from '@/change-op/statement-mutation/strategies/UpdateMutationStrategy';

describe( 'statementMutationFactory', () => {
	it( 'creates a ReplaceMutationStrategy for the REPLACE decision', () => {
		const strategy = statementMutationFactory( EditDecision.REPLACE );
		expect( strategy ).toBeInstanceOf( ReplaceMutationStrategy );
	} );

	it( 'creates a UpdateMutationStrategy for the UPDATE decision', () => {
		expect( statementMutationFactory( EditDecision.UPDATE ) ).toBeInstanceOf( UpdateMutationStrategy );
	} );
} );
