<?php
/**
 * Quick.php micro framework
 * @author Seyed Rahim Firouzi <seyed.rahim.firouzi@gmail.com>
 * @version 1.0
 * @license MIT
 * @copyright 2018 Seyed Rahim Firouzi
 */
/**
 * Request class
 */
class Request{
    /**
     * @var string request method
     */
    public $method='GET';
    /**
     * @var array request form value
     */
    public $POST=array();
    /**
     * @var array request query value
     */
    public $GET=array();
    /**
     * @var array request cookie value
     */
    public $COOKIE=array();
    /**
     * @var array $__FILE
     */
    public $FILE=array();
    /**
     * @var string remote ip
     */
    public $ip='';
    /**
     * @var string remote agent
     */
    public $agent='';
    /**
     * @var string is https
     */
    public $secure=false;
    /**
     * @var string request path
     */
    public $path='/';
    /**
     * constructor
     * @param string $rewrite_url_name
     */
    public function __construct($rewrite=''){
        $this->method=strtoupper($this->server('REQUEST_METHOD', 'GET'));
        if (get_magic_quotes_gpc ()) {
            $this->GET=$this->_clean_input_data($_GET);
            $this->POST=$this->_clean_input_data($_POST);
            $this->COOKIE=$this->_clean_input_data($_COOKIE);
        }else{
            $this->GET=$_GET;
            $this->POST=$_POST;
            $this->COOKIE=$_COOKIE;
        }
        
        $this->FILE=$_FILES;
        $this->ip=$this->server('REMOTE_ADDR', '0.0.0.0');
        $this->agent=$this->server( 'HTTP_USER_AGENT');
        $this->secure=($this->server( 'HTTPS','')=='on'?true:false);
        if($rewrite==''){
            $this->path=$this->server('REQUEST_URI','/');
        }else{
            $this->path=$this->get($rewrite,'/');
        }

    }
    private function _clean_input_data($str) {
        if (is_array ( $str )) {
            $new_array = array ();
            foreach ( $str as $key => $val ) {
                $new_array [$key] = $this->_clean_input_data ( $val );
            }
            return $new_array;
        }
        $str = stripslashes ( $str );
        return $str;
    }
    /**
     * return server parameter
     * @param string $key
     * @param string $def
     * @return string
     */
    public function server($key,$def=''){
        if(isset($_SERVER[$key]))
            return $_SERVER[$key];
        return $def;
    }
    /**
     * return query value
     * @param string $key name of query
     * @param string $def default value if not exist
     * @return string 
     */
    public function get($key,$def=''){
        
        if(isset($this->GET[$key]))
            return $this->GET[$key];
        return $def;
    }
    /**
     * return form value
     * @param string $key name of form
     * @param string $def default value if not exist
     * @return string 
     */
    public function post($key,$def=''){
        if(isset($this->POST[$key]))
            return $this->POST[$key];
        return $def;
    }
    /**
     * return object in form value decode json first
     * @param string $key
     * @param array $def
     * @return mixed
     */
    public function post_json($key,$def=array()){
        if(isset($this->POST[$key])){
            return json_decode($this->POST[$key],true);
        }
        return $def;
    }
    /**
     * return cookie value
     * @param string $key name of cookie
     * @param string $def default value if not exist
     * @return string 
     */
    public function cookie($key,$def=''){
        if(isset($this->COOKIE[$key]))
            return $this->COOKIE[$key];
        return $def;
    }
    /**
     * @var array route params
     */
    private $_params=[];
    /**
     * for set parameters by router engine
     * @params array associative array of parameter
     */
    public function set_parameters($params){
        $this->_params=$params;
    }
    /**
     * return route parameter
     * @param string $key name of parameter
     * @param string $def default value if not exist
     * @return string 
     */
    public function param($key,$def=''){
        if(isset($this->_params[$key]))
            return $this->_params[$key];
        return $def;
    } 
}
?>
