import EditDecision from '@/definitions/EditDecision';
import StatementMutationStrategy from './strategies/StatementMutationStrategy';
import ReplaceMutationStrategy from './strategies/ReplaceMutationStrategy';

export default function statementMutationFactory( strategy: EditDecision ): StatementMutationStrategy {
	switch ( strategy ) {
		case EditDecision.REPLACE:
			return new ReplaceMutationStrategy();
		default:
			throw new Error( 'There is no implementation for the selected mutation strategy: ' + strategy );
	}
}
