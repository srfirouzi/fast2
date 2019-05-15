<?php 
class Router{
    /**
     * 
     * @var string rewrite parameter name in $_GET
     */
    private $_rewrite='';
    /**
     * 
     * @var callable(array) external function to set parameter of route 
     */
    private $_holder=null;
    /**
     * 
     * @var array parameter use for call router callback functions
     */
    private $_callback_parameters=[];
    /**
     * @param string $rewrite name of parameter in $_GET in rewrite url
     * @param callable(array) $holder external function to set parameter of route
     * @param array $params
     * @param callable $err404 callback function to call ,if request path don't match by patterns
     */
    private $_err404=null;
    function __construct($rewrite='',$holder=null,$params=[],$err404=null){
        $this->_rewrite;
        $this->_holder=$holder;
        $this->_callback_parameters=$params;
        $this->_err404=$err404;
    }
    /**
     * list of route roll
     * @var array
     */
    private $_routes=[];
    /**
     * add route for routing
     *
     * add new path patern to route incoming request.
     *
     * @param string $path path pattern fo route
     * @param callable $func callback function to call ,if request path match by pattern
     * @param string $method requet path sprate by |
     *
     * @example
     * $app->route('/path/:varable/!varable_maybe_not_exist/',function(...){},'POST');<br/>
     * $app->route('/path/*',function(...){});<br/>
     * $app->route('/*',function(...){});<br/>
     */
    public function route($path,$func,$method='GET|POST'){
        $pathParts=$this->_compilePath($path);
        $this->_routes[]=[
            'method'=>$method,
            'func'=>$func,
            'maxlen'=>$pathParts['maxlen'],
            'fixlen'=>$pathParts['fixlen'],
            'paths'=>$pathParts['paths'],
            'extera'=>$pathParts['extera']
        ];
    }
    /**
     * compile path
     *
     * this method compile path pattern to main parts
     *
     * @param string $path path patern
     * @throws Exception
     * @return array
     */
    private function _compilePath($path){
        $parts=explode('/',$path);
        $clean_parts=[];
        for($i=0;$i<count($parts);$i++){
            $part=trim($parts[$i]);
            if($part!=''){
                $clean_parts[]=$part;
            }
        }
        $maxlen=0;
        $fixlen=0;
        $isdynamic=false;
        $isextera=false;
        $used_path=[];
        
        for($i=0;$i<count($clean_parts);$i++){
            $part=$clean_parts[$i];
            if($part=='*'){
                $isextera=true;
                break;
            }
            if(substr($part, 0, 1)=='!'){
                $isdynamic=true;
            }else{
                if($isdynamic){
                    throw new Exception('after dynamic part,must use only dynamic part');
                }
                $fixlen++;
            }
            $used_path[]=$part;
            $maxlen++;
        }
        return [
            'maxlen'=>$maxlen,
            'fixlen'=>$fixlen,
            'paths'=>$used_path,
            'extera'=>$isextera
        ];
    }
    /**
     * run micro service
     * @return mixed callback function return,return
     */
    public function run($path=null){
        // sort route
        usort($this->_routes, function($a,$b){
            $lena=$a['fixlen'];
            $lenb=$b['fixlen'];
            
            $maxa=$a['maxlen'];
            $maxb=$b['maxlen'];
            
            $exa=$a['extera'];
            $exb=$b['extera'];
            
            if($exa!=$exb){
                if($exa)
                    return -1;
                else
                    return 1;
            }
            if ($lena==$lenb && $maxa==$maxb) {
                return 0;
            }
            if($lena>$lenb){
                return 1;
            }
            if($lena==$lenb && $maxa<$maxb){
                return 1;
            }
            return -1;
        });
        if(is_null($path)){
            $path='/';
            if($this->_rewrite==''){
                if(isset($_SERVER['REQUEST_URI'])){
                    $path=$_SERVER['REQUEST_URI'];
                }
            }else{
                if(isset($_GET[$this->_rewrite])){
                    $path=$_GET[$this->_rewrite];
                    if(get_magic_quotes_gpc ()){
                        $path=stripslashes($path);
                    }
                }
            }
        }
        $func=$this->_getRouteFunction($path);
        
        if(is_callable($func)){
            return call_user_func_array($func, $this->_callback_parameters);
        }
        if(is_callable($this->_err404)){
           return call_user_func_array($this->_err404, $this->_callback_parameters); 
        }
        return null;
    }
    /**
     * return matched route roll function
     * @param string $path reuest path
     * @return callback(QuickRequest,QuickResponse)
     */
    private function _getRouteFunction($path){
        $parts=explode('/',$path);
        $path_part=[];
        for($i=0;$i<count($parts);$i++){
            $part=trim($parts[$i]);
            if($part!=''){
                $path_part[]=$part;
            }
        }
        //-----------
        $len=count($path_part);
        $method='GET';
        if(isset($_SERVER['REQUEST_METHOD']))
            $method=$_SERVER['REQUEST_METHOD'];
        
        for($i=0;$i<count($this->_routes);$i++){
            $route=$this->_routes[$i];
            
            if(strpos($this->_routes[$i]['method'], $method) === false){
                continue;
            }
            if( $len>=$route['fixlen'] && ( $len<=$route['maxlen'] || $route['extera'] ) ){
                $parameter=[];
                $is_ok=true;
                for($j=0;$j<$len && $j<$route['maxlen'];$j++){
                    if(substr($route['paths'][$j], 0,1)=='!' || substr($route['paths'][$j], 0,1)==':'){
                        $parameter[substr($route['paths'][$j], 1)]=$path_part[$j];
                    }else{
                        if($route['paths'][$j]!=$path_part[$j]){
                            $is_ok=false;
                            break;
                        }
                    }
                }
                if($is_ok==false)
                    continue;
                if($route['extera']){
                    $starpart='';
                    for($j=$route['maxlen'];$j<$len;$j++){
                        $starpart=$starpart.'/'.$path_part[$j];
                    }
                    $parameter['*']=$starpart;
                }
                $this->_set_parameters($parameter);
                if(is_callable($this->_holder)){
                    call_user_func($this->_holder, $parameter);
                }
                return $route['func'];
            }
        }
        return null;
    }
    
    
    /**
     * @var array route params
     */
    private $_params=[];
    /**
     * for set parameters by router engine
     * @params array associative array of parameter
     */
    private function _set_parameters($params){
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