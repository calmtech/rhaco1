<?php
Rhaco::import("network.http.RequestUtil");
Rhaco::import("network.http.model.RequestLoginConditionFile");
Rhaco::import("lang.Variable");
/**
 * ログイン認証を追加したRequest
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 * @copyright Copyright 2005- rhaco project. All rights reserved.
 */
class RequestLogin extends RequestUtil{
	/**
	 * ログインする
	 * @param RequestLoginCondition $requestLoginCondition
	 * @param Request $request
	 * @return boolean
	 */
	function login($requestLoginCondition=null,$request=null){
		if(RequestLogin::isLogin()) return true;
		$requestLoginCondition = (Variable::istype("RequestLoginCondition",$requestLoginCondition)) ? $requestLoginCondition : new RequestLoginConditionFile();
		$request = (Variable::istype("Request",$request)) ? $request : new RequestLogin();

		if($requestLoginCondition->condition($request)){
			$_SESSION[RequestLogin::getSessionLoginIdName()] = session_id();
			$requestLoginCondition->after($request);
			return true;
		}
		$requestLoginCondition->invalid($request);
		return false;
	}
	/**
	 * ログインは試みるがinvalid/afterは実行しない
	 * @param RequestLoginCondition $requestLoginCondition
	 * @return boolean
	 */
	function silent($requestLoginCondition){
		RequestUtil::usesession();
		if(RequestLogin::isLogin()) return true;
		$requestLoginCondition = (Variable::istype("RequestLoginCondition",$requestLoginCondition)) ? $requestLoginCondition : new RequestLoginConditionFile();
		$request = new RequestLogin();

		if($requestLoginCondition->condition($request)){
			$_SESSION[RequestLogin::getSessionLoginIdName()] = session_id();
			return true;
		}
		return false;
	}
	/**
	 * ログアウト
	 */
	function logout(){
		unset($_SESSION[RequestLogin::getSessionLoginIdName()]);
		unset($_SESSION[RequestLogin::getSessionLoginName()]);
		setcookie(RequestLogin::getSessionLoginName(),"",time() - 3600);
	}
	/**
	 * ログイン情報を保持するアプリケーションの名前を定義する
	 * @param string $name
	 */
	function setLoginSessionName($name){
		if(empty($name)){
			Rhaco::clearVariable("RHACO_CORE_REQUEST_LOGIN_SESSION_NAME");
		}else{
			Rhaco::setVariable("RHACO_CORE_REQUEST_LOGIN_SESSION_NAME",$name);
		}
	}
	/**
	 * ログインしているセッションIDを取得する
	 * @return string
	 */
	function getSessionLoginIdName(){
		return Rhaco::constant("REQUEST_SESSION_LOGIN_ID","SESSION_LOGIN_ID_".Rhaco::constant("APPLICATION_ID").
				(Rhaco::isVariable("RHACO_CORE_REQUEST_LOGIN_SESSION_NAME") ? "_".Rhaco::getVariable("RHACO_CORE_REQUEST_LOGIN_SESSION_NAME") : ""));
	}
	/**
	 * ログイン情報を保持するアプリケーションの名前を取得する
	 * @return string
	 */
	function getSessionLoginName(){
		return Rhaco::constant("REQUEST_SESSION_LOGIN","SESSION_LOGIN_".Rhaco::constant("APPLICATION_ID").
				(Rhaco::isVariable("RHACO_CORE_REQUEST_LOGIN_SESSION_NAME") ? "_".Rhaco::getVariable("RHACO_CORE_REQUEST_LOGIN_SESSION_NAME") : ""));
	}
	/**
	 * ログイン要求
	 * @param RequestLoginCondition $requestLoginCondition
	 * @param Request $request
	 */
	function loginRequired($requestLoginCondition=null,$request=null){
		RequestUtil::usesession();

		if(!RequestLogin::isLogin()){
			RequestLogin::login($requestLoginCondition,$request);
		}
	}
	/**
	 * ログイン済みか
	 * @return boolean
	 */
	function isLogin(){
		return isset($_SESSION[RequestLogin::getSessionLoginIdName()]);
	}
	/**
	 * ログインセッションが存在するか
	 * @return boolean
	 */
	function isLoginSession(){
		return isset($_SESSION[RequestLogin::getSessionLoginName()]);
	}
	/**
	 * ログインセッションを取得
	 * @return mixed
	 */
	function getLoginSession(){
		if(isset($_SESSION[RequestLogin::getSessionLoginName()])){
			return $_SESSION[RequestLogin::getSessionLoginName()];
		}
		return null;
	}
	/**
	 * ログインセッションをセットする
	 * @param mixed $value
	 */
	function setLoginSession($value){
		$_SESSION[RequestLogin::getSessionLoginName()] = $value;
	}
	/**
	 * クッキーにログイン情報があるか
	 * @return boolean
	 */
	function isLoginCookie(){
		return isset($_COOKIE[RequestLogin::getSessionLoginName()]);
	}
	/**
	 * クッキーからログイン情報を取得する
	 * @return string
	 */
	function getLoginCookie(){
		return isset($_COOKIE[RequestLogin::getSessionLoginName()]) ? $_COOKIE[RequestLogin::getSessionLoginName()] : null;
	}
	/**
	 * クッキーにログイン情報をセットする
	 * @param string $value
	 */
	function setLoginCookie($value){
		setcookie(RequestLogin::getSessionLoginName(),$value,time() + Rhaco::constant("COOKIE_EXPIRE_TIME",1209600));
	}
}
?>