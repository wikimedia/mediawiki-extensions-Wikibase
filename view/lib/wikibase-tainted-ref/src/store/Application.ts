import StatementsTaintedState from '@/store/StatementsTaintedState';
import StatementsPopperIsOpen from '@/store/StatementsPopperIsOpen';

export default interface Application {
	statementsTaintedState: StatementsTaintedState;
	statementsPopperIsOpen: StatementsPopperIsOpen;
	helpLink: string;
}
