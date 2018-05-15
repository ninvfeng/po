<?php if(!$_POST){ ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <title>Paste OCR|截图文本识别</title>
    <script src="https://cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://cdn.bootcss.com/paste.js/0.0.21/paste.min.js"></script>
    <style>
        .main{
            width:810px;
            margin:50px auto;
        }
        .img{
            width:400px;
            height:400px;
            background:#e6e6e6;
            float:left;
        }
        img{
            max-width:100%;
            max-height:100%;
        }
        .text{
            width:400px;
            height:400px;
            background:#e6e6e6;
            float:left;
            margin-left:10px;
        }
    </style>
</head>
<body>
    <div class="main">
        <h1>识别图片文本小工具，请使用ctrl+v粘贴截图</h1>
        <div class="img"><img src=""></div>
        <div class="text" contenteditable></div>        
    </div>
    <script type="text/javascript">
        $(function () {
            $('*').pastableNonInputable();
            $('*').on('pasteImage', function (ev, data) {
                console.log(data)
                $("img").attr('src',data.dataURL)
                $.post('',{img:data.dataURL},res=>{
                    $(".text").html(res);
                })
            })
        });
    </script>
</body>
</html>
<?php } ?>
<?php
if($_POST){
    // 图片base64编码
    // $data   = file_get_contents('./test.png');
    // $base64 = base64_encode($data);
    $base64 = str_replace('data:image/png;base64,','',$_POST['img']);

    //获取配置文件
    $config=require 'config.php';

    //设置请求数据
    $appkey = $config['app_key'];
    $params = array(
        'app_id'     => $config['app_id'],
        'time_stamp' => strval(time()),
        'nonce_str'  => strval(rand()),
        'sign'       => '',
        'image'      => $base64,
    );
    $params['sign'] = sign($params, $appkey);

    //执行API调用
    $url = 'https://api.ai.qq.com/fcgi-bin/ocr/ocr_generalocr';
    $data = http($url, $params,"POST");

    //处理返回结果
    $data=json_decode($data,true)['data']['item_list'];
    $res='';
    foreach ($data as $k => $v) {
        $res.=$v['itemstring']."<br>";
    }
    echo $res;
}

//http请求
function http($url, $params = array(), $method = 'GET', $ssl = false){
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    );
    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            $getQuerys = !empty($params) ? '?'. http_build_query($params) : '';
            $opts[CURLOPT_URL] = $url . $getQuerys;
            break;
        case 'POST':
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
    }
    if ($ssl) {
        $opts[CURLOPT_SSLCERTTYPE] = 'PEM';
        $opts[CURLOPT_SSLCERT]     = $ssl['cert'];
        $opts[CURLOPT_SSLKEYTYPE]  = 'PEM';
        $opts[CURLOPT_SSLKEY]      = $ssl['key'];;
    }
    /* 初始化并执行curl请求 */
    $ch     = curl_init();
    curl_setopt_array($ch, $opts);
    $data   = curl_exec($ch);
    $err    = curl_errno($ch);
    $errmsg = curl_error($ch);
    curl_close($ch);
    if ($err > 0) {
        $this->error = $errmsg;
        return false;
    }else {
        return $data;
    }
}

//签名
function sign($params, $appkey){
    ksort($params);
    $str = '';
    foreach ($params as $key => $value)
    {
        if ($value !== '')
        {
            $str .= $key . '=' . urlencode($value) . '&';
        }
    }
    $str .= 'app_key=' . $appkey;
    $sign = strtoupper(md5($str));
    return $sign;
}