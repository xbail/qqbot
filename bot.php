<?php

// ==================== 新增：日志记录函数 ====================
function 记录发送($action, $target, $content, $type = "文字") {
    wlog(json_encode([
        "direction" => "发送",
        "action" => $action,
        "source_type" => 消息来源,
        "target_id" => $target,
        "content_type" => $type,
        "content" => $content,
        "time" => date("Y-m-d H:i:s")
    ], JSON_UNESCAPED_UNICODE));
}

function BOT凭证(){
       $time=读("function/".appid,"time",0);
       if (time() < $time) {
         return 读("function/".appid,"Access",0);
       } else {
         $url="https://bots.qq.com/app/getAppAccessToken";
         $appid=appid;
         $secret=secret;
         $json=json_encode([
         "appId"=>"{$appid}",
         "clientSecret"=>$secret
         ]);
         $header=['Content-Type: application/json'];
         $fw=curl($url,"POST",$header,$json);
         写(1,1,$json);
         $fw=json_decode($fw,true);
         $Access=$fw["access_token"];
         $time=$fw["expires_in"];
         写("function/".appid,"time",time()+$time);
         写("function/".appid,"Access",$Access);
         return $Access;
      }
}

function BOTAPI($Address,$me,$json){
    $urls=[
    "正式"=>"https://api.sgroup.qq.com",
    "沙箱"=>"https://sandbox.api.sgroup.qq.com"
    ];
    $url = $urls[type].$Address;
    $header = ["Authorization: QQBot ".BOT凭证(), 'Content-Type: application/json'];
    $curl=curl($url,$me,$header,$json);
    return $curl;
}

function 文字($content) {
   记录发送("发送文字", 来源, $content, "文字");
   switch (消息来源) {
     case "群聊":
        $json = json_encode([
        "content" => "\n{$content}",
        "msg_type" => 0,
        "msg_id" => 消息ID,
        "msg_seq" => rand(1,99999)
         ]);
         return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
         break;
     case "私聊":
        $jsonData = [
        "content" => "{$content}",
        "msg_type" => 0,
        "msg_seq" => rand(1,99999)
         ];
         if (defined('事件ID')) $jsonData["event_id"] = 事件ID;
         else $jsonData["msg_id"] = 消息ID;
         return BOTAPI("/v2/users/".来源."/messages","POST",json_encode($jsonData));
         break;
     case "加群":
     case "退群":
     case "互动":
        $json = json_encode([
        "content" => "{$content}",
        "msg_type" => 0,
        "event_id" => 事件ID,
        "msg_seq" => rand(1,99999)
         ]);
         return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
         break;
     case "文字子频道":
         $json = json_encode([
         "content" => $content,
         "msg_id" => 消息ID
         ]);
         return BOTAPI("/channels/".来源."/messages","POST",$json);
         break;
   }
}


function 富媒体($type,$image,$name = null) {
    $types = ["图片" => 1, "视频" => 2, "语音" => 3 , "文件" => 4];
    $t = $types[$type] ?? 1;
    if (preg_match('/^http(s)?:\/\//i', $image)) {
        $jsonData = [
            "file_type" => $t,
            "url" => $image,
            "file_name" => $name,
            "srv_send_msg" => false
        ];
    } else {
        $jsonData = [
            "file_type" => $t,
            "file_data" => base64_encode($image),
            "file_name" => $name,
            "srv_send_msg" => false
        ];
    }
    $json = json_encode($jsonData);
        switch (消息来源) {
           case "加群":
           case "退群":
           case "群聊":
           case "互动":
               return json_decode(BOTAPI("/v2/groups/".来源."/files", "POST",$json),true);
               break;
           case "私聊":
               return json_decode(BOTAPI("/v2/users/".来源."/files", "POST",$json),true);
               break;
        }
}


