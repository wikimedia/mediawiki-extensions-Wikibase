import StatementMap from '@/datamodel/StatementMap';

export default interface StatementsState {
	[ entityId: string ]: StatementMap;
}
