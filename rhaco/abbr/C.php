<?php
Rhaco::import("database.model.Criteria");
/**
 * database.model.Criteraのエイリアス
 * 全て静的利用
 * 
 * @author Kazutaka Tokushima
 * @license New BSD License
 * @copyright Copyright 2007- rhaco project. All rights reserved.
 */
class C extends Criteria{
	/**
	 * AND 式
	 * @param Criteria $criteria
	 */
	function andc($criteria){
		/*** unit("database.CriteriaTest"); */
		return $this->addCriteria($criteria);
	}
	/**
	 * OR 式
	 * @param Criteria $criteria
	 * @return Criteria $this
	 */
	function orc($criteria){
		/*** unit("database.CriteriaTest"); */
		return $this->addCriteriaOr($criteria);
	}
}
?>