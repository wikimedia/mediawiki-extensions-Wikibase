import StatementMap from '@/datamodel/StatementMap';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';

interface CondensedEntityRevision {
	id?: string;
	statements?: StatementMap;
	revisionId?: number;
}

export default function ( fields: CondensedEntityRevision ): EntityRevision {
	return new EntityRevision(
		new Entity(
			fields.id || 'Q1',
			fields.statements || {},
		),
		fields.revisionId || 0,
	);
}
