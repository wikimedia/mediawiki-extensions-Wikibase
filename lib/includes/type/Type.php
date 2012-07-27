<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daniel
 * Date: 27.07.12
 * Time: 18:00
 * To change this template use File | Settings | File Templates.
 */
interface Type {

	/**
	 * @return String
	 */
	public function getTypeID();
}

abstract class TypeBase implements Type {

	public function getTypeID() {
		return get_class( $this );
	}

}