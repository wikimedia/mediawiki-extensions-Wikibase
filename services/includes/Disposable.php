<?php

/**
 * An interface for objects that support explicit disposal.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 *
 * @todo make this reusable outside Wikibase
 */
interface Disposable {

	/**
	 * Releases any system (or other) resources held by this object.
	 *
	 * It is safe to call dispose() multiple times.
	 * The behavior of all other methods of this object becomes undefined after calling dispose()
	 * for the first time.
	 *
	 * Implementing classes may choose to implement the __destruct() method to call dispose().
	 */
	public function dispose();

}
