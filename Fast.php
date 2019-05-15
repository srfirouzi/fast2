<?php 
/**
 * fast micro framework
 * @author Seyed Rahim Firouzi <seyed.rahim.firouzi@gmail.com>
 * @version 1.0
 * @license MIT
 * @copyright 2019 Seyed Rahim Firouzi
 */
/**
 * @var string web root path
 */
define ('BASEPATH',dirname ( dirname ( __FILE__ ) ));
/**
 * load php file
 * @param string $path path to the file base of web root
 * @return boolean if file exist and load return true or return false
 */
function load_file($path){
    $filename=BASEPATH . $path;
    if(file_exists($filename)){
        include_once $filename;
        return true;
    }
    return false;
}
/**
 * fast framework core 
 *
 */
class Fast{
    /**
     * constructor
     * @param array $setting setting for app
     */
    function __construct($setting=[]){
        foreach ($setting as $key => $value){
            $this->_setting[$key]=$value;
        }
        $this->add_function('not_found', array($this,'_not_found'));
        $this->register('req', 'Request',[$this->setting('base.rewrite','_')],'/fast/libs/Request.php');
        $this->register('res', 'Response',[$this->setting('base.url','')],'/fast/libs/Response.php');
        $this->register('db', 'DataBase',[
            $this->setting('db.dsn'),
            $this->setting('db.user'),
            $this->setting('db.pass'),
            $this->setting('db.perfix')
        ],'/fast/libs/DataBase.php');
        $this->register('fs', 'FileSystem',[BASEPATH],'/fast/libs/FileSystem.php');
        $this->register('mvc', 'MVC',[
            BASEPATH.'/models',
            BASEPATH.'/views',
            BASEPATH.'/controllers',
            $this
        ],'/fast/libs/MVC.php',['model','view','controller']);
        
        if($this->setting('boof.active',false)){
            $this->register('boof', 'Boof',[
                BASEPATH.'/views',
                $this->setting('boof.debug',false)
            ],'/fast/libs/Boof.php',['view','render','add_view_function']);
        } 
        $this->register('router', 'Router',[
            $this->setting('base.rewrite','_'),
            [$this->req,'set_parameters'],
            [$this->req,$this->res,$this],
            [$this,'not_found']
        ],'/fast/libs/Router.php',['route','run']);
    }
    /**
     * @var array setting of application
     */
    private $_setting=[];
    /**
     * get application setting
     * @param string $name
     * @param mixed $def
     * @return mixed
     */
    public function setting($name,$def='') {
        if(isset($this->_setting[$name]))
            return $this->_setting[$name];
        return $def;
    }
    /**
     * set application seting
     * @param string $name
     * @param mixed $value
     */
    public function set_setting($name,$value=''){
        $this->_setting[$name]=$value;
    }
    /**
     * @var array list of magic method
     */
    private $_functions=[];
    /**
     * @var array list of magic method by peroperty method 
     */
    private $_functions_extera=[];
    /**
     * add new method to engine
     *
     * @param string $name method name
     * @param function(...) $func
     */
    public function add_function($name,$func){
        $this->_functions[$name]=$func;
    }
    /**
     * magic method to return defined method
     * @param string $name name of method
     * @param array   $params call parameters
     * @throws Exception
     * @return mixed
     */
    public function __call($name, $params) {
        if(isset($this->_functions[$name])){
            $func=$this->_functions[$name];
            if(is_callable($func)){
                return call_user_func_array($func, $params);
            }
        }
        if(isset($this->_functions_extera[$name])){
            $obj_name=$this->_functions_extera[$name];
            $obj=$this->$obj_name;
            $func=[$obj,$name];
            if(is_callable($func)){
                $this->_functions[$name]=$func;
                return call_user_func_array($func, $params);
            }
            
        }
        throw new Exception('method '.$name.' is not exist');
    }
    /**
     * @var array list of registered magic property
     */
    private $_properties=[];
    /**
     * @var array list of magic property
     */
    private $_objects=[];
    /**
     * add new property for application
     * @param string $name name of peroperty
     * @param string $class name of class for make peropert
     * @param array $params parameter to constructor of class
     * @param string $loadpath file path content class defined for loaded
     * @param array[string] $functions list of method name automatic add_functions to engine
     */
    public function register($name, $class,$params = [],$loadpath='',$functions=[]){
        $this->_properties[$name]=[
            'class'=>$class,
            'params'=>$params,
            'loadpath'=> $loadpath
        ];
        for($i=0;$i<count($functions);$i++){
            $fn=$functions[$i];
            $this->_functions_extera[$fn]=$name;
        }
        
    }
    /**
     * magic method to return model
     * @param string $name
     * @throws Exception
     * @return object
     */
    public function __get($name){
        if(isset($this->_objects[$name])){
            return $this->_objects[$name];
        }
        if(isset($this->_properties[$name])){
            $class=$this->_properties[$name];
            if($class['loadpath']!=''){
                load_file($class['loadpath']);
            }
            $reflection =new ReflectionClass($class['class']);
            $obj = $reflection->newInstanceArgs($class['params']);
            $this->_objects[$name]=$obj;
            return  $obj;echo "asdsa";
        }
        $out=$this->model($name);
        if(!is_null($out)){
            return $out;
        }
        throw new Exception($name.' is not exist');
    }
    /**
     * default 404 route function
     *
     * @param Request $req
     * @param Response $res
     */
    public function _not_found($req,$res) {
        $res->set_out_code(404);
        $res->write('Page not found!');
    }
}



?>