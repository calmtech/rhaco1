<?php
Rhaco::import("io.model.File");
Rhaco::import("lang.Variable");
Rhaco::import("lang.ArrayUtil");
Rhaco::import("lang.StringUtil");
Rhaco::import("lang.ObjectUtil");
Rhaco::import("tag.model.TemplateFormatter");
Rhaco::import("exception.ExceptionTrigger");
Rhaco::import("io.FileUtil");
/**
 * HTTP Request
 * $_FILES,$_SESSOIN,$_COOKIE,$_GET,$_POST とユーザが設定した変数を統一的に扱う
 * @author Kazutaka Tokushima
 * @license New BSD License
 * @copyright Copyright 2005- rhaco project. All rights reserved.
 */
class RequestUtil{
	var $variables	= array();
	var $session	= array();
	var $files		= array();
	var $urlmaps	= array();
	var $post		= false;
	var $args		= "";
	var $filters = array();
	/**
	 * コンストラクタ
	 * @param mixed $arg1
	 * @param mixed ...
	 * @return Request
	 */
	function RequestUtil(){
		$args = func_get_args();
		$this->__init__($args);
	}
	function __init__($args=null){
		$args = ArrayUtil::arrays($args);
		RequestUtil::usesession();
		$this->variables = array();
		$this->filters = array_merge($this->filters,ObjectUtil::loadObjects($args,array("reset","validate")));
		$this->reset();
		$this->setEnv($this->variables);
		if($this->isState()) $this->_loadState();
		$this->validate();
	}
	function _loadState(){
		$name = (isset($_POST["_rsid"])) ? $_POST["_rsid"] : (isset($_GET["_rsid"]) ? $_GET["_rsid"] : "");
		if(!empty($name) && $this->isSession($name)) $this->setVariable($this->getSession($name));
	}
	/**
	 * 現在の変数状態を保存する
	 */
	function saveState(){
		$name = uniqid("");
		$vars = $this->getVariable();
		if(array_key_exists("_rsid",$vars)) unset($vars["_rsid"]);
		$this->setSession($name,$vars);
		$this->setVariable("_rsid",$name);
	}
	/**
	 * 変数状態の回復が要求されているか
	 * @return boolean
	 */
	function isState(){
		return (array_key_exists("_rsid",$_POST) || array_key_exists("_rsid",$_GET));
	}
	/**
	 * 保存されている変数状態をクリアする
	 */
	function clearState(){
		$rsid = $this->getVariable('_rsid');
		if($rsid != ''){
			$this->clearSession($rsid);
		}
	}
	/**
	 * スーパーグローバル変数($_FILES,$_SESSOIN,$_COOKIE,$_GET,$_POST)を取り込み内部変数に格納する
	 * @param hash $variables 上書きする変数
	 */
	function setEnv($variables=array()){
		if(isset($_FILES) && is_array($_FILES)){
			foreach($_FILES as $key => $files) $this->files[$key] = $this->_parseFileElement($files);
		}
		if(isset($_SESSION) && is_array($_SESSION)){
			foreach($_SESSION as $key => $session) $this->session[$key] = StringUtil::getMagicQuotesOffValue($session);
		}
		if(isset($_COOKIE) && is_array($_COOKIE)){
			foreach($_COOKIE as $key => $cookie) $this->session[$key] = StringUtil::getMagicQuotesOffValue($cookie);
		}
		if(isset($_GET) && is_array($_GET)){
			foreach($_GET as $key => $get) $this->variables[$key] = StringUtil::getMagicQuotesOffValue($get);
		}
		if(isset($_POST) && is_array($_POST) && sizeof($_POST) > 0){
			$this->post = true;
			foreach($_POST as $key => $post) $this->variables[$key] = StringUtil::getMagicQuotesOffValue($post);
		}
		// with cgi.fix_pathinfo = 1
		$pathinfo = Rhaco::getVariable("RHACO_PATH_INFO");
		$pathinfo = (empty($pathinfo) && array_key_exists("PATH_INFO",$_SERVER)) ?
			((empty($_SERVER["PATH_INFO"]) && array_key_exists("ORIG_PATH_INFO",$_SERVER)) ? $_SERVER["ORIG_PATH_INFO"] : $_SERVER["PATH_INFO"]) : $pathinfo;
		if(empty($pathinfo) && isset($this->variables["pathinfo"])) $pathinfo = $this->variables["pathinfo"];
		if(!empty($pathinfo) && $pathinfo[0] != "/") $pathinfo = "/".$pathinfo;
		$this->args = preg_replace("/(.*?)\?.*/","\\1",$pathinfo);
		$this->setUrlMap();

		if(isset($variables) && is_array($variables)){
			foreach($variables as $key => $value) $this->variables[$key] = StringUtil::getMagicQuotesOffValue($value);
		}
	}
	/**
	 * スーパーグローバル変数取得前に実行される
	 */
	function reset(){
		ObjectUtil::calls($this->filters,"reset",$this);
	}
	/**
	 * スーパーグローバル変数取得前に実行される
	 */
	function validate(){
		ObjectUtil::calls($this->filters,"validate",$this);
	}
	/**
	 * 疑似例外が発行されていないか
	 * @return boolean
	 */
	function isValid(){
		return !ExceptionTrigger::invalid("request");
	}
	/**
	 * POSTかどうか
	 * @return boolean
	 */
	function isPost(){
		if($this->post || $_SERVER["REQUEST_METHOD"] == "POST"){
			return true;
		}
		return false;
	}
	/**
	 * POSTされたファイルを取得
	 * @param string $name
	 * @return File
	 */
	function getFile($name=""){
		if(empty($name)) return $this->files;
		if(!isset($this->files[$name])){
			$requestFiles			= array();
			$requestFiles['name']	= $name;
			$requestFiles['error']	= UPLOAD_ERR_NO_FILE;

			return $this->_parseFileElement($requestFiles);
		}
		return $this->files[$name];
	}
	/**
	 * POST されたファイルを文字列として取得
	 * @param $name
	 * @return string
	 */
	function readFile($name){
		if($this->isFile($name)){
			$file = $this->getFile($name);
			if(is_file($file->getTmp())){
				return FileUtil::read($file->getTmp());
			}
		}
		return null;
	}
	/**
	 * POSTされたファイルがあるか
	 * @param string $name
	 * @return boolean
	 */
	function isFile($name){
		return (isset($this->files[$name]) && Variable::istype("File",$this->files[$name]) && !$this->files[$name]->isError());
	}
	/**
	 * セッションに変数があるか
	 * @param string $name
	 * @return boolean
	 */
	function isSession($name){
		return array_key_exists($name,$this->session);
	}

