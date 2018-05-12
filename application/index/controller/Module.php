<?php
namespace app\index\controller;

class Module
{
    public function index()
    {
    	/**
    	公共模块common
    	*/
        $list = [
			'__file__' => ['config.php', 'common.php'],
	        '__dir__'  => ['controller', 'model', 'view'],
	        'controller' => ['Base'],
	        'model'      => ['Base','Module', 'Controller','Action','Advertise','Article','ArticleComment','Category','Department','Forum','Friendlink','Message','Nav','News','NewsComment','Post','Role','RoleModule','RoleController','RoleAction','RoleNav','RoleUser','Rule','Setting','Sitemap','Type','User','Userinfo','Zone'],
	        'view'       => ['index/index'],
		];
		\MyBuild::module("common",$list);

		/**
    	后台模块admin
    	*/
		$list = [
			'__file__' => ['config.php', 'common.php'],
	        '__dir__'  => ['controller', 'model', 'view'],
	        'controller' => ['Index','Module', 'Controller','Action','Advertise','Article','ArticleComment','Category','Department','Forum','Friendlink','Message','Nav','News','NewsComment','Post','Role','RoleModule','RoleController','RoleAction','RoleNav','RoleUser','Rule','Setting','Sitemap','Type','User','Userinfo','Zone'],
	        'model'      => [],
	        'view'       => ['index/index', 'index/home', 'module/index', 'controller/index','action/index','advertise/index','article/index','articlecomment/index','category/index','department/index','forum/index','friendlink/index','message/index','nav/index','news/index','newscomment/index','post/index','role/index','rolemodule/index','rolecontroller/index','roleaction/index','rolenav/index','roleuser/index','rule/index','setting/index','sitemap/index','type/index','user/index','userinfo/index','zone/index'],
		];
		\MyBuild::module("admin",$list);

		/**
    	用户模块user
    	*/
		$list = [
			'__file__' => ['config.php', 'common.php'],
	        '__dir__'  => ['controller', 'model', 'view'],
	        'controller' => ['Index','User','Userinfo'],
	        'model'      => [],
	        'view'       => ['index/index','user/index','userinfo/index'],
		];
		\MyBuild::module("user",$list);


		/**
    	前台模块index
    	*/
		$list = [
			'__file__' => ['config.php', 'common.php'],
	        '__dir__'  => ['controller', 'model', 'view'],
	        'controller' => ['Index','Friendlink','Article','ArticleComment','News'],
	        'model'      => [],
	        'view'       => ['index/index','friendlink/index','article/view','news/index','articlecomment/view'],
		];
		\MyBuild::module("index",$list);

		/**
		 手机端模块index
		 */
		$list = [
		    '__file__' => ['config.php', 'common.php'],
		    '__dir__'  => ['controller', 'model', 'view'],
		    'controller' => ['Index','Friendlink','Article','ArticleComment','News'],
		    'model'      => [],
		    'view'       => ['index/index','friendlink/index','article/view','articlecomment/index','news/view'],
		];
		\MyBuild::module("wap",$list);
    }
}
