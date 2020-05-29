import { StatementMap } from '@wmde/wikibase-datamodel-types';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';

interface CondensedEntityRevision {
	id?: string;
	statements?: StatementMap;
	revisionId?: number;
}

export default function ( fields?: CondensedEntityRevision ): EntityRevision {
	return new EntityRevision(
		new Entity(
			fields && fields.id || 'Q1',
			fields && fields.statements || {},
		),
		fields && fields.revisionId || 0,
	);
}
