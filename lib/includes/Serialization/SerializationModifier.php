<?php

namespace Wikibase\Lib\Serialization;

/**
 * Class which can be used to easily modify serializations and arrays.
 *
 * This could easily be factored out into a library.
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SerializationModifier {

	/**
	 * @param array $array the array to modify
	 * @param null|string $path the path that we want to modify.
	 *     Element keys should be seperated with / characters.
	 *     * characters can be used to match all keys at a given level.
	 *     null can be used to modify $array directly.
	 *     Examples:
	 *         null
	 *         'foo/*'
	 *         'root/entities/*\/statement/references/*\/snaks/*'
	 * @param callback $callback
	 *  Callback accepts 1 parameter which is the element to touch
	 *  Callback should return the altered element
	 *
	 * @return array the altered array
	 */
	public function modifyUsingCallback( array $array, $path, $callback ) {
		$elementsToTouch = $this->getElementsMatchingPath(
			$array,
			$this->getPathParts( $path )
		);

		foreach ( $elementsToTouch as $key => $element ) {
			$newElement = call_user_func( $callback, $element );
			$this->setArrayValueAtKey( $array, $key, $newElement );
		}

		return $array;
	}

	/**
	 * @param array $array
	 * @param string $path
	 * @param mixed $value
	 */
	private function setArrayValueAtKey( array &$array, $path, $value ) {
		$current = &$array;
		$pathParts = $this->getPathParts( $path );

		foreach ( $pathParts as $key ) {
			$current = &$current[$key];
		}

		$current = $value;
	}

	/**
	 * Method to get elements that match the path given.
	 * This is called recursively along with getElementsForAllKeys and getElementsForKey
	 * The number of calls depends on the depth of the array.
	 *
	 * @param array $array
	 * @param string[] $pathElements
	 * @param string $currentPath
	 *
	 * @return array
	 */
	private function getElementsMatchingPath( array $array, array $pathElements, $currentPath = '' ) {
		$elements = [];

		if ( empty( $pathElements ) ) {
			$elements[$currentPath] = $array;
		} else {
			$currentKey = array_shift( $pathElements );

			if ( $currentKey === '*' ) {
				$elements = array_merge(
					$elements,
					$this->getElementsForAllKeys( $array, $pathElements, $currentPath )
				);
			} else {
				$elements = array_merge(
					$elements,
					$this->getElementsForKey( $array, $pathElements, $currentPath, $currentKey )
				);
			}
		}

		return $elements;
	}

	/**
	 * @param array $array
	 * @param string[] $pathElements
	 * @param string $currentPath
	 *
	 * @return array
	 */
	private function getElementsForAllKeys( array $array, array $pathElements, $currentPath ) {
		$elements = [];

		foreach ( array_keys( $array ) as $arrayKey ) {
			$elements = array_merge(
				$elements,
				$this->getElementsForKey( $array, $pathElements, $currentPath, $arrayKey )
			);
		}

		return $elements;
	}

	/**
	 * @param array $array
	 * @param string[] $pathElements
	 * @param string $currentPath
	 * @param string $key
	 *
	 * @return array
	 */
	private function getElementsForKey( array $array, array $pathElements, $currentPath, $key ) {
		$elements = [];

		if ( isset( $array[$key] ) ) {
			$thisPath = $this->getJoinedPath( $currentPath, $key );

			if ( is_array( $array[$key] ) ) {
				$elements = array_merge(
					$elements,
					$this->getElementsMatchingPath( $array[$key], $pathElements, $thisPath )
				);
			} else {
				$elements[$thisPath] = $array[$key];
			}

		}

		return $elements;
	}

	/**
	 * @param string $prefix
	 * @param string $key
	 *
	 * @return string
	 */
	private function getJoinedPath( $prefix, $key ) {
		if ( $prefix === '' ) {
			return $key;
		}

		return $prefix . '/' . $key;
	}

	/**
	 * @param null|string $path
	 *
	 * @return string[]
	 */
	private function getPathParts( $path ) {
		if ( $path === null || $path === '' ) {
			return [];
		}

		return explode( '/', $path );
	}

}