function 图片($image,$content=null) {
   $logContent = $content ?? "[图片]";
   记录发送("发送图片", 来源, $logContent, "图片");
   switch (消息来源) {
     case "群聊":
        $file_info =富媒体("图片",$image);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
        "content" => $content !== null ? "\n{$content}" : "",
        "msg_type" => 7,
        "msg_id" => 消息ID,
        "msg_seq" => mt_rand(1, 9999),
        "media" => ["file_info" => $file]
        ]);
        return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
        break;
     case "私聊":
        $file_info =富媒体("图片",$image);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
        "content" => "{$content}",
        "msg_type" => 7,
        "msg_id" => 消息ID,
        "msg_seq" => mt_rand(1, 9999),
        "media" => ["file_info" => $file]
        ]);
        return BOTAPI("/v2/users/".来源."/messages","POST",$json);
        break;
     case "加群":
     case "退群":
     case "互动":
        $file_info =富媒体("图片",$image);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
        "content" => "{$content}",
        "msg_type" => 7,
        "event_id" => 事件ID,
        "msg_seq" => mt_rand(1, 9999),
        "media" => ["file_info" => $file]
        ]);
        return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
        break;
     case "文字子频道":
         $json = json_encode([
             "content" => $content,
             "file_image" => $image,
             "msg_id" => 消息ID
         ]);
         return BOTAPI("/channels/".来源."/messages","POST",$json);
         break;
   }
}


function silk($link){
    $link = str_replace("&","%26",$link);
    $url = "https://oiapi.net/API/Mp32Silk?url=".$link;
    $r = json_decode(curl($url,"GET",[],''), true);
    return $r["message"] ?? '';
}



function 语音($yy) {
   记录发送("发送语音", 来源, "[语音文件]", "语音");
   switch (消息来源) {
     case "群聊":
        $silk = silk($yy);
        $file_info = 富媒体("语音",$silk);
        if (isset($file_info['message'])) {
         return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
          "msg_type" => 7,
          "msg_id" => 消息ID,
          "msg_seq" => mt_rand(1, 9999),
          "media" => ["file_info" => $file]
         ]);
         return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
         break;
     case "私聊":
        $silk = silk($yy);
        $file_info = 富媒体("语音",$silk);
        if (isset($file_info['message'])) {
         return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
          "msg_type" => 7,
          "msg_id" => 消息ID,
          "msg_seq" => mt_rand(1, 9999),
          "media" => ["file_info" => $file]
         ]);
         return BOTAPI("/v2/users/".来源."/messages","POST",$json);
         break;
     case "加群":
     case "退群":
     case "互动":
        $silk = silk($yy);
        $file_info = 富媒体("语音",$silk);
        if (isset($file_info['message'])) {
         return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
          "msg_type" => 7,
          "event_id" => 事件ID,
          "msg_seq" => mt_rand(1, 9999),
          "media" => ["file_info" => $file]
         ]);
         return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
         break;
   }
}

function 文件($yy,$nm) {
   记录发送("发送文件", 来源, "[文件: {$nm}]", "文件");
   switch (消息来源) {
     case "群聊":
        $file_info = 富媒体("文件",$yy,$nm);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
          "msg_type" => 7,
          "msg_id" => 消息ID,
          "msg_seq" => mt_rand(1, 9999),
          "media" => ["file_info" => $file]
         ]);
         return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
         break;
     case "私聊":
        $file_info = 富媒体("文件",$yy,$nm);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
          "msg_type" => 7,
          "msg_id" => 消息ID,
          "msg_seq" => mt_rand(1, 9999),
          "media" => ["file_info" => $file]
         ]);
         return BOTAPI("/v2/users/".来源."/messages","POST",$json);
         break;
     case "加群":
     case "退群":
     case "互动":
        $file_info = 富媒体("文件",$yy,$nm);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
          "msg_type" => 7,
          "event_id" => 事件ID,
          "msg_seq" => mt_rand(1, 9999),
          "media" => ["file_info" => $file]
         ]);
         return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
         break;
   }
}



