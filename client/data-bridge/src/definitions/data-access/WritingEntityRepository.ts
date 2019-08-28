import EntityRevision from '@/datamodel/EntityRevision';

interface WritingEntityRepository {
	/**
	 * Rejects to TechnicalProblem or EntityNotFound errors in case of problems
	 */
	saveEntity( entity: EntityRevision ): Promise<EntityRevision>;
}
export default WritingEntityRepository;
