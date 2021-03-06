<?php
Rhaco::import("network.http.Header");
Rhaco::import("database.TableObjectUtil");
/**
 * Views用filter
 * 
 * @author Kazutaka Tokushima
 * @license New BSD License
 * @copyright Copyright 2008- rhaco project. All rights reserved.
 */
class ViewsFilter{
	/**
	 * create / confirmedCreate
	 *
	 * @param mixed $object
	 * @param string $redirectHref
	 * @param boolean $isarg
	 */
	function afterCreate($object,$redirectHref=null,$isarg=false){
		$this->_redirect($redirectHref,$object,$isarg);
	}
	/**
	 * update / confirmedUpdate
	 *
	 * @param mixed $object
	 * @param string $redirectHref
	 * @param boolean $isarg
	 */
	function afterUpdate($object,$redirectHref=null,$isarg=false){
		$this->_redirect($redirectHref,$object,$isarg);
	}
	/**
	 * drop
	 *
	 * @param mixed $object
	 * @param string $redirectHref
	 * @param boolean $isarg
	 */
	function afterDrop($object,$redirectHref=null){
		$this->_redirect($redirectHref);
	}

	function _redirect($redirectHref,$tableObject=null,$isarg=false){
		if($tableObject != null && $isarg){
			if(empty($redirectHref)) $redirectHref = Rhaco::uri();			
			$href = (substr($redirectHref,-1) == "/") ? substr($redirectHref,0,-1) : $redirectHref;

			if(TableObjectUtil::setaccessor($tableObject)){
				foreach($tableObject->primaryKey() as $column){
					$href .= "/".TableObjectUtil::getter($tableObject,$column);
				}
			}
			$redirectHref = $href;
		}
		Header::redirect($redirectHref);
	}
}
?>