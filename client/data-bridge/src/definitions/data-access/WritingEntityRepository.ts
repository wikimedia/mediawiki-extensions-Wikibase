import Entity from '@/datamodel/Entity';
import EntityRevision from '@/datamodel/EntityRevision';

interface WritingEntityRepository {
	/**
	 * Rejects to TechnicalProblem or EntityNotFound errors in case of problems
	 */
	saveEntity( entity: Entity, base?: EntityRevision ): Promise<EntityRevision>;
}
export default WritingEntityRepository;
