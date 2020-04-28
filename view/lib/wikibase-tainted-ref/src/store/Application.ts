import StatementsTaintedState from '@/store/StatementsTaintedState';
import StatementsPopperIsOpen from '@/store/StatementsPopperIsOpen';
import StatementsEditState from '@/store/StatementsEditState';

export default interface Application {
	statementsTaintedState: StatementsTaintedState;
	statementsPopperIsOpen: StatementsPopperIsOpen;
	statementsEditState: StatementsEditState;
	helpLink: string;
}
