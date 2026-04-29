@extends('layouts.app')
@section('title', 'Chat')
@section('page-title', 'Messagerie instantanée')
@section('page-subtitle', 'Échangez en temps réel avec vos collègues')
@section('content')

@verbatim
<style>
#chat-wrap { display:flex; height:calc(100vh - 185px); min-height:520px; background:#fff; border-radius:1.5rem; border:1px solid #e5e7eb; box-shadow:0 1px 8px rgba(0,0,0,.06); overflow:hidden; }

/* Panneau gauche */
#conv-panel { width:290px; flex-shrink:0; border-right:1px solid #f1f2f5; display:flex; flex-direction:column; background:#fafafa; }
#conv-search { border:0; border-bottom:1px solid #f1f2f5; padding:.65rem 1rem; font-size:.85rem; outline:none; background:#fff; width:100%; }
#conv-search:focus { border-color:#2453d6; }
#conv-list { flex:1; overflow-y:auto; }
.conv-item { display:flex; align-items:center; gap:.75rem; padding:.7rem 1rem; cursor:pointer; transition:background .12s; border-bottom:1px solid #f5f5f7; }
.conv-item:hover { background:#f1f5ff; }
.conv-item.active { background:#eef2ff; border-left:3px solid #2453d6; }
.c-av { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.83rem; flex-shrink:0; color:#fff; }
.conv-name { font-weight:600; font-size:.84rem; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-preview { font-size:.72rem; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Panneau droit */
#msg-panel { flex:1; display:flex; flex-direction:column; min-width:0; }
#msg-header { padding:.9rem 1.25rem; border-bottom:1px solid #f1f2f5; display:flex; align-items:center; gap:.75rem; background:#fff; flex-shrink:0; }
#msg-body { flex:1; overflow-y:auto; padding:1.1rem 1.25rem; display:flex; flex-direction:column; gap:.45rem; }

/* Bulles */
.msg-row { display:flex; align-items:flex-end; gap:.45rem; }
.msg-row.mine { flex-direction:row-reverse; }
.msg-bub { max-width:64%; padding:.5rem .85rem; border-radius:1.1rem; font-size:.875rem; line-height:1.45; word-break:break-word; }
.msg-bub.them { background:#f1f5f9; color:#1e293b; border-bottom-left-radius:3px; }
.msg-bub.mine { background:#2453d6; color:#fff; border-bottom-right-radius:3px; }
.msg-time { font-size:.67rem; color:#b0bac9; padding-bottom:2px; flex-shrink:0; }
.msg-av-sm { width:27px; height:27px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.68rem; flex-shrink:0; color:#fff; }
.msg-name-sm { font-size:.67rem; color:#94a3b8; margin-bottom:2px; }

/* Input */
#msg-footer { padding:.7rem 1rem; border-top:1px solid #f1f2f5; background:#fff; display:flex; align-items:flex-end; gap:.65rem; flex-shrink:0; }
#msg-input { flex:1; border:1px solid #e2e8f0; border-radius:1.4rem; padding:.5rem 1rem; font-size:.875rem; outline:none; resize:none; max-height:120px; line-height:1.45; transition:border-color .15s; font-family:inherit; }
#msg-input:focus { border-color:#2453d6; }
#msg-send { width:38px; height:38px; background:#2453d6; color:#fff; border-radius:50%; border:0; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:background .15s; }
#msg-send:hover { background:#1a3fb0; }
#msg-send:disabled { background:#c7d2fe; cursor:not-allowed; }

#no-conv-msg { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#d1d5db; gap:.75rem; text-align:center; }

.tab-btn { flex:1; padding:.6rem 0; font-size:.75rem; font-weight:700; border:0; background:transparent; cursor:pointer; border-bottom:2px solid transparent; transition:all .15s; }
.tab-btn.on { color:#2453d6; border-bottom-color:#2453d6; }
.tab-btn.off { color:#94a3b8; }

/* Colonne connectes */
#online-panel { width:300px; flex-shrink:0; border-left:1px solid #f1f2f5; background:#fbfcff; display:flex; flex-direction:column; }
#online-header { padding:.85rem 1rem; border-bottom:1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; }
#online-body { flex:1; overflow-y:auto; padding:.55rem .75rem; }
.admin-group { border:1px solid #e6ebf3; background:#fff; border-radius:.9rem; overflow:hidden; margin-bottom:.65rem; }
.admin-group-hd { display:flex; align-items:center; justify-content:space-between; padding:.55rem .7rem; background:#f8fafc; border-bottom:1px solid #edf2f7; }
.admin-group-name { font-size:.75rem; font-weight:700; color:#0f172a; }
.admin-group-count { font-size:.67rem; color:#64748b; background:#e2e8f0; border-radius:9999px; padding:.1rem .45rem; }
.online-user { display:flex; align-items:center; gap:.55rem; padding:.5rem .65rem; border-bottom:1px solid #f5f7fb; }
.online-user:last-child { border-bottom:0; }
.online-meta { min-width:0; }
.online-name { font-size:.78rem; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.online-role { font-size:.67rem; color:#94a3b8; }
.online-dot { width:7px; height:7px; border-radius:9999px; background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.14); flex-shrink:0; }

@media(max-width:1024px){ #online-panel{display:none;} }

@media(max-width:640px){ #conv-panel{width:100%;} #msg-panel{display:none;} #chat-wrap.sm-msg #conv-panel{display:none;} #chat-wrap.sm-msg #msg-panel{display:flex;} }
</style>
@endverbatim

<div id="chat-wrap">

    {{-- ══ GAUCHE : conversations ══ --}}
    <div id="conv-panel">
        <input id="conv-search" type="text" placeholder="Rechercher…">
        <div class="flex border-b border-gray-100 bg-white">
            <button class="tab-btn on" id="tab-group"  onclick="setTab('group')"><i class="fas fa-hashtag mr-1 text-xs"></i>Salons</button>
            <button class="tab-btn off" id="tab-direct" onclick="setTab('direct')"><i class="fas fa-user mr-1 text-xs"></i>Directs</button>
        </div>
        <div id="conv-list">
            <div id="list-group"></div>
            <div id="list-direct" class="hidden"></div>
            <div id="list-empty" class="hidden px-4 py-8 text-center text-sm text-gray-400">Aucun contact.</div>
            <div id="list-load"  class="hidden px-4 py-8 text-center text-sm text-gray-400"><i class="fas fa-circle-notch fa-spin mr-1"></i>Chargement…</div>
        </div>
    </div>

    {{-- ══ DROITE : messages ══ --}}
    <div id="msg-panel" class="hidden" style="flex-direction:column;">
        <div id="msg-header">
            <button class="sm:hidden text-gray-400 mr-1" onclick="document.getElementById('chat-wrap').classList.remove('sm-msg')">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div id="hdr-av" class="c-av bg-blue-500"><i class="fas fa-hashtag text-sm"></i></div>
            <div class="flex-1 min-w-0">
                <div id="hdr-name" class="font-bold text-gray-800 text-sm">Général</div>
                <div id="hdr-sub"  class="text-xs text-gray-400">Salon public</div>
            </div>
        </div>

        <div id="msg-body">
            <div id="no-conv-msg">
                <i class="fas fa-comments text-4xl"></i>
                <p class="text-sm">Sélectionnez une conversation</p>
            </div>
        </div>

        <div id="msg-footer">
            <textarea id="msg-input" rows="1" placeholder="Écrire un message…" disabled
                      onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
            <button id="msg-send" disabled onclick="sendMessage()">
                <i class="fas fa-paper-plane text-sm"></i>
            </button>
        </div>
    </div>

    {{-- ══ CONNECTES PAR ADMINISTRATION ══ --}}
    <div id="online-panel">
        <div id="online-header">
            <div class="text-xs font-semibold text-slate-700">Utilisateurs en ligne</div>
            <div id="online-total" class="text-[11px] text-slate-500">0</div>
        </div>
        <div id="online-body">
            <div id="online-loading" class="px-2 py-4 text-xs text-slate-400"><i class="fas fa-circle-notch fa-spin mr-1"></i>Chargement...</div>
            <div id="online-empty" class="hidden px-2 py-4 text-xs text-slate-400">Aucun utilisateur connecte.</div>
            <div id="online-groups"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const ME   = "{{ auth()->id() }}";
const CSRF = document.querySelector('meta[name=csrf-token]').content;

let curRoom = null, curName = '', curColor = 'bg-blue-500', curType = 'group';
let curTab  = 'group';
let lastTs  = null;
let timer   = null;
let users   = {};
let seenMsgIds = new Set();
let fetching = false;
let hbTimer = null;
let contactsTimer = null;

// Salons prédéfinis
const SALONS = [
    { room:'general',    icon:'fa-hashtag',    name:'Général',    color:'bg-blue-500' },
    { room:'annonces',   icon:'fa-bullhorn',   name:'Annonces',   color:'bg-orange-500' },
    { room:'documents',  icon:'fa-file-alt',   name:'Documents',  color:'bg-emerald-500' },
    { room:'signatures', icon:'fa-pen-nib',    name:'Signatures', color:'bg-purple-500' },
    { room:'workflows',  icon:'fa-code-branch',name:'Workflows',  color:'bg-indigo-500' },
];

const COLORS = ['bg-violet-500','bg-pink-500','bg-teal-500','bg-orange-500','bg-sky-500','bg-rose-500'];

// ── Init salons ──────────────────────────────────────────────
(function initSalons(){
    const g = document.getElementById('list-group');
    SALONS.forEach(s => {
        const d = document.createElement('div');
        d.className = 'conv-item';
        d.dataset.room = s.room;
        d.dataset.name = s.name;
        d.dataset.color = s.color;
        d.innerHTML = `<div class="c-av ${s.color}"><i class="fas ${s.icon} text-sm"></i></div>
            <div style="flex:1;min-width:0"><div class="conv-name">${s.name}</div><div class="conv-preview">Salon public</div></div>`;
        d.onclick = () => openConv(s.room, s.name, s.color, 'group');
        g.appendChild(d);
    });
    openConv('general','Général','bg-blue-500','group');
})();

// ── Onglets ──────────────────────────────────────────────────
function setTab(tab) {
    curTab = tab;
    ['group','direct'].forEach(t => {
        document.getElementById('tab-'+t).className = 'tab-btn ' + (t===tab?'on':'off');
        document.getElementById('list-'+t).classList.toggle('hidden', t!==tab);
    });
    if (tab === 'direct') loadUsers();
}

// ── Charger contacts ─────────────────────────────────────────
async function loadUsers() {
    document.getElementById('list-load').classList.remove('hidden');
    document.getElementById('list-empty').classList.add('hidden');
    try {
        const r = await fetch('/chat/users');
        const list = await r.json();
        const el = document.getElementById('list-direct');
        el.innerHTML = '';
        users = {};
        list.forEach(u => {
            users[u.id] = u;
            const c   = COLORS[u.name.charCodeAt(0) % COLORS.length];
            const dm  = dmRoom(ME, u.id);
            const d   = document.createElement('div');
            d.className   = 'conv-item';
            d.dataset.room = dm;
            d.dataset.name = u.name;
            d.dataset.color = c;
            d.innerHTML = `<div class="c-av ${c}">${u.initials}</div>
                <div style="flex:1;min-width:0"><div class="conv-name">${esc(u.name)}</div><div class="conv-preview"><span style="display:inline-block;width:7px;height:7px;border-radius:9999px;background:#22c55e;vertical-align:middle;margin-right:6px;"></span>En ligne • ${ucRole(u.role)}</div></div>`;
            d.onclick = () => openConv(dm, u.name, c, 'direct');
            el.appendChild(d);
        });
        if (list.length === 0) document.getElementById('list-empty').classList.remove('hidden');
    } catch(e) { console.error(e); }
    document.getElementById('list-load').classList.add('hidden');
    filterConv();
}

async function loadOnlineByAdministration() {
    const loadingEl = document.getElementById('online-loading');
    const emptyEl = document.getElementById('online-empty');
    const groupsEl = document.getElementById('online-groups');
    const totalEl = document.getElementById('online-total');

    if (!loadingEl || !groupsEl || !totalEl || !emptyEl) return;

    loadingEl.classList.remove('hidden');
    emptyEl.classList.add('hidden');

    try {
        const r = await fetch('/chat/online-by-administration');
        if (!r.ok) return;

        const groups = await r.json();
        groupsEl.innerHTML = '';

        let total = 0;
        groups.forEach(group => {
            total += Number(group.count || 0);
            const g = document.createElement('div');
            g.className = 'admin-group';

            const usersHtml = (group.users || []).map(u => {
                const c = COLORS[u.name.charCodeAt(0) % COLORS.length];
                return `<div class="online-user">
                    <div class="online-dot"></div>
                    <div class="c-av ${c}" style="width:28px;height:28px;font-size:.65rem;">${esc(u.initials)}</div>
                    <div class="online-meta" style="flex:1;">
                        <div class="online-name">${esc(u.name)}${u.is_me ? ' (Vous)' : ''}</div>
                        <div class="online-role">${ucRole(u.role)}</div>
                    </div>
                </div>`;
            }).join('');

            g.innerHTML = `<div class="admin-group-hd">
                <div class="admin-group-name" title="${esc(group.administration_name || 'Sans administration')}">${esc(group.administration_name || 'Sans administration')}</div>
                <div class="admin-group-count">${group.count || 0}</div>
            </div>${usersHtml}`;

            groupsEl.appendChild(g);
        });

        totalEl.textContent = `${total} en ligne`;
        if (!groups.length) {
            emptyEl.classList.remove('hidden');
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingEl.classList.add('hidden');
    }
}

function dmRoom(a,b){ return 'dm_'+[a,b].sort().join('_'); }
function ucRole(r){ return r==='admin'?'Administrateur':r==='superadmin'?'Super Admin':'Utilisateur'; }

async function sendHeartbeat() {
    try {
        await fetch('/chat/heartbeat', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': CSRF}
        });
    } catch (e) {}
}

function startPresenceLoops() {
    sendHeartbeat();
    if (hbTimer) clearInterval(hbTimer);
    hbTimer = setInterval(sendHeartbeat, 20000);

    if (contactsTimer) clearInterval(contactsTimer);
    contactsTimer = setInterval(function() {
        if (curTab === 'direct') {
            loadUsers();
        }
        loadOnlineByAdministration();
    }, 20000);

    loadOnlineByAdministration();
}

// ── Ouvrir conversation ───────────────────────────────────────
function openConv(room, name, color, type) {
    curRoom=room; curName=name; curColor=color; curType=type; lastTs=null;
    seenMsgIds = new Set();

    document.querySelectorAll('.conv-item').forEach(e=>e.classList.remove('active'));
    const ci = document.querySelector(`.conv-item[data-room="${room}"]`);
    if (ci) ci.classList.add('active');

    const av = document.getElementById('hdr-av');
    av.className = `c-av ${color}`;
    av.innerHTML = type==='direct' ? name.substring(0,2).toUpperCase() : '<i class="fas fa-hashtag text-sm"></i>';
    document.getElementById('hdr-name').textContent = name;
    document.getElementById('hdr-sub').textContent  = type==='direct' ? 'Message direct' : 'Salon public';

    const panel = document.getElementById('msg-panel');
    panel.classList.remove('hidden');
    panel.style.display = 'flex';
    document.getElementById('chat-wrap').classList.add('sm-msg');

    const inp = document.getElementById('msg-input');
    inp.disabled    = false;
    inp.placeholder = `Message dans ${name}…`;
    document.getElementById('msg-send').disabled = false;

    document.getElementById('msg-body').innerHTML = '';
    clearInterval(timer);
    fetchMessages(true);
    timer = setInterval(()=>fetchMessages(false), 2000);
    inp.focus();
}

// ── Polling ───────────────────────────────────────────────────
async function fetchMessages(init) {
    if (!curRoom) return;
    if (fetching) return;
    fetching = true;
    let url = `/chat/messages?room=${encodeURIComponent(curRoom)}`;
    if (!init && lastTs) url += `&since=${encodeURIComponent(lastTs)}`;
    try {
        const r = await fetch(url);
        if (!r.ok) return;
        const msgs = await r.json();
        if (!msgs.length) return;
        const body = document.getElementById('msg-body');
        const atBot = body.scrollHeight - body.scrollTop - body.clientHeight < 80;
        msgs.forEach(m => appendMsg(m));
        lastTs = msgs[msgs.length-1].ts;
        if (init || atBot) body.scrollTop = body.scrollHeight;
    } catch(e) {
    } finally {
        fetching = false;
    }
}

// ── Afficher bulle ────────────────────────────────────────────
function appendMsg(m) {
    const body = document.getElementById('msg-body');
    const nc = document.getElementById('no-conv-msg');
    if (nc) nc.remove();

    if (m && m.id && seenMsgIds.has(m.id)) return;
    if (m && m.id) seenMsgIds.add(m.id);

    if (m && m.id && body.querySelector(`.msg-row[data-id="${m.id}"]`)) return;

    const row = document.createElement('div');
    row.className = 'msg-row' + (m.mine?' mine':'');
    row.dataset.id = m.id;

    if (!m.mine) {
        row.innerHTML = `
          <div class="msg-av-sm ${curColor}">${esc(m.initials)}</div>
          <div><div class="msg-name-sm">${esc(m.name)}</div>
          <div class="msg-bub them">${esc(m.text).replace(/\n/g,'<br>')}</div></div>
          <span class="msg-time">${m.time}</span>`;
    } else {
        row.innerHTML = `
          <span class="msg-time">${m.time}</span>
          <div class="msg-bub mine">${esc(m.text).replace(/\n/g,'<br>')}</div>`;
    }
    body.appendChild(row);
}

// ── Envoi ─────────────────────────────────────────────────────
async function sendMessage() {
    const inp = document.getElementById('msg-input');
    const txt = inp.value.trim();
    if (!txt || !curRoom) return;
    inp.value = ''; inp.style.height='auto';
    document.getElementById('msg-send').disabled = true;
    try {
        const body = { text:txt, room:curRoom };
        if (curType === 'direct') {
            const parts = curRoom.replace('dm_','').split('_');
            body.recipient_id = parts.find(p=>p!==ME) || null;
        }
        const r = await fetch('/chat/send',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify(body),
        });
        if (r.ok) {
            const msg = await r.json();
            appendMsg(msg);
            lastTs = msg.ts;
            document.getElementById('msg-body').scrollTop = document.getElementById('msg-body').scrollHeight;
        }
    } catch(e) {}
    document.getElementById('msg-send').disabled = false;
    inp.focus();
}

// ── Helpers ───────────────────────────────────────────────────
function handleKey(e){ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMessage();} }
function autoResize(el){ el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,120)+'px'; }
function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function filterConv(){
    const q = document.getElementById('conv-search').value.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(el=>{
        el.style.display = (el.dataset.name||'').toLowerCase().includes(q)?'':'none';
    });
}
document.getElementById('conv-search').addEventListener('input', filterConv);

document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        sendHeartbeat();
        if (curTab === 'direct') loadUsers();
        loadOnlineByAdministration();
    }
});

startPresenceLoops();
</script>
@endpush

@endsection
