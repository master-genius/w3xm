<?php

function auto_post_data($filter = [], $filter_type = false, $map = []) {

    $post_table = [];
    foreach ($_POST as $k => $v) {
        if (!empty($filter)) {
            /*
             * 过滤模式，如果filter_type为false，表示在filter数组中的字段不被读取
             * 否则，表示只有在filter中的字段才会被读取
             **/
            if ($filter_type === false) {
                if (array_search($k, $filter) !== false) {
                    continue;
                }
            } else {
                if (array_search($k, $filter) === false) {
                    continue;
                }
            }
        }

        if (!empty($map) && isset($map[$k])) {
            $post_table[$map[$k]] = trim($v);
        } else {
            $post_table[$k] = trim($v);
        }
    }
    
    return $post_table;
}

//用于获取提交的数据
//参数格式：[ 'a'=>['post','name',''] , 'b'=>['get','id',''], 'c'=>['session','uid',''] ]
//
function request_data_table($field_arr=[],$strap=true)
{
    $req_data = [];
    $data = '';
    foreach($field_arr as $d){
        if($d[0] == 'post'){
            $data = isset($_POST[$d[1]])?$_POST[$d[1]]:(isset($d[2])?$d[2]:null);
        }
        elseif($d[0] == 'get'){
            $data = isset($_GET[$d[1]])?$_GET[$d[1]]:(isset($d[2])?$d[2]:null);
        }
        elseif($d[0] == 'session'){
            $data = isset($_SESSION[$d[1]])?$_SESSION[$d[1]]:(isset($d[2])?$d[2]:null);
        }
        else{
            $data = null;
        }
        
        if($data === null){
            continue;
        }
        if($strap){
            $data=trim($data);
        }
        if(isset($d[3])){
            $req_data[$d[3]] = $data;
        }
        else{
            $req_data[$d[1]] = $data;
        }
    }
    return $req_data;
}

function request_data($val_type,$ind,$val_def=null,$strap=true)
{
    $data = null;
    switch($val_type){
        case 'post':
            $data = isset($_POST[$ind])?$_POST[$ind]:$val_def;
            break;
        case 'get':
            $data = isset($_GET[$ind])?$_GET[$ind]:$val_def;
            break;
        case 'session':
            $data = isset($_SESSION[$ind])?$_SESSION[$ind]:$val_def;
            break;
        default:;
    }

    return ($strap?trim($data):$data);
}

function get_data($ind, $def_val=null)
{
    return request_data('get',$ind,$def_val);
}

function post_data($ind, $def_val=null)
{
    return request_data('post', $ind, $def_val);
}

function json_exit($data)
{
    if(is_array($data)){
        exit(json_encode($data));
    }
    elseif (is_string($data)){
        exit($data);
    }
    else {
        exit('');
    }
}


function total_page($total,$pagesize)
{
    return (
            ($total%$pagesize)
            ?
                (( (int)($total/$pagesize) )+1)
            :
                ((int)($total/$pagesize))
           );
}

//mode: ymd ymdhm all
function format_time($t,$mode='all')
{
    $format = "%Y-%m-%d";
    switch ($mode) {
        case 'ymd':
            break;
        case 'ymdhm':
            $format .= " %H:%M";
            break;
        case 'all':
            $format .= " %H:%M:%S";
            break;
        default:;
    }

    return strftime($format, $t);
}

function number_test(&$n,$v=0)
{
  if(!is_numeric($n)){
    $n=$v;
  }
}

function api_ret($res, $data='success') {
    return $res->withHeader('Access-Control-Allow-Origin','*')
            ->withStatus(200)
            ->write(json_encode($data, JSON_UNESCAPED_UNICODE));
}

function res_ret($res, $data = '') {
    return $res->withHeader('Access-Control-Allow-Origin','*')
            ->withStatus(200)
            ->write($data);
}

