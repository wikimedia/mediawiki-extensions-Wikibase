<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * Consumers acquire ids for stored terms to be used to link
 * entities to these terms.
 */
interface TermIdsAcquirer {

	/**
	 * acquires ids for given terms.
	 *
	 * @param array $termsArray array containing terms per type per language.
	 *  Example:
	 * 	[
	 *		'label' => [
	 *			'en' => 'some label',
	 *			'de' => 'another label',
	 *			...
	 *		],
	 *		'alias' => [
	 *			'en' => [ 'alias', 'another alias', ...],
	 *			'de' => 'de alias',
	 *			...
	 *		],
	 *		...
	 *  ]
	 *
	 * @param callable|null $acquiredIdsConsumerCallback callback that is passed
	 *	the acquired ids in an array as its only argument.
	 *	If a callable is passed to this parameter, this function guarantees
	 *	that the acquired ids that were passed to it will continue to exist
	 *	in the underlying storage.
	 *	If null is passed to this parameter, this function will not guarantee
	 *	that any acquired ids will continue to exist, as the clean up logic
	 *	might be running in parallel and may result in deleting those acquired
	 *	ids if they existed already in underlying storage and were decided to
	 *	be cleaned by another process.
	 */
	public function acquireTermIds(
		array $termsArray,
		callable $acquiredIdsConsumerCallback = null
	);

}