function 视频($video) {
   记录发送("发送视频", 来源, "[视频文件]", "视频");
   switch (消息来源) {
     case "群聊":
        $file_info =富媒体("视频",$video);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
        "msg_type" => 7,
        "msg_id" => 消息ID,
        "msg_seq" => mt_rand(1, 9999),
        "media" => ["file_info" => $file]
        ]);
        return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
        break;
     case "私聊":
        $file_info =富媒体("视频",$video);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
        "msg_type" => 7,
        "msg_id" => 消息ID,
        "msg_seq" => mt_rand(1, 9999),
        "media" => ["file_info" => $file]
        ]);
        return BOTAPI("/v2/users/".来源."/messages","POST",$json);
        break;
     case "加群":
     case "退群":
     case "互动":
        $file_info =富媒体("视频",$video);
        if (isset($file_info['message'])) {
          return 文字($file_info['message']);
        }
        $file = $file_info['file_info'];
        $json = json_encode([
        "msg_type" => 7,
        "event_id" => 事件ID,
        "msg_seq" => mt_rand(1, 9999),
        "media" => ["file_info" => $file]
        ]);
        return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
        break;
   }
}


function 按钮($key) {
   记录发送("发送按钮", 来源, "[按钮ID: {$key}]", "按钮");
   switch (消息来源) {
     case "群聊":
         $json = json_encode([
         "msg_type" => 2,
         "msg_id" => 消息ID,
         "msg_seq" => mt_rand(1, 9999),
         "keyboard" => [
           "id" => $key
           ]
         ]);
         return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
         break;
     case "私聊":
        $json = json_encode([
         "msg_type" => 2,
         "msg_id" => 消息ID,
         "msg_seq" => mt_rand(1, 9999),
         "keyboard" => [
           "id" => $key
           ]
         ]);
         return BOTAPI("/v2/users/".来源."/messages","POST",$json);
         break;
     case "加群":
     case "退群":
     case "互动":
        $json = json_encode([
         "msg_type" => 2,
         "event_id" => 事件ID,
         "msg_seq" => mt_rand(1, 9999),
         "keyboard" => [
           "id" => $key
           ]
         ]);
         return BOTAPI("/v2/groups/".来源."/messages","POST",$json);
         break;
   }
}

function 头像($id){
   return "https://q.qlogo.cn/qqapp/".appid."/{$id}/640";
}

function BOT信息(){
  return BOTAPI("/users/@me","GET",0);
}

function 文卡(...$items) {
    // 构建日志内容
    $itemTexts = [];
    foreach ($items as $item) {
        $itemTexts[] = $item['text'] ?? '[文本]';
    }
    记录发送("发送文卡", 来源, implode(" | ", $itemTexts), "文卡");
    
    $list_items = [];
    foreach ($items as $item) {
        if (isset($item['url'])) {
            $list_items[] = [
                "obj_kv" => [
                    ["key" => "desc", "value" => $item['text']],
                    ["key" => "link", "value" => $item['url']]
                ]
            ];
        } else {
            $list_items[] = [
                "obj_kv" => [
                    ["key" => "desc", "value" => $item['text']]
                ]
            ];
        }
    }
    $json = [
        "msg_type" => 3,
        "msg_seq" => mt_rand(1, 9999),
        "ark" => [
            "template_id" => 23,
            "kv" => [
                ["key" => "#DESC#", "value" => "愿为西南风,长逝入君怀"],
                ["key" => "#PROMPT#", "value" => "愿为西南风,长逝入君怀"],
                ["key" => "#LIST#", "obj" => $list_items]
            ]
        ]
    ];
    switch (消息来源) {
         case "群聊":
           if (defined('事件ID')) $json["event_id"] = 事件ID;
           else $json["msg_id"] = 消息ID;
           return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json));
         break;
         case "私聊":
           if (defined('事件ID')) $json["event_id"] = 事件ID;
           else $json["msg_id"] = 消息ID;
           return BOTAPI("/v2/users/".来源."/messages", "POST", json_encode($json));
         break;
         case "加群":
         case "退群":
         case "互动":
           $json["event_id"] = 事件ID;
           return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json));
         break;
    }
}

