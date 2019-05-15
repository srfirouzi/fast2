<?php 
class Fast{
    
    
    public function not_found($req,$res){}
    /**
     * 
     * @var Request
     */
    public $req;
    /**
     * 
     * @var Response
     */
    public $res;
    /**
     * 
     * @var DataBase
     */
    public $db;
    /**
     * 
     * @var FileSystem
     */
    public $fs;
    /**
     * 
     * @var MVC
     */
    public $mvc;
    public function model($key) {}
    public function view($name,$data=[]){}
    public function controller($keu) {}  
    /**
     * 
     * @var Boof
     */
    public $boof;
    //public function view($name,$env=[]) {}
    public function render($src,$env=[]) {}
    public function add_view_function($name, $func) {}
    /**
     * 
     * @var Router
     */
    public $router;
    public function route($path,$func,$method='GET|POST'){}
    public function run($path=null){}
    
    
    
    
}






?>