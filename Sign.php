<?php
set_time_limit(600);
require(__DIR__ . '/2captcha-php/src/autoloader.php');

// 2Captcha的api_key，用于过极验验证
$api_key = "";
// mcbbs的cookie
$cookie = "";
// server酱api_key
$key = "";


if(checkSignIn($cookie)){die();}
$chal = passGeeTest($api_key, $cookie);
if(!$chal){die();}
$res = signIn($cookie,"1","记上一笔，hold住我的快乐！",$chal);
if($res[0] = true){
    sc_send("签到成功", $res[1], $key);
}else{
    sc_send("签到失败", $res[1], $key);
}

function passGeeTest(string $api_key, string $cookie) : array|bool {
    $solver = new \TwoCaptcha\TwoCaptcha($api_key);
    // To bypass GeeTest first we need to get new challenge value
    
    // 使用了do while循环是因为2Captcha有时解不出来geetest验证码，
    // 返回ERROR_CAPTCHA_UNSOLVABLE（应该说10次有4次返回错误代码），
    // 直接一个循环解决
    do{
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.mcbbs.net/plugin.php?id=geetest3&amp;model=start&amp;t=1667578418195');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    
        $headers = array();
        $headers[] = 'Authority: www.mcbbs.net';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $headers[] = 'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6';
        $headers[] = 'Cache-Control: max-age=0';
        $headers[] = 'Cookie: '.$cookie;
        $headers[] = 'Sec-Ch-Ua: \"Microsoft Edge\";v=\"107\", \"Chromium\";v=\"107\", \"Not=A?Brand\";v=\"24\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Sec-Ch-Ua-Platform: \"Windows\"';
        $headers[] = 'Sec-Fetch-Dest: document';
        $headers[] = 'Sec-Fetch-Mode: navigate';
        $headers[] = 'Sec-Fetch-Site: none';
        $headers[] = 'Sec-Fetch-User: ?1';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36 Edg/107.0.1418.26';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $resp = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $data = json_decode($resp);
        $challenge = $data->challenge;
        // $challenge = explode(";", $resp)[0];
    
        // Then we are ready to make a call to 2captcha API
        $flag = 0;
        $error = null;
        
        try {
            $result = $solver->geetest([
                'gt'        => 'c4c41e397ee921e9862d259da2a031c4',
                'apiServer' => 'api.geetest.com',
                'challenge' => $challenge,
               'url'       => 'https://www.mcbbs.net/plugin.php?id=dc_signin',
            ]);
        } catch (\Exception $e) {
            print_r($e->getMessage());
            $error = $e->getMessage();
            $flag ++;
            sleep(10); // 有时错误代码来得太快，循环时会卡在获取challenge的时候
        }    
    }while($flag < 10 && $error == "ERROR_CAPTCHA_UNSOLVABLE");

    
    $data = json_decode($result->code,true);
    return $data;

}

function getFormHash(string $cookie) {
    $data = get("https://www.mcbbs.net/home.php?mod=spacecp&inajax=1",$cookie);
    preg_match("/<input type=\"hidden\" value=\"([a-z0-9]*?)\" name=\"formhash\" \/>/",$data,$match);
    if(!isset($match[1])){
        return false;
    }
    return $match[1];
}

function get(string $url,string $cookie,array $header = null){
    $c = curl_init();
    curl_setopt($c,CURLOPT_URL,$url);
    curl_setopt($c,CURLOPT_COOKIE,$cookie);
    curl_setopt($c, CURLOPT_HEADER, 0);
    curl_setopt($c,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($c,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($c,CURLOPT_HTTPHEADER, [
        "Referer: https://www.mcbbs.net/plugin.php?id=dc_signin",
        "Connection: closed",
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36"
    ]);
    if(!is_null($header)) {
        curl_setopt($c,CURLOPT_HTTPHEADER, $header);
    }
    $x = curl_exec($c);
    curl_close($c);
    return $x;
}
function post(string $url,string $cookie, array $data,array $header = null) {
    $c = curl_init();
    curl_setopt($c,CURLOPT_URL,$url);
    curl_setopt($c,CURLOPT_COOKIE,$cookie);
    curl_setopt($c, CURLOPT_HEADER, 0);
    curl_setopt($c,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($c,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($c, CURLOPT_POST, 1);
    curl_setopt($c,CURLOPT_HTTPHEADER, [
        "Referer: https://www.mcbbs.net/plugin.php?id=dc_signin",
        "Connection: closed",
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36"
    ]);
    if(!is_null($header)) {
        curl_setopt($c,CURLOPT_HTTPHEADER, $header);
    }
    curl_setopt($c, CURLOPT_POSTFIELDS, $data);
    $x = curl_exec($c);
    curl_close($c);
    return $x;
}

function checkSignIn (string $cookie) {
    $signUrl = "https://www.mcbbs.net/plugin.php?id=dc_signin:sign&inajax=1";
    $check = get($signUrl, $cookie);
    if(!$check || preg_match("/您今日已经签过到/",$check)) {
        return true;
    }
    return false;
}

function signIn(string $cookie, string $emote,string $content,array $chal) {
    $signUrl = "https://www.mcbbs.net/plugin.php?id=dc_signin:sign&inajax=1";
    $hash = getFormHash($cookie);
    if(!$hash){return false;}
    $result = post($signUrl, $cookie, [
        "formhash" => $hash,
        "signsubmit" => "yes",
        "handlekey" => "signin",
        "emotid" => $emote,
        "referer" => "https://www.mcbbs.net/plugin.php?id=dc_signin",
        "content" => $content,
        "geetest_challenge" => $chal['geetest_challenge'],
        "geetest_validate" => $chal['geetest_validate'],
        "geetest_seccode" => $chal['geetest_seccode']
    ]);
    if(!preg_match("/签到成功/",$result)){
        echo $result.PHP_EOL;
        $res1[] = false;
        $res1[] = $result;
        return $res1;
    }
    $res2[] = true;
    $res2[] = $result;
    return $res2;
}

function sc_send(  $text , $desp = '' , $key = '[SENDKEY]'  )
{
    $data = array( 
        'text' => $text, 
        'desp' => $desp 
    );
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://sctapi.ftqq.com/'.$key.'.send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $headers = array();
    $headers[] = 'Content-type: application/x-www-form-urlencoded';
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);   

    return $resp;
}