function 大图($title,$xtitle,$iurl){
    记录发送("发送大图卡片", 来源, "标题: {$title}", "大图卡片");
    $json = [
        "msg_type" => 3,
        "msg_seq" => mt_rand(1, 9999),
        "ark" => [
            "template_id" => 37,
            "kv" => [
                ["key" => "#METATITLE#", "value" => $title],
                ["key" => "#METASUBTITLE#", "value" => $xtitle],
                ["key" => "#PROMPT#", "value" => "愿为西南风,长逝入君怀"],
                ["key" => "#METACOVER#", "value" => $iurl]
            ]
        ]
    ];
    switch (消息来源) {
         case "群聊":
           if (defined('事件ID')) $json["event_id"] = 事件ID;
           else $json["msg_id"] = 消息ID;
           return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json));
         break;
         case "私聊":
           if (defined('事件ID')) $json["event_id"] = 事件ID;
           else $json["msg_id"] = 消息ID;
           return BOTAPI("/v2/users/".来源."/messages", "POST", json_encode($json));
         break;
         case "加群":
         case "退群":
         case "互动":
           $json["event_id"] = 事件ID;
           return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json));
         break;
    }
}

function 跳转卡($title,$desc,$image,$tz){
    记录发送("发送跳转卡片", 来源, "标题: {$title}, 链接: {$tz}", "跳转卡片");
    $json = [
        "msg_type" => 3,
        "msg_seq" => mt_rand(1, 9999),
        "ark" => [
            "template_id" => 24,
            "kv" => [
                ["key" => "#DESC#", "value" => "愿为西南风,长逝入君怀"],
                ["key" => "#PROMPT#", "value" => "愿为西南风,长逝入君怀"],
                ["key" => "#TITLE#", "value" => $title],
                ["key" => "#METADESC#", "value" => $desc],
                ["key" => "#IMG#", "value" => $image],
                ["key" => "#LINK#", "value" => $tz],
                ["key" => "#SUBTITLE#", "value" => "愿为西南风,长逝入君怀"]
            ]
        ]
    ];
    switch (消息来源) {
         case "群聊":
           if (defined('事件ID')) $json["event_id"] = 事件ID;
           else $json["msg_id"] = 消息ID;
           return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json));
         break;
         case "私聊":
           if (defined('事件ID')) $json["event_id"] = 事件ID;
           else $json["msg_id"] = 消息ID;
           return BOTAPI("/v2/users/".来源."/messages", "POST", json_encode($json));
         break;
         case "加群":
         case "退群":
         case "互动":
           $json["event_id"] = 事件ID;
           return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json));
         break;
    }
}

function 流式(...$msgs){
    $content_preview = implode(" ", array_slice($msgs, 0, 2));
    记录发送("流式回复", 来源, $content_preview . (count($msgs) > 2 ? " ..." : ""), "流式");
    
    $id = null;
    $index = 0;
    $total = count($msgs);
    foreach ($msgs as $msg) {
        $isLast = ($index === $total - 1);
        $json = [
            "content" => (string)$msg,
            "msg_id" => 消息ID,
            "msg_seq" => rand(1, 99999),
            "stream" => [
                "state" => $isLast ? 10 : 1,
                "id" => $id,
                "index" => $index,
                "reset" => false
            ]
        ];
        $curl = BOTAPI("/v2/users/".来源."/messages", "POST", json_encode($json));
        $json = json_decode($curl, true);
        $id = $json["id"];
        $index++;
    }
    return $curl;
}

