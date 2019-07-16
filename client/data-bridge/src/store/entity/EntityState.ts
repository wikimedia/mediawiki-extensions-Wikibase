import StatementMap from '@/datamodel/StatementMap';

interface EntityState {
	id: string;
	baseRevision: number;
	statements: StatementMap|null;
}

export default EntityState;
