import EditDecision from '@/definitions/EditDecision';
import StatementMutationStrategy from './strategies/StatementMutationStrategy';
import ReplaceMutationStrategy from './strategies/ReplaceMutationStrategy';
import UpdateMutationStrategy from '@/change-op/statement-mutation/strategies/UpdateMutationStrategy';

export default function statementMutationFactory( strategy: EditDecision ): StatementMutationStrategy {
	switch ( strategy ) {
		case EditDecision.REPLACE:
			return new ReplaceMutationStrategy();
		case EditDecision.UPDATE:
			return new UpdateMutationStrategy();
	}
}
