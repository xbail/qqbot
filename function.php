<?php
include(__DIR__."/function/qrcode.php");
include(__DIR__."/function/GD.php");
include(__DIR__."/function/Parsedown.php");
include(__DIR__."/function/Mail/class.smtp.php");
include(__DIR__."/function/tuwen.php");

function sign($payload,$seed){
while (strlen($seed) < SODIUM_CRYPTO_SIGN_SEEDBYTES) {
    $seed .= $seed;
}
$privateKey = sodium_crypto_sign_secretkey(
    sodium_crypto_sign_seed_keypair(substr($seed, 0, SODIUM_CRYPTO_SIGN_SEEDBYTES))
);
$signature = bin2hex(
    sodium_crypto_sign_detached(
        $payload['d']['event_ts'] . $payload['d']['plain_token'], 
        $privateKey
    )
);
echo json_encode([
    'plain_token' => $payload['d']['plain_token'],
    'signature' => $signature
]);
}


function 写($文件, $键, $值) {
    $文件路径 = "database/" . $文件;
    $目录 = dirname($文件路径);
    if (!is_dir($目录)) {
        if (!mkdir($目录, 0777, true)) {
            error_log("无法创建目录: {$目录}");
            return false;
        }
    }
    $fp = fopen($文件路径, "c+");
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    try {
        $内容 = filesize($文件路径) > 0 ? fread($fp, filesize($文件路径)) : '{}';
        $数据 = json_decode($内容, true) ?: [];
        $数据[$键] = $值;
        $json = json_encode($数据, JSON_UNESCAPED_UNICODE);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $json);
        return true;
    } catch (Exception $e) {
        error_log("写入文件出错: {$文件路径}, 错误: " . $e->getMessage());
        return false;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function 读($文件, $键, $默认值 = null) {
    $文件路径 = "database/" . $文件;
    if (!file_exists($文件路径)) {
        return $默认值;
    }
    $fp = fopen($文件路径, "r");
    if (!flock($fp, LOCK_SH)) {
        fclose($fp);
        return $默认值;
    }
    try {
        $内容 = fread($fp, filesize($文件路径));
        $数据 = json_decode($内容, true);
        return $数据[$键] ?? $默认值;
    } catch (Exception $e) {
        return $默认值;
    } finally {
        flock($fp, LOCK_UN); 
        fclose($fp);
    }
}


function wlog($content) {
    $date = date('Y-m-d H:i:s');
    $logDir = "Log/".appid;
    $logFile = $logDir . '/' . date('Y-m-d') . '.log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logContent = "[{$date}] {$content}" . PHP_EOL;
    file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
}


function curl($url, $method, $headers, $params){
    $url = str_replace(" ", "%20", $url);
    if (is_array($params)) {
        $requestString = http_build_query($params);
    } else {
        $requestString = $params ? : '';
    }
    if (empty($headers)) {
        $headers = array('Content-type: text/json'); 
    } elseif (!is_array($headers)) {
        parse_str($headers,$headers);
    }
    // setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // 修复原函数BUG：默认设为POST会覆盖GET/PUT/DELETE，移到switch后
    switch ($method){  
        case "GET" : curl_setopt($ch, CURLOPT_HTTPGET, 1);break;  
        case "POST": curl_setopt($ch, CURLOPT_POST, 1);
                     curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;  
        case "PUT" : curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");   
                     curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;  
        case "DELETE":  curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");   
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);break;  
    }
    // getting response from server
    $response = curl_exec($ch);
    
    //close the connection
    curl_close($ch);
    
    //return the response
    if (stristr($response, 'HTTP 404') || $response == '') {
        return array('Error' => '请求错误');
    }
    return $response;
}

function 二维码($content){
ob_start();
Toplib_Lib_QRcode::png($content, false, QR_ECLEVEL_L, 7, 1, false, [255,255,255], [0,0,0]);
return ob_get_clean();
}

function 前缀后($str,$prefix) {
    if (strpos($str,$prefix) !== false) {
        return substr($str, strlen($prefix));
    } else {
        return $str;
    }
}
function 前缀($str,$prefix) {
    if (strpos($str,$prefix) === 0) {     
        return true;
    } else {
       
        return false;
    }
}

function 域名大写($msg) {
    $suffixes = array(
        'com', 'net', 'org', 'edu', 'gov', 'mil', 'biz', 'info', 'top',
        'xyz', 'vip', 'pro', 'name', 'tech', 'site', 'club', 'online',
        'store', 'shop', 'blog', 'app', 'cn', 'cc', 'tv', 'io', 'ai'
    );
      foreach ($suffixes as $suffix) {
        $pattern = '/([\.\/])(' . $suffix . ')\b/i';
        $msg = preg_replace_callback($pattern, function($matches) {
            return $matches[1] . ucfirst(strtolower($matches[2]));
        }, $msg);
    }
    return $msg;
}

function markdown转html($markdown){
   $parsedown = new Parsedown();
   $html = $parsedown->text($markdown);
   return $html;
}

function 邮箱($mailTitle,$content,$Adress,$user,$password){
$mail = new PHPMailer();
$mail->SMTPDebug = 1;
$mail->isSMTP();
$mail->SMTPAuth = true;
$mail->Host = 'smtp.qq.com';
$mail->SMTPSecure = 'ssl';
$mail->Port = 465;
$mail->CharSet = 'UTF-8';
$mail->Username = $user;
$mail->Password = $password;
$mail->From = $user;
$mail->FromName = 'Cucko';
$mail->isHTML(true);
$mail->addAddress($Adress);
$mail->Subject = $mailTitle;
$mail->Body = $content;
return $mail->send();
}


function HTML转图($html, $selector = ".container", $noCache = false, $quality = 100, $scale = 1.5, $format = "png") {
    $url = "http://nw.qzqi.com:45000/screenshot";
    // 仅保留接口必填/可选参数，剔除原无效的long（高）、width（宽）
    $params = [
        'text' => $html,
        'selector' => $selector,
        'noCache' => $noCache ? 'true' : 'false',
        'quality' => $quality,
        'scale' => $scale,
        'format' => $format
    ];
    $header = [
        'Accept: image/' . $format,
        'Content-Type: application/json'
    ];
    $requestBody = json_encode($params, JSON_UNESCAPED_UNICODE);
    $response = curl($url, "POST", $header, $requestBody);
    return $response;
}