function 撤回($id){
   记录发送("撤回消息", 来源, "消息ID: {$id}", "撤回");
   $type = [
      "群聊"=>"groups",
      "私聊"=>"users"
   ];
   $type = $type[消息来源];
   return BOTAPI("/v2/{$type}/".来源."/messages/".$id,"DELETE","");
}

function 原生MD($md, $keyboard = null) {
   记录发送("发送原生MD", 来源, $md, "原生MD");
   $json = [
       "msg_type" => 2,
       "msg_seq" => rand(1, 9999),
       "markdown" => [
           "content" => $md
       ]
   ];
   
   if ($keyboard !== null) {
       $json["keyboard"] = ["id" => $keyboard];
   }
   
   switch (消息来源) {
     case "群聊":
        if (defined('事件ID')) $json["event_id"] = 事件ID;
        else $json["msg_id"] = 消息ID;
        return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json));
        break;
     case "私聊":
        if (defined('事件ID')) $json["event_id"] = 事件ID;
        else $json["msg_id"] = 消息ID;
        return BOTAPI("/v2/users/".来源."/messages", "POST", json_encode($json));
        break;
     case "加群":
     case "退群":
     case "互动":
        $json["event_id"] = 事件ID;
        return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json));
        break;
   }
}

function 原生按钮($md, $rows) {
   记录发送("发送原生自定义按钮", 来源, $md, "原生按钮");

   $json = [
       "msg_type" => 2,
       "msg_seq" => rand(1, 9999),
       "markdown" => [
           "content" => $md
       ],
       "keyboard" => [
           "content" => [
               "rows" => $rows
           ]
       ]
   ];

   switch (消息来源) {
     case "群聊":
        $json["msg_id"] = 消息ID;
        return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json, JSON_UNESCAPED_UNICODE));
        break;
     case "私聊":
        $json["msg_id"] = 消息ID;
        return BOTAPI("/v2/users/".来源."/messages", "POST", json_encode($json, JSON_UNESCAPED_UNICODE));
        break;
     case "加群":
     case "退群":
     case "互动":
        $json["event_id"] = 事件ID;
        return BOTAPI("/v2/groups/".来源."/messages", "POST", json_encode($json, JSON_UNESCAPED_UNICODE));
        break;
   }
}

function 发MD($template_id, $params, $keyboard_id = null) {
    // 构建日志内容
    $logParams = [];
    if (isset($params['key']) && isset($params['values'])) {
        $logParams[] = $params['key'] . ":" . implode(",", $params['values']);
    } elseif (is_array($params)) {
        foreach ($params as $p) {
            if (isset($p['key'])) {
                $logParams[] = $p['key'];
            }
        }
    }
    记录发送("发送自定义MD", 来源, "模板: {$template_id} " . implode(" ", $logParams), "自定义MD");
    
    if (isset($params['key']) && isset($params['values'])) {
        $params = [$params];
    }
    
    $json_data = [
        "content" => "",
        "msg_type" => 2,
        "msg_seq" => mt_rand(1, 99999),
        "markdown" => [
            "custom_template_id" => $template_id,
            "params" => $params
        ]
    ];
    
    if (!empty($keyboard_id)) {
        $json_data["keyboard"] = ["id" => $keyboard_id];
    }
    
    if (in_array(消息来源, ["加群", "退群", "互动"])) {
        $json_data["event_id"] = 事件ID;
    } else {
        $json_data["msg_id"] = 消息ID;
    }
    
    switch (消息来源) {
        case "群聊":
        case "加群":
        case "退群":
        case "互动":
            $api_url = "/v2/groups/" . 来源 . "/messages";
            break;
        case "私聊":
            $api_url = "/v2/users/" . 来源 . "/messages";
            break;
        case "文字子频道":
            $api_url = "/channels/" . 来源 . "/messages";
            break;
        default:
            return "错误：消息来源不支持";
    }
    
    return BOTAPI($api_url, "POST", json_encode($json_data, JSON_UNESCAPED_UNICODE));
}