	/**
	 * セッションから変数を取得
	 * @param string $name
	 * @param mixed $defaultData
	 * @return mixed
	 */
	function getSession($name="",$defaultData=null){
		if(empty($name)){
			return $this->session;
		}
		if(!array_key_exists($name,$this->session) || $this->session[$name] == null){
			return $defaultData;
		}
		return (isset($this->session[$name])) ? $this->session[$name] : null;
	}
	/**
	 * セッションに変数を保存する
	 * @param string/array $arg1
	 * @param mixed $arg2
	 */
	function setSession(){
		$argList = func_get_args();

		if(is_array($argList[0])){
			foreach($argList[0] as $key => $value){
				$this->session[$key]	= $value;
				$_SESSION[$key]			= $value;
			}
		}else if(sizeof($argList) == 2){
			$this->session[$argList[0]]	= $argList[1];
			$_SESSION[$argList[0]]		= $argList[1];
		}
	}
	/**
	 * セッションの値を削除する
	 * 引数を渡さない場合には全て削除する
	 * @param string $arg
	 * @param string ...
	 */
	function clearSession(){
		if(func_num_args() == 0){
			$this->session = array();
			foreach(ArrayUtil::arrays($_SESSION) as $key => $value){
				unset($_SESSION[$key]);
			}
		}else{
			foreach(func_get_args() as $name){
				unset($this->session[$name]);
				unset($_SESSION[$name]);
			}
		}
	}
	/**
	 * 変数があるか
	 * @param $name
	 * @return boolean
	 */
	function isVariable($name){
		return array_key_exists($name,$this->variables);
	}
	/**
	 * 変数を取得する
	 * @param string $name
	 * @param mixed $defaultData
	 * @return mixed
	 */
	function getVariable($name="",$defaultData=null){
		if(empty($name)) return $this->variables;
		return isset($this->variables[$name]) ? $this->variables[$name] : $defaultData;
	}
	/**
	 * 変数を保存する
	 * @param array/string $arrayOrKey
	 * @param mixed $value
	 */
	function setVariable($name,$value=null){
		if(!is_array($name)) $name = array($name=>$value);
		$this->variables = array_merge(ArrayUtil::arrays($this->variables),$name);
	}
	/**
	 * 変数を削除する
	 * 引数を渡さない場合には全て削除する
	 * @param string $arg
	 * @param string ...
	 */
	function clearVariable(){
		/*** unit("network.http.RequestTest"); */
		if(func_num_args() == 0){
			$this->variables = array();
		}else{
			foreach(func_get_args() as $arg){
				foreach(ArrayUtil::arrays($arg) as $name){
					if(array_key_exists($name,$this->variables)) unset($this->variables[$name]);
				}
			}
		}
	}
	/**
	 * url map をセットする
	 */
	function setUrlMap(){
		$this->urlmaps = $list = $hash = array();

		if(!empty($this->args)){
			foreach(explode("/",$this->args) as $value) if($value !== "") $list[] = $value;

			if(func_num_args() > 0){
				foreach(func_get_args() as $arg){
					foreach(ArrayUtil::arrays($arg) as $value) $hash[] = $value;
				}
			}else{
				$hash = range(0,sizeof($list));
			}
			foreach($list as $key => $value){
				$this->urlmaps[isset($hash[$key]) ? $hash[$key] : $key] = urldecode($value);
			}
		}
	}
	/**
	 * url map を取得する
	 * @return array/string
	 */
	function map(){
		if(func_num_args() == 0) return ArrayUtil::arrays($this->urlmaps);
		list($key) = func_get_args();
		if(isset($this->urlmaps[$key])) return $this->urlmaps[$key];
		return null;
	}
	function _parseFileElement($requestFiles){
		$fileElement		= new File($requestFiles['name']);
		$fileElement->tmp	= isset($requestFiles['tmp_name']) ? $requestFiles['tmp_name'] : "";
		$fileElement->size	= isset($requestFiles['size']) ? $requestFiles['size'] : "";
		$fileElement->error	= $requestFiles['error'];
		return $fileElement;
	}
	/**
	 * オブジェクトのプロパティに保持している変数をコピーする
	 * @param object $object
	 * @param boolean $isMethod true setter利用/false プロパティ直
	 * @return object
	 */
	function toObject($object,$isMethod=true){
		/*** unit("network.http.RequestTest"); */
		return ObjectUtil::hashConvObject($this->getVariable(),$object,$isMethod);
	}
	/**
	 * 保持している変数のGET文字列を返す
	 * @param unknown_type $list
	 * @return unknown
	 */
	function toQuery($list=array()){
		/***
		 * $list = array("hoge"=>123,"rhaco"=>"lib");
		 * $req = new RequestUtil();
		 * $req->clearVariable();
		 *
		 * $req->setVariable($list);
		 * eq("?hoge=123&rhaco=lib",$req->toQuery());
		 */
		$query = TemplateFormatter::httpBuildQuery($this->getVariable(),$list);
		if(!empty($query)) $query = "?".$query;
		return $query;
	}
	/**
	 * オブジェクトのプロパティーを変数に保存する
	 * @param object $object
	 * @param boolean $isMethod　メソッドも変数として保存するか
	 */
	function parseObject($object,$isMethod=false){
		/*** unit("network.http.RequestTest"); */
		foreach(ObjectUtil::objectConvHash($object,array(),$isMethod) as $key => $value){
			$this->setVariable($key,$value);
		}
	}
	/**
	 * 疑似例外を発行する
	 * @param ExceptionBase $exception
	 */
	function raise($exception){
		ExceptionTrigger::raise($exception,"request");
	}
	/**
	 * セッションを利用可能にする
	 * @static
	 * @param string $id
	 */
	function usesession($id=""){
		if(session_id() == "" && isset($_SERVER["HTTP_USER_AGENT"])){
			/** セッションに保存されたクラスオブジェクトの定義を先にincludeする必要があります。　*/
			/** SESSION_CACHE_LIMITERが nocache以外の値にセットされている場合にのみ SESSION_EXPIRE_TIMEが有効となります。 */
			/** (none/nocache/private/private_no_expire/public) */
			session_cache_limiter(Rhaco::constant("SESSION_CACHE_LIMITER","nocache"));
			session_cache_expire(Rhaco::constant("SESSION_EXPIRE_TIME",10800)/60);
			if(!empty($id))	session_id($id);
			session_start();
		}
	}
}
?>