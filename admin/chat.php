<?php
if (!isset($_COOKIE['admin_token'])) {
    header("Location: index.php");
    exit();
}
$appid = $_GET['appid'] ?? '';
if (!$appid) die('缺少appid参数');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>QQ官机云平台 · 聊天记录</title>
  <style>
    :root{--bg1:#edf1ff;--bg2:#d8e0ff;--line:#d7defd;--glass:rgba(255,255,255,.46);--txt:#35395e;--pri:#8f9aff;--pri2:#aab5ff}
    *{box-sizing:border-box} html,body{height:100%;margin:0}
    body{font-family:"PingFang SC","Microsoft YaHei",sans-serif;background:radial-gradient(1000px 540px at -8% -8%, #fafbff 0%, transparent 60%),radial-gradient(1000px 640px at 110% 110%, #d0d8ff 0%, transparent 56%),linear-gradient(145deg,var(--bg1),var(--bg2));}
    .app{width:min(1320px,96vw);height:min(860px,96vh);margin:2vh auto;border-radius:24px;border:1px solid rgba(255,255,255,.8);background:linear-gradient(165deg,rgba(255,255,255,.44),rgba(243,247,255,.24));box-shadow:0 30px 80px rgba(76,88,184,.28);display:grid;grid-template-columns:240px 1fr;overflow:hidden;position:relative}
    .left{border-right:1px solid var(--line);padding:14px;background:#eef2ff;display:flex;flex-direction:column;gap:10px}
    .logo{font-size:26px;font-weight:900;color:#4d57a4}.chat-type{display:flex;border:1px solid rgba(255,255,255,.82);border-radius:12px;overflow:hidden;background:linear-gradient(160deg,rgba(255,255,255,.56),rgba(238,243,255,.38))}
    .chat-type button{flex:1;border:0;background:transparent;padding:8px 0;font-weight:700;color:#6971a2;cursor:pointer}.chat-type .on{background:rgba(255,255,255,.72);color:#4b56a8}
    .menu{display:grid;gap:6px;overflow:auto;min-height:0}.menu button{border:0;background:rgba(255,255,255,.45);cursor:pointer;text-align:left;padding:10px 12px;border-radius:12px;color:#555b83}
    .menu .item-row{display:flex;align-items:center;gap:8px}
    .menu .item-av{width:24px;height:24px;border-radius:50%;overflow:hidden;background:#e9eeff;flex:0 0 24px;display:grid;place-items:center;font-size:11px;color:#5b639d}
    .menu .item-av img{width:100%;height:100%;object-fit:cover;display:block}
    .menu .item-name{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .menu button.on{background:rgba(255,255,255,.86);color:#4e5bdd;font-weight:700}
    .meta{font-size:12px;color:#7b84b3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .center{position:relative;padding:12px}
    .chat-head{position:absolute;left:12px;right:12px;top:12px;height:40px;display:flex;align-items:center;gap:8px;padding:0 12px;border-radius:12px;border:1px solid rgba(255,255,255,.82);background:linear-gradient(160deg,rgba(255,255,255,.74),rgba(236,242,255,.56));color:#5a6298;font-weight:700}
    .messages{position:absolute;left:12px;right:12px;top:60px;bottom:84px;overflow:auto;display:grid;align-content:start;gap:8px;padding-bottom:8px}
    .msg{max-width:min(82%,560px);display:grid;grid-template-columns:28px 1fr;gap:8px;align-items:start;font-size:13px;line-height:1.45}
    .msg .av{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:700;overflow:hidden;background:#eef2ff;color:#5862a0}
    .av img{width:100%;height:100%;object-fit:cover;display:block}
    .msg .box{padding:8px 12px;border-radius:12px;box-shadow:0 6px 16px rgba(95,108,201,.12);max-width:100%;overflow:hidden}
    .msg.group .meta{font-size:11px;color:#6d75a7;margin-bottom:3px;height:16px}
    .msg .txt{white-space:pre-wrap;word-break:break-word;overflow-wrap:anywhere;line-height:1.5;max-width:100%;color:#3d4474}
    .msg.in{justify-self:start;color:#4d557e}.msg.in .av{background:#eef2ff;color:#5862a0}.msg.in .box{background:rgba(255,255,255,.88);border:1px solid rgba(255,255,255,.95)}
    .msg.out{justify-self:end;color:#2f376f;grid-template-columns:1fr 28px}.msg.out .av{order:2;background:linear-gradient(130deg,#8f9aff,#aab5ff);color:#fff}.msg.out .box{order:1;background:linear-gradient(130deg,#8f9aff,#aab5ff);border:1px solid rgba(255,255,255,.55);color:#1f2559}
    .msg.out .txt{color:#1f2559}
    .chat{position:absolute;left:12px;right:12px;bottom:12px;min-height:60px;padding:8px 10px 8px 14px;display:flex;gap:8px;align-items:flex-end;border-radius:16px;border:1px solid rgba(255,255,255,.9);background:rgba(255,255,255,.62)}
    .chat textarea{flex:1;border:0;outline:none;background:transparent;color:#4f557f;font-size:15px;resize:none;line-height:1.45;max-height:120px;overflow-y:auto;overflow-x:hidden;padding:8px 0;scrollbar-width:none;-ms-overflow-style:none}
    #chatInput::-webkit-scrollbar{width:0;height:0;display:none}
    .send{width:42px;height:42px;border-radius:12px;border:1px solid rgba(255,255,255,.5);cursor:pointer;color:#fff;background:linear-gradient(130deg,var(--pri),var(--pri2))}
    .more{width:42px;height:42px;border-radius:12px;border:1px solid rgba(255,255,255,.82);cursor:pointer;color:#6b74bd;font-weight:700;background:linear-gradient(160deg,rgba(255,255,255,.74),rgba(236,242,255,.56))}
    .quick{position:absolute;left:12px;right:12px;bottom:82px;padding:12px;border-radius:16px;border:1px solid rgba(255,255,255,.78);background:linear-gradient(160deg,rgba(255,255,255,.54),rgba(239,244,255,.36));box-shadow:0 18px 36px rgba(95,108,201,.22);z-index:6;opacity:0;pointer-events:none;transform:translateY(10px);transition:.2s}
    .quick.show{opacity:1;pointer-events:auto;transform:translateY(0)}
    .quick-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}
    .q-item{border:0;background:transparent;cursor:pointer;display:grid;justify-items:center;gap:5px;color:#6a719d;border-radius:12px;padding:4px 2px}
    .q-icon{width:52px;height:52px;border-radius:14px;background:linear-gradient(160deg,rgba(255,255,255,.78),rgba(237,242,255,.62));border:1px solid rgba(255,255,255,.9);display:grid;place-items:center;font-size:22px;color:#4e578f}
    .q-item span{font-size:11px;line-height:1.1;white-space:nowrap}
    .mobile-menu-btn{display:none;position:absolute;left:12px;top:12px;z-index:6;width:40px;height:40px;border-radius:12px;border:1px solid rgba(255,255,255,.82);background:linear-gradient(160deg,rgba(255,255,255,.74),rgba(236,242,255,.56));color:#5d66a4;font-size:20px;font-weight:800;cursor:pointer;transition:opacity .2s ease}
    .sidebar-mask{display:none;position:absolute;inset:0;background:rgba(31,39,88,.38);z-index:7;opacity:0;transition:opacity .2s ease}
    .app.sidebar-open .mobile-menu-btn{opacity:0;pointer-events:none}
    .sidebar-mask.show{display:block;opacity:1}
    @media (max-width:900px){
      .app{width:100vw;height:100dvh;margin:0;border-radius:0;grid-template-columns:1fr}
      .mobile-menu-btn{display:grid;place-items:center}
      .left{position:absolute;left:0;top:0;bottom:0;width:260px;z-index:8;transform:translateX(-105%);opacity:0;transition:transform .24s ease, opacity .24s ease;box-shadow:0 18px 40px rgba(68,82,180,.28);background:#eef2ff;border-right:1px solid #d7defd;backdrop-filter:none;-webkit-backdrop-filter:none}
      .left.show{transform:translateX(0);opacity:1}
      .sidebar-mask.show{display:block;opacity:1}
      .center{padding:12px}
      .chat-head{left:60px;right:12px}
      .messages{top:60px;bottom:84px}
      .quick-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:6px}
    }
  .main-content{height:calc(100vh - 60px);overflow-y:auto !important;overflow-x:hidden !important;}
</style>





</head>
<body>
<div class="app">
  <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
  <div class="sidebar-mask" id="sidebarMask"></div>
  <aside class="left" id="leftSidebar">
    <div class="logo">✦ QQ官机云平台 <span style="font-size:12px">聊天记录</span></div>
    <div class="chat-type">
      <button class="on" data-chat="private">私聊</button>
      <button data-chat="group">群聊</button>
    </div>
    <div class="menu" id="menu"></div>
  </aside>
  <main class="center">
    <div class="chat-head" id="chatHead">请选择会话</div>
    <div class="messages" id="messages"></div>
    <div class="quick" id="quickPanel">
      <div class="quick-grid">
        <button class="q-item" data-send-method="text"><div class="q-icon">📝</div><span>文字</span></button>
        <button class="q-item" data-send-method="native_md"><div class="q-icon">📄</div><span>原生MD</span></button>
        <button class="q-item" data-send-method="card"><div class="q-icon">🧩</div><span>卡片</span></button>
        <button class="q-item" data-quick="照片"><div class="q-icon">🖼️</div><span>照片</span></button>
        <button class="q-item" data-quick="文件"><div class="q-icon">📁</div><span>文件</span></button>
      </div>
    </div>
    <div class="chat">
      <textarea id="chatInput" rows="1" placeholder="输入消息..."></textarea>
      <button class="more" id="moreBtn">＋</button>
      <button class="send" id="sendBtn">➤</button>
    </div>
  </main>
</div>
<script>
const appid = <?php echo json_encode($appid, JSON_UNESCAPED_UNICODE); ?>;
const logName = new Date().toISOString().slice(0,10)+'.log';
let currentMode = 'private';
let listData = {private:[], group:[]};
let activeChatId = '';
let sendMethod = 'text';
let privateNickMap = {};
let userNameMap = {}; // userId -> nickname (from current loaded messages + private list)
let lastMessageFingerprint = '';
let liveTimer = null;

const $menu = document.getElementById('menu');
const $messages = document.getElementById('messages');
const $head = document.getElementById('chatHead');
const $input = document.getElementById('chatInput');
const quickPanel = document.getElementById('quickPanel');
const moreBtn = document.getElementById('moreBtn');


const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sidebarMask = document.getElementById('sidebarMask');
const leftSidebar = document.getElementById('leftSidebar');
function openSidebar(){ if(!leftSidebar) return; leftSidebar.classList.add('show'); sidebarMask.classList.add('show'); document.querySelector('.app')?.classList.add('sidebar-open'); }
function closeSidebar(){ if(!leftSidebar) return; leftSidebar.classList.remove('show'); sidebarMask.classList.remove('show'); document.querySelector('.app')?.classList.remove('sidebar-open'); }
if(mobileMenuBtn) mobileMenuBtn.onclick = openSidebar;
if(sidebarMask) sidebarMask.onclick = closeSidebar;
window.addEventListener('resize',()=>{ if(window.innerWidth>900) closeSidebar(); });

document.querySelectorAll('.chat-type button').forEach(btn=>btn.onclick=()=>{
  document.querySelectorAll('.chat-type button').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on');
  currentMode = btn.dataset.chat;
  activeChatId = '';
  lastMessageFingerprint = '';
  if(liveTimer) clearInterval(liveTimer);
  $messages.innerHTML='';
  $head.textContent = currentMode==='group' ? '群聊' : '私聊';
  renderMenu();
});

document.getElementById('sendBtn').onclick = sendMessage;
if(moreBtn){ moreBtn.onclick=(e)=>{e.stopPropagation(); quickPanel.classList.toggle('show');}; }
document.addEventListener('click',(e)=>{ if(!quickPanel||!moreBtn) return; if(quickPanel.contains(e.target)||moreBtn.contains(e.target)) return; quickPanel.classList.remove('show'); });
if(quickPanel){
  quickPanel.querySelectorAll('.q-item').forEach(b=>b.onclick=()=>{
    const method = b.dataset.sendMethod;
    if(method){
      sendMethod = method;
      const map = {text:'文字', native_md:'原生MD', card:'卡片'};
      const label = map[sendMethod] || '文字';
      $input.setAttribute('placeholder', `[${label}] 输入消息...`);
      if(moreBtn) moreBtn.textContent = label === '文字' ? '＋' : label[0];
    }
    quickPanel.classList.remove('show');
  });
}
$input.addEventListener('keydown',e=>{ if(e.key==='Enter' && (e.ctrlKey || e.metaKey)){ e.preventDefault(); sendMessage(); } });
$input.addEventListener('input', adjustInputHeight);
adjustInputHeight();


function adjustInputHeight(){
  if(!$input) return;
  $input.style.height = 'auto';
  const h = Math.min($input.scrollHeight, 120);
  $input.style.height = h + 'px';
  $input.style.overflowY = 'auto';
  const extra = Math.max(0, h - 24);
  $messages.style.bottom = (84 + extra) + 'px';
}

function shortText(t){ t=(t||'').replace(/\s+/g,' ').trim(); return t.length>28?t.slice(0,28)+'…':t; }
function avatarText(name,id){ return (name||id||'?').replace(/[👤👥\s（）()]/g,'').slice(0,1) || '?'; }


function getAvatarUrlByMode(mode,id){
  if(!id) return '';
  if(mode==='group') return `https://p.qlogo.cn/gh/${id}/${id}/0`;
  return `https://q.qlogo.cn/qqapp/${appid}/${id}/100`;
}

function avatarImgHtml(mode,id,name){
  const ch = avatarText(name,id);
  const url = getAvatarUrlByMode(mode,id);
  if(!url) return ch;
  return `<img src="${url}" alt="${ch}" onerror="this.remove()">`;
}

function renderTextWithMentions(text){
  const safe = (text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
  return safe.replace(/<@([^>]+)>/g, (_, uid) => {
    const name = userNameMap[uid] || uid;
    return `@${name}`;
  });
}



function escapeHtml(str){
  return (str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}


function renderNativeMdButtons(src){
  // 提取 [文本](mqqapi://...) 按钮
  const btnRegex = /\[([^\]]+?)\]\((mqqapi:\/\/[^\s)]+)\)/g;
  const buttons = [];
  let m;
  while((m = btnRegex.exec(src)) !== null){
    buttons.push({label:m[1], url:m[2]});
  }
  if(!buttons.length) return src;

  // 从正文移除按钮片段和分隔符
  let textPart = src.replace(btnRegex, '').replace(/\s*\|\s*/g, ' ').trim();

  const btnsHtml = buttons.map((b,i)=>{
    const active = i===0 ? 'background:rgba(47,109,255,.1);border-color:#2f6dff;color:#2f6dff;' : '';
    return `<a href="${b.url}" style="display:inline-block;padding:8px 14px;border-radius:12px;border:1px solid #c8ccd8;background:#fff;color:#2d335a;text-decoration:none;font-weight:700;${active}">${b.label}</a>`;
  }).join('');

  const panel = `<div style="margin-top:8px;padding:10px;border-radius:14px;background:rgba(255,255,255,.78);border:1px solid rgba(210,216,236,.9);display:flex;gap:10px;flex-wrap:wrap;">${btnsHtml}</div>`;
  return textPart ? `${textPart}<br>${panel}` : panel;
}

function renderMarkdownToHtml(text, messageType='text'){
  let src = renderTextWithMentions(text || '');
  src = escapeHtml(src);
  src = src.replace(/!\[([^\]]*?)\]\((https?:\/\/[^\s)]+)\)/g, '<img src="$2" alt="$1" style="max-width:100%;border-radius:10px;display:block;margin:4px 0;">');
  // 仅原生MD消息渲染框架按钮面板
  if (messageType === 'native_md') {
    src = renderNativeMdButtons(src);
  }
  src = src.replace(/\[([^\]]+?)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" style="color:#2f6dff;text-decoration:underline;">$1</a>');
  src = src.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  src = src.replace(/`([^`]+)`/g, '<code style="background:rgba(88,98,160,.12);padding:1px 6px;border-radius:6px;">$1</code>');
  src = src.replace(/```([\s\S]*?)```/g, (m, code) => `<pre style="background:rgba(33,40,83,.92);color:#e9edff;border-radius:10px;padding:10px;overflow:auto;margin:6px 0;"><code>${code}</code></pre>`);
  src = src.replace(/\n/g, '<br>');
  return src;
}

function safeRenderMessageHtml(text, messageType='text'){
  try {
    return renderMarkdownToHtml(text, messageType);
  } catch (e) {
    return escapeHtml(renderTextWithMentions(text || '')).replace(/\n/g, '<br>');
  }
}

async function loadPrivateNicknames(){
  const ids = (listData.private||[]).map(i=>i.id).filter(Boolean);
  if(!ids.length){ privateNickMap = {}; return; }
  const form = new URLSearchParams({
    type:'get_nicknames',
    appid,
    name:logName,
    user_ids: ids.join(',')
  });
  try{
    const res = await fetch('api/chat.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:form.toString()});
    const j = await res.json();
    if(j.code===200 && j.nicknames && typeof j.nicknames === 'object'){
      privateNickMap = j.nicknames;
      Object.entries(privateNickMap).forEach(([uid,n])=>{ if(n && String(n).trim()) userNameMap[uid]=String(n).trim(); });
    }
  }catch(_){ }
}

function getPrivateTitle(id){
  const nick = privateNickMap[id];
  if(nick && String(nick).trim()) return `👤 ${nick}`;
  return `👤 ${id.slice(0,12)}${id.length>12?'...':''}`;
}

async function loadList(){
  const u = `api/chat.php?type=list&appid=${encodeURIComponent(appid)}&name=${encodeURIComponent(logName)}`;
  const res = await fetch(u); const j = await res.json();
  if(j.code!==200){ $menu.innerHTML='<div style="color:#888">暂无数据</div>'; return; }
  listData.group = j.groups||[]; listData.private = j.privates||[];
  await loadPrivateNicknames();
  renderMenu();
}

function renderMenu(){
  const arr = currentMode==='group' ? listData.group : listData.private;
  if(!arr.length){ $menu.innerHTML='<div style="color:#888;padding:8px">暂无会话</div>'; return; }
  $menu.innerHTML = arr.map((it,idx)=>{
    const title = currentMode==='group' ? it.id : ((privateNickMap[it.id] && String(privateNickMap[it.id]).trim()) ? privateNickMap[it.id] : '未知用户');
    return `
    <button class="${activeChatId===it.id?'on':''}" data-id="${it.id}">
      <div class="item-row">
        <div class="item-av">${avatarImgHtml(currentMode,it.id,title)}</div>
        <div class="item-name">${title}</div>
      </div>
      <div class="meta">${shortText(it.last_message)} · ${it.last_message_time||''}</div>
    </button>`;
  }).join('');
  $menu.querySelectorAll('button').forEach((b,i)=>b.onclick=()=>{openChat(arr[i]); closeSidebar();});
}

function messageFingerprint(m){
  if(!m) return '';
  return [m.time||'', m.type||'', m.user_id||'', m.content||'', m.message_id||''].join('|');
}

async function refreshCurrentMessages(silent=true){
  if(!activeChatId) return;
  const u = `api/chat.php?type=messages&appid=${encodeURIComponent(appid)}&name=${encodeURIComponent(logName)}&chat_type=${currentMode}&chat_id=${encodeURIComponent(activeChatId)}`;
  const res = await fetch(u); const j = await res.json();
  if(j.code!==200 || !Array.isArray(j.messages)) return;
  const last = j.messages.length ? j.messages[j.messages.length-1] : null;
  const fp = messageFingerprint(last);
  if(!silent || fp !== lastMessageFingerprint){
    $messages.innerHTML='';
    j.messages.forEach(m=>{ if(m && m.user_id && m.username){ userNameMap[m.user_id]=m.username; } appendMsg(normalizeMessage(m)); });
    $messages.scrollTop = $messages.scrollHeight;
    lastMessageFingerprint = fp;
  }
}

function startLiveRefresh(){
  if(liveTimer) clearInterval(liveTimer);
  liveTimer = setInterval(()=>{ refreshCurrentMessages(true).catch(()=>{}); }, 3000);
}

async function openChat(item){
  activeChatId = item.id;
  renderMenu();
  $head.textContent = `${currentMode==='group'?'群聊':'私聊'} ID: ${item.id}`;
  await refreshCurrentMessages(false);
  startLiveRefresh();
}

function normalizeMessage(m){
  if(m.type==='bot') return {side:'out', name:'我', avatar:'我', text:m.content||'', userId:'', avatarUrl:'', messageType:(m.message_type||'text')};
  const name = m.username || m.user_id || '用户';
  const uid = (m.user_id||'');
  if(uid && name) userNameMap[uid] = name;
  return {side:'in', name, avatar:avatarText(name,uid), text:m.content||'', userId:uid, avatarUrl:getAvatarUrlByMode('private', uid), messageType:(m.message_type||'text')};
}

function appendMsg(m){
  const wrap = document.createElement('div');
  const isGroup = currentMode==='group';
  wrap.className = `msg ${m.side} ${isGroup?'group':''}`;
  if(isGroup){
    wrap.innerHTML = `<div class="av" data-user-id="${m.userId||''}">${m.side==='in' && m.avatarUrl ? `<img src="${m.avatarUrl}" alt="${m.avatar}" onerror="this.remove()">` : m.avatar}</div><div><div class="meta">${m.side==='in'?m.name:'我'}</div><div class="box"><div class="txt"></div></div></div>`;
  }else{
    wrap.innerHTML = `<div class="av" data-user-id="${m.userId||''}">${m.side==='in' && m.avatarUrl ? `<img src="${m.avatarUrl}" alt="${m.avatar}" onerror="this.remove()">` : m.avatar}</div><div class="box"><div class="txt"></div></div>`;
  }
  wrap.querySelector('.txt').innerHTML = safeRenderMessageHtml(m.text || '', m.messageType || 'text');
  const av = wrap.querySelector('.av');
  if(av && m.side==='in' && m.userId){
    av.style.cursor = 'pointer';
    av.title = `@${m.userId}`;
    av.addEventListener('click', (e)=>{
      e.stopPropagation();
      const mention = `<@${m.userId}> `;
      const v = $input.value || '';
      if(!v.includes(`<@${m.userId}>`)){
        $input.value = (v ? v + ' ' : '') + mention;
      }
      $input.focus();
      adjustInputHeight();
      try{ $input.setSelectionRange($input.value.length, $input.value.length); }catch(_){ }
    });
  }
  $messages.appendChild(wrap);
}

async function sendMessage(){
  const text = $input.value.trim();
  if(!activeChatId){ alert('请先选择会话'); return; }
  if(!text) return;
  const form = new URLSearchParams({
    type:'send', appid, chat_type: currentMode, chat_id: activeChatId, send_method: sendMethod, content:text, name:logName
  });
  const res = await fetch('api/chat.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:form.toString()});
  const j = await res.json();
  if(j.code===200){
    if(quickPanel) quickPanel.classList.remove('show');
    appendMsg({side:'out', avatar:'我', text, userId:'', avatarUrl:'', messageType: sendMethod});
    $messages.scrollTop = $messages.scrollHeight;
    $input.value='';
    adjustInputHeight();
    setTimeout(()=>refreshCurrentMessages(true).catch(()=>{}), 500);
  } else {
    alert(j.msg || '发送失败');
  }
}

loadList();
setInterval(loadList, 15000);
</script>
</body>
</html>
