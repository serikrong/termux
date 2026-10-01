<script>

// == jsUSER1.php== อย่าลบ อย่าแก้ บรรทัดนี้ // 

(function() {
    const storedUsername = localStorage.getItem('username');
    if (!storedUsername) return window.location.href = 'login22.php';

    // --- ตรวจสอบเงื่อนไข Username / Role ---
    let userRole = 'others';
    if (storedUsername === '0956820142') {
        userRole = 'Admin';
    } else if (storedUsername === '1234') {
        userRole = 'user';
    } else {
        userRole = 'others';
    }

    console.log('User Role:', userRole);

    const features = ['gps', 'radar', 'rain', 'saveone', 'youtube', 'data', 'menu', 'admin'];
    let userSettings = {
        iconOrder: [], features: {}, clockOn: 'off', calendarOn: 'off',
        speakerOn: 'off', googleOn: 'on', findBoxOn: 'on', usernameDisplayOn: 'off', customIcons: {}, systemOrder: [],
        findQuery: '', searchEngine: 'google'
    };

    // --- กรองรายการ Default Order ตาม Username ---
    const allDefaultOrder = ['user1GpsBtn', 'user1RadarBtn', 'user1RainBtn', 'user1SaveoneBtn', 'user1YoutubeBtn', 'user1DataBtn', 'user1MenuBtn', 'user1AdminBtn'];
    const defaultOrder = storedUsername === '0956820142' 
        ? allDefaultOrder 
        : ['user1RainBtn', 'user1YoutubeBtn', 'user1RadarBtn'];

    const defaultSystemOrder = ['user1ClockBtn', 'user1CalendarBtn', 'user1SpeakerBtn', 'user1MoreBtn'];

    // --- รายการ Master Icon Configs ทั้งหมด ---
    const allIconConfigs = {
        user1GpsBtn: { key: 'gps', name: 'จีพีเอส', script: 'gps', init: () => (window.initGps1 || window.initGpsTracker || window.startGps1)?.(), stop: () => (window.stopGps1 || window.stopGpsTracker)?.(), html: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><g id="gps-icon-group"><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/><circle cx="12" cy="12" r="3"/></g></svg>', class: 'user1-gps-box' },
        user1RadarBtn: { key: 'radar', name: 'เรดาร์', script: 'radar', init: () => (window.initRadar1 || window.startRadar1)?.(), stop: () => window.stopRadar1?.(), html: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1" fill="currentColor"/><g id="radar-icon-group"><line x1="12" y1="12" x2="19" y2="5"/></g></svg>', class: 'user1-radar-box' },
        user1RainBtn: { key: 'rain', name: 'พยากรณ์ฝน', script: 'rain', init: () => (window.initRain1 || window.startRain1)?.(), stop: () => window.stopRain1?.(), html: '<svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4C9.11 4 6.6 5.64 5.35 8.04C2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/><g class="rain-drops"><line x1="7" y1="14" x2="6" y2="19"/><line x1="12" y1="14" x2="11" y2="19"/><line x1="17" y1="14" x2="16" y2="19"/></g></svg>', class: 'user1-rain-box' },
        user1SaveoneBtn: { key: 'saveone', name: 'เซฟวัน', script: 'saveone', init: () => (window.initSaveone1 || window.startSaveone1)?.(), stop: () => window.stopSaveone1?.(), html: '<div class="so-symbol-wrapper"><span class="so-sg">SG</span><span class="so-text">Save</span><span class="so-one">One</span></div>', class: 'user1-saveone-box' },
        user1YoutubeBtn: { key: 'youtube', name: 'Youtube', script: 'youtube', init: () => (window.initYoutube1 || window.initYoutubeTracker || window.startYoutube1)?.(), stop: () => (window.stopYoutube1 || window.stopYoutubeTracker)?.(), html: '<svg viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>', class: 'user1-youtube-btn-box' },
        user1DataBtn: { key: 'data', name: 'บัญชี', script: 'data', init: () => (window.initData1 || window.startData1)?.(), stop: () => window.stopData1?.(), html: '<div class="db-symbol-wrapper"><span class="db-text">DB</span><span class="data-text">Data</span></div>', class: 'user1-data-box' },
        user1MenuBtn: { key: 'menu', name: 'เมนู', script: 'menu', init: () => (window.initMenu1 || window.startMenu1)?.(), stop: () => window.stopMenu1?.(), html: '<div class="ap-symbol-wrapper"><span class="ap-text">AP</span><span class="app-text">App</span></div>', class: 'user1-menu-box' },
        user1AdminBtn: { key: 'admin', name: 'แอดมิน', script: 'admin', init: () => (window.initAdmin1 || window.startAdmin1)?.(), stop: () => window.stopAdmin1?.(), html: '<div class="ad-symbol-wrapper"><span class="ad-text">AD</span><span class="admin-text">admin</span></div>', class: 'user1-admin-box' }
    };

    // --- กรอง iconConfigs ที่เปิดให้ใช้งานตาม Username ---
    const iconConfigs = {};
    if (storedUsername === '0956820142') {
        Object.assign(iconConfigs, allIconConfigs);
    } else {
        iconConfigs.user1RainBtn = allIconConfigs.user1RainBtn;
        iconConfigs.user1YoutubeBtn = allIconConfigs.user1YoutubeBtn;
        iconConfigs.user1RadarBtn = allIconConfigs.user1RadarBtn;
    }

    const systemConfigs = {
        user1ClockBtn: {
            key: 'clock', name: 'เวลา', script: 'time',
            init: () => toggleSystemState('clock', 'on', () => loadScript('time', () => (window.initTime1 || window.initTimeTracker)?.())),
            stop: () => toggleSystemState('clock', 'off', () => { (window.stopTime1 || window.stopTimeTracker)?.(); document.getElementById('time-container')?.remove(); }),
            html: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polyline points="12 6 12 12 16 14"/></svg>', class: 'user1-clock-box'
        },
        user1CalendarBtn: {
            key: 'calendar', name: 'ปฏิทิน', script: 'calendar',
            init: () => toggleSystemState('calendar', 'on', () => loadScript('calendar', () => window.initCalendar1?.())),
            stop: () => toggleSystemState('calendar', 'off', () => { window.stopCalendar1?.(); document.getElementById('calendar-container')?.remove(); }),
            html: '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>', class: 'user1-calendar-box-system'
        },
        user1SpeakerBtn: {
            key: 'speaker', name: 'ลำโพง', script: '',
            init: () => {
                userSettings.speakerOn = 'on'; window.speaker = 1; saveSettings();
                updateSpeakerUI('on');
                window.speakText('เปิดลำโพง');
            },
            stop: () => {
                userSettings.speakerOn = 'off'; window.speaker = 0; saveSettings();
                updateSpeakerUI('off');
            },
            html: '<svg viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>', class: 'user1-speaker-box'
        },
        user1MoreBtn: { key: 'more', name: 'เมนูเพิ่มเติม', script: '', init: toggleMenuEditInline, stop: () => document.getElementById('menuEditInlineContainer')?.remove(), html: '<span style="font-size:18px;font-weight:bold;line-height:1;">⋮</span>', class: 'user1-more-box' }
    };

    const escapeAttr = str => !str ? '' : String(str).replace(/[&"'<>]/g, m => ({'&':'&amp;','"':'&quot;',"'":'&#39;','<':'&lt;','>':'&gt;'}[m]));

    function toggleSystemState(type, state, callback) {
        userSettings[type + 'On'] = state;
        window[type] = state === 'on' ? 1 : 0;
        saveSettings();
        const el = document.getElementById(type === 'clock' ? 'user1ClockStatus' : 'user1CalendarTopBtn');
        if (el) el.setAttribute('data-state', state);
        callback?.();
    }

    function updateSpeakerUI(state) {
        const btn = document.getElementById('user1SpeakerTopBtn');
        if (!btn) return;
        btn.dataset.state = state;
        const line = btn.querySelector('.speaker-off-line');
        if (line) line.style.display = state === 'on' ? 'none' : 'block';
    }

    function registerCustomIconConfig(id, item) {
        const capScript = item.script ? item.script.charAt(0).toUpperCase() + item.script.slice(1) : '';
        iconConfigs[id] = {
            key: item.key || '', name: item.name || '', script: item.script || '', html: item.html || '', class: item.class || 'user1-custom-box',
            init: () => loadScript(item.script, () => window[`init${capScript}1`]?.()),
            stop: () => window[`stop${capScript}1`]?.()
        };
    }

    async function serverRequest(action, data = null) {
        try {
            const res = await fetch('jsUser1Save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, username: storedUsername, app: 'jsUser1', ...(data && { data }) })
            });
            return await res.json();
        } catch (e) { console.error('Server request failed:', e); }
    }

    async function loadSettings() {
        const result = await serverRequest('load');
        if (result?.success && result.data && Object.keys(result.data).length) {
            Object.assign(userSettings, result.data);
            if (userSettings.customIcons) {
                Object.keys(userSettings.customIcons).forEach(id => registerCustomIconConfig(id, userSettings.customIcons[id]));
            }
        }
    }

    const saveSettings = () => serverRequest('save', userSettings);

    window.speakText = (text, lang = 'th-TH') => {
        if (userSettings.speakerOn !== 'on' || !('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(Object.assign(new SpeechSynthesisUtterance(text), { lang }));
    };

    function injectStyles() {
        if (document.getElementById('user1-box-styles')) return;
        const style = document.createElement('style');
        style.id = 'user1-box-styles';
        style.innerText = `
            .user1-line-container{width:100%;margin:0;padding:4px 0;display:flex;flex-direction:column;gap:5px;background:#000;box-sizing:border-box;}
            .user1-line-1,.user1-line-2-wrapper,.user1-line-find{width:100%;display:flex;align-items:center;background:#000;box-sizing:border-box;padding:0 4px;}
            .user1-line-1{justify-content:space-between;}
            .user1-line-2-wrapper{position:relative;gap:4px;}
            .user1-line-2{width:100%;display:flex;flex-wrap:wrap;align-items:center;background:#000;box-sizing:border-box;gap:6px;}
            .user1-line-find{padding:2px 4px;gap:6px;position:relative;}
            .user1-find-input{flex-grow:1;min-width:0;background:#111;border:1px solid #444;color:#fff;padding:5px 8px;box-sizing:border-box;border-radius:4px;font:10pt/1.2 sans-serif !important;}
            .user1-find-input:focus{border-color:#a9e34b;outline:none;}
            .user1-line-left-group{display:flex;align-items:center;gap:8px;flex-shrink:0;}
            .user1-line-left{font-size:15px;font-weight:bold;color:#fff;display:flex;align-items:center;gap:6px;cursor:pointer;padding:0 4px;flex-shrink:0;user-select:none;}
            .user1-line-left svg{width:18px;height:18px;fill:none;stroke:#777;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:stroke 0.2s;}
            .user1-line-left[data-state="on"] svg{stroke:#a9e34b !important;}
            .user1-username-text{font-size:14px;color:#fff;font-family:sans-serif;font-weight:normal;cursor:pointer;}
            
            /* สไตล์สำหรับปิดการคลิกเมื่อ username เป็น 1234 */
            .no-click {
                pointer-events: none !important;
                cursor: default !important;
            }

            .user1-userpage-container { 
                width: 100%; 
                min-height: 80vh; 
                border: none; 
                margin-top: 5px; 
                background: #111; 
                border-radius: 4px; 
                display: block;
                box-sizing: border-box;
            }

            .user1-context-menu {
                position: absolute; background: #222; border: 1px solid #444; border-radius: 4px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.4); z-index: 9999; display: none; padding: 4px 0; min-width: 100px;
            }
            .user1-context-item {
                padding: 6px 12px; font-size: 13px; color: #fff; cursor: pointer; font-family: sans-serif;
            }
            .user1-context-item:hover { background: #333; color: #ff4d4d; }

            .user1-toggle-find-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;background:transparent;border:1px solid #444;border-radius:4px;user-select:none;transition:all 0.2s;}
            .user1-toggle-find-btn svg{width:18px;height:18px;fill:none;stroke:#777;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
            .user1-toggle-find-btn[data-state="on"]{border-color:#a9e34b;}
            .user1-toggle-find-btn[data-state="on"] svg{stroke:#a9e34b !important;}
            
            @keyframes searchIconPulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.15); }
                100% { transform: scale(1); }
            }
            .user1-toggle-find-btn[data-state="on"] svg {
                animation: searchIconPulse 1.5s ease-in-out infinite;
                transform-origin: center;
            }

            .user1-right-group{display:flex;align-items:center;gap:8px;flex-shrink:0;}
            .user1-left-group{width:100%;display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex-shrink:0;min-width:0;}
            .sortable-item{width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:grab;touch-action:none;flex-shrink:0;user-select:none;}
            .sortable-item img{width:24px;height:24px;object-fit:contain;}
            .dragging{opacity:.4;transform:scale(.9);}
            .user1-gps-box svg,.user1-radar-box svg,.user1-rain-box svg,.user1-speaker-top-btn svg,.user1-custom-box svg,.user1-clock-box svg,.user1-calendar-box-system svg,.user1-speaker-box svg{fill:none;stroke:#777;stroke-width:2.5;width:24px;height:24px;}
            .user1-youtube-btn-box svg{fill:#777;stroke:none;width:24px;height:24px;}
            [data-state="on"] svg{stroke:#a9e34b !important;}
            .user1-youtube-btn-box[data-state="on"] svg{fill:#ff0000 !important;}

            /* --- รูปแบบปุ่มขนาดใหญ่ 2 บรรทัด สำหรับ Username 1234 --- */
            .user1-username-1234 .sortable-item {
                width: 68px;
                height: 58px;
                flex-direction: column;
                justify-content: center;
                gap: 2px;
                background: #181818;
                border: 1px solid #333;
                border-radius: 6px;
                padding: 4px;
                box-sizing: border-box;
            }
            .user1-username-1234 .sortable-item[data-state="on"] {
                border-color: #a9e34b;
                background: #222;
            }
            .user1-username-1234 .sortable-item svg {
                width: 26px !important;
                height: 26px !important;
            }
            .user1-icon-label {
                font-size: 11px;
                color: #aaa;
                font-family: sans-serif;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
                text-align: center;
            }
            .user1-username-1234 .sortable-item[data-state="on"] .user1-icon-label {
                color: #a9e34b;
                font-weight: bold;
            }

            .ap-symbol-wrapper,.db-symbol-wrapper,.ad-symbol-wrapper{position:relative;width:100%;height:32px;display:flex;align-items:center;justify-content:center;}
            .ap-symbol-wrapper span,.db-symbol-wrapper span,.ad-symbol-wrapper span{position:absolute;font-weight:bold;font-family:sans-serif;transition:opacity .4s ease,transform .4s ease,color .4s ease;}
            .ap-symbol-wrapper .ap-text,.db-symbol-wrapper .db-text,.ad-symbol-wrapper .ad-text{opacity:1;transform:scale(1);color:#777;font-size:13px;}
            .ap-symbol-wrapper .app-text,.db-symbol-wrapper .data-text,.ad-symbol-wrapper .admin-text{opacity:0;transform:scale(.8);color:#777;font-size:11px;}
            #user1MenuBtn[data-state="on"] .ap-text,#user1DataBtn[data-state="on"] .db-text,#user1AdminBtn[data-state="on"] .ad-text{opacity:0;transform:scale(.8);}
            #user1MenuBtn[data-state="on"] .app-text,#user1DataBtn[data-state="on"] .data-text,#user1AdminBtn[data-state="on"] .admin-text{opacity:1;transform:scale(1);color:#a9e34b !important;}
            .custom-symbol{color:#777;font-size:13px;font-weight:bold;font-family:sans-serif;transition:color .4s ease,transform .4s ease;}
            .sortable-item[data-state="on"] .custom-symbol{color:#a9e34b !important;}
            .sortable-item[data-state="off"] .custom-symbol{color:#777 !important;}
            #user1SaveoneBtn{width:44px;}
            .so-symbol-wrapper{position:relative;width:100%;height:32px;display:flex;flex-direction:column;align-items:center;justify-content:center;}
            .so-symbol-wrapper span{position:absolute;font-family:sans-serif;transition:opacity .4s ease,transform .4s ease,color .4s ease;text-align:center;}
            .so-symbol-wrapper .so-sg{opacity:1;transform:scale(1);color:#777;font-size:13px;font-weight:bold;position:static;}
            .so-symbol-wrapper .so-text,.so-symbol-wrapper .so-one{opacity:0;transform:scale(.8);color:#777;font-size:9.5px;font-weight:bold;width:100%;}
            #user1SaveoneBtn[data-state="on"] .so-sg{opacity:0;transform:scale(.8);position:absolute;}
            #user1SaveoneBtn[data-state="on"] .so-text,#user1SaveoneBtn[data-state="on"] .so-one{opacity:1;transform:scale(1);color:#a9e34b !important;}
            @keyframes spin{100%{transform:rotate(360deg);}}
            #user1GpsBtn[data-state="on"] #gps-icon-group,#user1RadarBtn[data-state="on"] #radar-icon-group{animation:spin 2.5s linear infinite;transform-origin:12px 12px;}
            @keyframes fallRain{0%{transform:translateY(0);opacity:.3;}50%{transform:translateY(2.5px);opacity:1;}100%{transform:translateY(0);opacity:.3;}}
            #user1RainBtn[data-state="on"] .rain-drops{animation:fallRain .6s ease-in-out infinite;}
            @keyframes youtubePlayPulse{0%{transform:scale(1);}50%{transform:scale(1.12);}100%{transform:scale(1);}}
            #user1YoutubeBtn[data-state="on"]{animation:youtubePlayPulse 1.2s ease-in-out infinite;}
            .user1-clock-inline{font-size:14px;color:#fff;font-family:monospace;font-weight:bold;cursor:pointer;padding:2px 5px;}
            .user1-speaker-top-btn,.user1-more-top-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;user-select:none;}
            .user1-more-top-btn{font-size:20px;font-weight:bold;color:#fff;}
            .user1-calendar-top-btn{cursor:pointer;display:flex;align-items:center;justify-content:center;user-select:none;}
            .user1-calendar-box{width:26px;height:26px;border:1.5px solid #fff;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:bold;font-family:sans-serif;color:#fff;background:transparent;box-sizing:border-box;}
            #user1CalendarTopBtn[data-state="on"] .user1-calendar-box{border-color:#a9e34b;color:#a9e34b;}
            .menu-inline-section{width:100%;background:#1a1a1a;border:1px solid #444;border-radius:6px;padding:10px;box-sizing:border-box;color:#fff;font-family:sans-serif;margin-top:4px;font-size:10pt;}
            .menu-inline-header{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #333;padding-bottom:8px;margin-bottom:8px;}
            .menu-inline-header h3{margin:0;color:#ffcc00;font-size:10pt;font-weight:normal;}
            .header-actions{display:flex;align-items:center;gap:6px;}
            .btn-save-menu{background:#ffcc00;color:#000;border:none;padding:4px 12px;cursor:pointer;border-radius:4px;font-size:10pt;}
            .btn-clear-menu{background:#ff4d4d;color:#fff;border:none;padding:4px 10px;cursor:pointer;border-radius:4px;font-size:10pt;}
            .btn-inline-close{background:transparent;color:#aaa;border:none;cursor:pointer;font-size:16px;line-height:1;padding:0 4px;}
            .menu-edit-table{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:6px;font-size:10pt;}
            .menu-edit-table th,.menu-edit-table td{border:1px solid #333;padding:5px 4px;text-align:center;vertical-align:middle;font-weight:normal;font-size:10pt;}
            .menu-edit-table th{background:#222;color:#ffcc00;text-align:center;}
            .menu-edit-table input{width:100%;background:#000;border:1px solid #444;color:#fff;padding:3px 5px;box-sizing:border-box;border-radius:3px;font:10pt/1.2 sans-serif !important;}
            .menu-edit-table input:focus{border-color:#ffcc00;outline:none;}
            .menu-edit-table th:nth-child(1),.menu-edit-table td:nth-child(1){width:36px;}
            .menu-edit-table th:last-child,.menu-edit-table td:last-child{width:32px;}
            .col-order-no{color:#ffcc00;cursor:grab;touch-action:none;user-select:none;text-align:center !important;font-weight:bold !important;}
            .col-order-no:active{cursor:grabbing;}
            .menu-row-dragging{opacity:.35;}
            .menu-row-drag-over{border-top:2px solid #ffcc00 !important;}
            .btn-row-delete{color:#ff4d4d;cursor:pointer;user-select:none;font-size:14px !important;font-weight:bold !important;text-align:center !important;}
            .btn-row-delete:hover{color:#ff0000;}
            .menu-edit-table tr{touch-action:auto;}
            @media(max-width:600px){
                .user1-line-2,.user1-left-group{flex-wrap:wrap;}
                .sortable-item{flex:0 0 auto;}
                .menu-inline-section{padding:6px;}
                .menu-edit-table,.menu-edit-table th,.menu-edit-table td{font-size:9pt;padding:4px 2px;}
            }
        `;
        document.head.appendChild(style);
    }

    function loadScript(name, callback) {
        if (!name) return callback?.();
        const id = `js-${name}-script`;
        if (!document.getElementById(id)) {
            const script = document.createElement('script');
            script.id = id;
            script.src = `js${name.charAt(0).toUpperCase() + name.slice(1)}1.php`;
            script.onload = () => callback?.();
            document.head.appendChild(script);
        } else {
            callback?.();
        }
    }

    function applyUserIframeState() {
        const lineContainer = document.querySelector('.user1-line-container');
        if (!lineContainer) return;

        const iconEl = document.getElementById('user1UsernameDisplay');
        const textEl = document.getElementById('user1UsernameText');
        const state = userSettings.usernameDisplayOn;

        if (iconEl) iconEl.dataset.state = state;
        if (textEl) textEl.style.display = state === 'on' ? 'inline' : 'none';

        let iframe = document.getElementById('user1UserPageFrame');

        if (state === 'on') {
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'user1UserPageFrame';
                iframe.className = 'user1-userpage-container';
                iframe.src = `${encodeURIComponent(storedUsername)}/index.php`;

                iframe.onload = () => {
                    try {
                        const scrollHeight = iframe.contentWindow.document.body.scrollHeight;
                        if (scrollHeight > 0) {
                            iframe.style.height = (scrollHeight + 20) + 'px';
                        }
                    } catch (e) {
                        console.warn('Cannot auto-fit iframe height:', e);
                    }
                };
                lineContainer.appendChild(iframe);
            } else {
                iframe.style.display = 'block';
            }
        } else {
            if (iframe) {
                iframe.style.display = 'none';
            }
        }
    }

    function toggleUserIframe() {
        // หากเป็น username 1234 ป้องกันไม่ให้เปิด/ปิด iframe
        if (storedUsername === '1234') return;

        userSettings.usernameDisplayOn = userSettings.usernameDisplayOn === 'on' ? 'off' : 'on';
        saveSettings();
        applyUserIframeState();
    }

    function renderIcons() {
        const sortableContainer = document.getElementById('sortableContainer');
        if (!sortableContainer) return;

        const validIconOrder = (userSettings.iconOrder || []).filter(id => iconConfigs[id]);

        sortableContainer.innerHTML = validIconOrder.map(id => {
            const item = iconConfigs[id];
            if (!item) return '';
            const state = (userSettings.features && userSettings.features[item.key]) || 'off';
            
            const labelHtml = storedUsername === '1234' ? `<span class="user1-icon-label">${escapeAttr(item.name)}</span>` : '';
            return `<div id="${id}" title="${item.name || ''}" class="${item.class || 'user1-custom-box'} sortable-item" data-state="${state}" draggable="true">${item.html || ''}${labelHtml}</div>`;
        }).join('');

        const rightGroup = document.querySelector('.user1-right-group');
        if (!rightGroup) return;

        const currentDay = new Date().getDate();
        const speakerState = userSettings.speakerOn || 'off';
        const calendarState = userSettings.calendarOn || 'off';
        const clockState = userSettings.clockOn || 'off';

        const systemElements = {
            user1ClockBtn: `<span id="user1ClockStatus" class="user1-clock-inline" title="คลิกเพื่อเปิด/ปิดนาฬิกา" data-state="${clockState}">00:00:00</span>`,
            user1CalendarBtn: `<div id="user1CalendarTopBtn" class="user1-calendar-top-btn" title="ปฏิทิน" data-state="${calendarState}"><div class="user1-calendar-box">${currentDay}</div></div>`,
            user1SpeakerBtn: `<div id="user1SpeakerTopBtn" class="user1-speaker-top-btn" title="ลำโพง" data-state="${speakerState}"><svg viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/><line class="speaker-off-line" x1="2" y1="2" x2="22" y2="22" style="display:${speakerState === 'on' ? 'none' : 'block'};"/></svg></div>`,
            user1MoreBtn: `<div id="user1MoreTopBtn" class="user1-more-top-btn" title="แก้ไขปุ่มเมนู">⋮</div>`
        };

        const sysOrder = (userSettings.systemOrder && userSettings.systemOrder.length) ? userSettings.systemOrder : defaultSystemOrder;
        rightGroup.innerHTML = sysOrder.map(id => systemElements[id] || '').join('');
    }

    function toggleMenuEditInline() {
        let container = document.getElementById('menuEditInlineContainer');
        if (container) return container.remove();

        const lineContainer = document.querySelector('.user1-line-container');
        if (!lineContainer) return;

        container = document.createElement('div');
        container.id = 'menuEditInlineContainer';
        container.className = 'menu-inline-section';
        container.innerHTML = `
            <div class="menu-inline-header">
                <h3>ตารางเมนู</h3>
                <div class="header-actions">
                    <button class="btn-save-menu" id="saveMenuInline">Save</button>
                    <button class="btn-clear-menu" id="clearMenuInline">Clear</button>
                    <button class="btn-inline-close" id="closeMenuInline">×</button>
                </div>
            </div>
            <table class="menu-edit-table"><thead><tr><th>#</th><th>Key</th><th>ชื่อ</th><th>Icon</th><th>JS</th><th>X</th></tr></thead><tbody id="menuTableBody"></tbody></table>
            <table class="menu-edit-table"><thead><tr><th>#</th><th>Key</th><th>ชื่อ</th><th>Icon</th><th>X</th></tr></thead><tbody id="systemTableBody"></tbody></table>
        `;
        lineContainer.insertBefore(container, document.querySelector('.user1-line-2-wrapper')?.nextSibling || null);

        const tbody = document.getElementById('menuTableBody');
        const sysTbody = document.getElementById('systemTableBody');

        const createRow = (id = '', key = '', name = '', html = '', script = '') => {
            const tr = document.createElement('tr');
            tr.dataset.id = id;
            tr.innerHTML = `<td class="col-order-no">0</td><td><input type="text" class="inp-key" value="${escapeAttr(key)}"></td><td><input type="text" class="inp-name" value="${escapeAttr(name)}"></td><td><input type="text" class="inp-html" value="${escapeAttr(html)}"></td><td><input type="text" class="inp-script" value="${escapeAttr(script)}"></td><td class="btn-row-delete" title="ลบ">X</td>`;
            return tr;
        };

        const createSysRow = (id = '', key = '', name = '', html = '') => {
            const tr = document.createElement('tr');
            tr.dataset.id = id;
            tr.innerHTML = `<td class="col-order-no">0</td><td><input type="text" class="inp-sys-key" value="${escapeAttr(key)}" readonly style="color:#888;"></td><td><input type="text" class="inp-sys-name" value="${escapeAttr(name)}"></td><td><input type="text" class="inp-sys-html" value="${escapeAttr(html)}"></td><td class="btn-row-delete" title="ลบ">X</td>`;
            return tr;
        };

        const reindex = () => {
            [tbody, sysTbody].forEach(b => {
                let n = 1;
                b.querySelectorAll('tr').forEach(r => {
                    if (r.style.display !== 'none') r.querySelector('.col-order-no').innerText = n++;
                });
            });
        };

        (userSettings.iconOrder || []).forEach(id => {
            const item = iconConfigs[id];
            if (item) tbody.appendChild(createRow(id, item.key, item.name, item.html, item.script));
        });
        tbody.appendChild(createRow());

        (userSettings.systemOrder?.length ? userSettings.systemOrder : defaultSystemOrder).forEach(id => {
            const item = systemConfigs[id];
            if (item) sysTbody.appendChild(createSysRow(id, item.key, item.name, item.html));
        });
        reindex();

        tbody.addEventListener('input', e => {
            if (!e.target.classList.contains('inp-key')) return;
            const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none');
            if (e.target.closest('tr') === rows[rows.length - 1] && e.target.value.trim() !== '') {
                tbody.appendChild(createRow());
                reindex();
            }
        });

        container.addEventListener('click', e => {
            if (e.target.classList.contains('btn-row-delete')) {
                e.target.closest('tr').style.display = 'none';
                reindex();
            }
            if (e.target.id === 'closeMenuInline') container.remove();
        });

        const bindTableDrag = tableBody => {
            let dragRow = null, pointerId = null, timer = null, dragging = false, startX = 0, startY = 0;
            const clearDrag = () => {
                if (timer) { clearTimeout(timer); timer = null; }
                if (dragRow) dragRow.classList.remove('menu-row-dragging');
                tableBody.querySelectorAll('.menu-row-drag-over').forEach(r => r.classList.remove('menu-row-drag-over'));
                dragRow = null; dragging = false; pointerId = null;
            };

            tableBody.addEventListener('pointerdown', ev => {
                const handle = ev.target.closest('.col-order-no');
                if (!handle) return;
                const row = handle.closest('tr');
                if (!row) return;
                pointerId = ev.pointerId; startX = ev.clientX; startY = ev.clientY;
                if (ev.pointerType === 'touch') {
                    timer = setTimeout(() => { dragRow = row; dragging = true; row.classList.add('menu-row-dragging'); row.setPointerCapture?.(pointerId); }, 700);
                } else {
                    dragRow = row; dragging = true; row.classList.add('menu-row-dragging'); row.setPointerCapture?.(pointerId);
                }
                ev.preventDefault();
            }, { passive: false });

            tableBody.addEventListener('pointermove', ev => {
                if (!dragRow || !dragging) {
                    if (timer && ev.pointerType === 'touch' && Math.hypot(ev.clientX - startX, ev.clientY - startY) > 8) { clearTimeout(timer); timer = null; }
                    return;
                }
                const rows = Array.from(tableBody.querySelectorAll('tr')).filter(r => r !== dragRow && r.style.display !== 'none');
                let target = rows.find(row => ev.clientY < row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2);
                tableBody.querySelectorAll('.menu-row-drag-over').forEach(r => r.classList.remove('menu-row-drag-over'));
                if (target) {
                    target.classList.add('menu-row-drag-over');
                    tableBody.insertBefore(dragRow, target);
                } else {
                    tableBody.appendChild(dragRow);
                }
                reindex();
                ev.preventDefault();
            }, { passive: false });

            tableBody.addEventListener('pointerup', () => {
                if (dragging && dragRow && tableBody === tbody) {
                    userSettings.iconOrder = Array.from(tbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none').map(r => r.dataset.id).filter(Boolean);
                }
                clearDrag();
            });
            tableBody.addEventListener('pointercancel', clearDrag);
        };

        bindTableDrag(tbody);
        bindTableDrag(sysTbody);

        document.getElementById('saveMenuInline').onclick = async () => {
            const newOrderList = [];
            userSettings.customIcons = {};

            tbody.querySelectorAll('tr').forEach(row => {
                if (row.style.display === 'none') return;
                const key = row.querySelector('.inp-key')?.value.trim() || '';
                const name = row.querySelector('.inp-name')?.value.trim() || '';
                const htmlVal = row.querySelector('.inp-html')?.value.trim() || '';
                const scriptVal = row.querySelector('.inp-script')?.value.trim() || '';
                if (!key && !name) return;

                const id = row.dataset.id || `custom_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
                let customHtml = htmlVal || iconConfigs[id]?.html || '';

                if (/^https?:\/\//i.test(htmlVal) || (/\.(png|jpg|jpeg|gif|webp|svg)$/i.test(htmlVal) && !htmlVal.startsWith('<'))) {
                    customHtml = `<img src="${escapeAttr(htmlVal)}" alt="${escapeAttr(name)}">`;
                } else if (!customHtml) {
                    customHtml = `<span class="custom-symbol">${escapeAttr(key.substring(0, 2).toUpperCase())}</span>`;
                }

                registerCustomIconConfig(id, { key, name: name || key, script: scriptVal, html: customHtml });
                userSettings.customIcons[id] = { key, name: name || key, html: customHtml, script: scriptVal };
                newOrderList.push(id);
            });

            userSettings.iconOrder = newOrderList;
            userSettings.systemOrder = Array.from(sysTbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none').map(r => {
                const id = r.dataset.id;
                if (id && systemConfigs[id]) {
                    const name = r.querySelector('.inp-sys-name')?.value.trim();
                    const html = r.querySelector('.inp-sys-html')?.value.trim();
                    if (name) systemConfigs[id].name = name;
                    if (html) systemConfigs[id].html = html;
                }
                return id;
            }).filter(Boolean);

            await saveSettings();
            renderIcons();
            container.remove();
            if (window.speaker === 1) window.speakText('บันทึกการตั้งค่าเมนูเรียบร้อย');
        };

        document.getElementById('clearMenuInline').onclick = async () => {
            if (!confirm('คุณต้องการเรียกค่าเริ่มต้นกลับมาทั้งหมดใช่หรือไม่?')) return;
            userSettings.iconOrder = [...defaultOrder];
            userSettings.systemOrder = [...defaultSystemOrder];
            userSettings.customIcons = {};
            await saveSettings();
            renderIcons();
            container.remove();
            if (window.speaker === 1) window.speakText('เรียกค่าเริ่มต้นเรียบร้อย');
        };
    }

    async function initUser1() {
        await loadSettings();
        injectStyles();

        const container = document.getElementById('user1');
        if (!container) return;

        userSettings.iconOrder = Array.from(new Set([...(userSettings.iconOrder || []), ...defaultOrder])).filter(id => iconConfigs[id]);
        if (!userSettings.systemOrder?.length) userSettings.systemOrder = [...defaultSystemOrder];
        
        if (storedUsername === '1234') {
            userSettings.findBoxOn = 'on';
        } else if (!userSettings.findBoxOn) {
            userSettings.findBoxOn = 'on';
        }

        if (!userSettings.usernameDisplayOn) userSettings.usernameDisplayOn = 'off';

        features.forEach(f => window[f] = userSettings.features?.[f] === 'on' ? 1 : 0);
        window.speaker = userSettings.speakerOn === 'on' ? 1 : 0;

        const findBoxDisplay = userSettings.findBoxOn === 'on' ? 'flex' : 'none';
        const toggleBtnDisplay = storedUsername === '1234' ? 'none' : 'flex';
        const usernameDisplayState = userSettings.usernameDisplayOn;
        const usernameTextDisplay = usernameDisplayState === 'on' ? 'inline' : 'none';

        const displayText = `${escapeAttr(storedUsername)} (${userRole})`;
        const user1324Class = storedUsername === '1234' ? 'user1-username-1234' : '';

        // ถ้าเป็น username 1234 ให้ใส่คลาส 'no-click'
        const noClickClass = storedUsername === '1234' ? 'no-click' : '';

        container.innerHTML = `
            <div class="user1-line-container ${user1324Class}">
                <div class="user1-line-1">
                    <div class="user1-line-left-group">
                        <span id="user1UsernameDisplay" class="user1-line-left ${noClickClass}" title="คลิกเปิด/ปิดหน้าผู้ใช้ | คลิกขวาเพื่อ Logout" data-state="${usernameDisplayState}">
                            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <span id="user1UsernameText" class="user1-username-text ${noClickClass}" style="display:${usernameTextDisplay};" title="คลิกเปิด/ปิดหน้าผู้ใช้ | คลิกขวาเพื่อ Logout">${displayText}</span>
                        <div id="user1ToggleFindBtn" class="user1-toggle-find-btn" style="display:${toggleBtnDisplay};" title="ซ่อน/แสดงช่องค้นหา" data-state="${userSettings.findBoxOn}">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </div>
                    </div>
                    <span class="user1-right-group"></span>
                </div>
                <div class="user1-line-2-wrapper"><div class="user1-line-2"><div id="sortableContainer" class="user1-left-group"></div></div></div>
                <div class="user1-line-find" style="display:${findBoxDisplay};"><input type="text" id="user1FindInput" class="user1-find-input" placeholder="ค้นหา..."></div>
                <div id="user2"></div>
            </div>
            <div id="user1ContextMenu" class="user1-context-menu">
                <div id="user1LogoutBtn" class="user1-context-item">Logout</div>
            </div>
        `;

        renderIcons();
        applyUserIframeState();

        setInterval(() => {
            const clockEl = document.getElementById('user1ClockStatus');
            if (clockEl) clockEl.innerText = new Date().toLocaleTimeString('th-TH', { hour12: false });
        }, 1000);

        const sortableContainer = document.getElementById('sortableContainer');
        const findInput = document.getElementById('user1FindInput');
        const toggleFindBtn = document.getElementById('user1ToggleFindBtn');
        const findBoxContainer = document.querySelector('.user1-line-find');
        const usernameDisplayEl = document.getElementById('user1UsernameDisplay');
        const usernameTextEl = document.getElementById('user1UsernameText');
        const contextMenu = document.getElementById('user1ContextMenu');
        const logoutBtn = document.getElementById('user1LogoutBtn');

        const showContextMenu = (e) => {
            e.preventDefault();
            contextMenu.style.display = 'block';
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.top = `${e.pageY}px`;
        };

        usernameDisplayEl?.addEventListener('contextmenu', showContextMenu);
        usernameTextEl?.addEventListener('contextmenu', showContextMenu);

        window.addEventListener('click', () => {
            if (contextMenu) contextMenu.style.display = 'none';
        });

        logoutBtn?.addEventListener('click', () => {
            window.location.href = 'logout.php';
        });

        // เฉพาะผู้ใช้ที่ไม่ใช่ 1234 ถึงจะคลิกเพื่อ Toggle Iframe ได้
        if (storedUsername !== '1234') {
            usernameDisplayEl?.addEventListener('click', toggleUserIframe);
            usernameTextEl?.addEventListener('click', toggleUserIframe);
        }

        toggleFindBtn?.addEventListener('click', () => {
            if (storedUsername === '1234') return;

            userSettings.findBoxOn = userSettings.findBoxOn === 'on' ? 'off' : 'on';
            toggleFindBtn.dataset.state = userSettings.findBoxOn;
            
            if (findBoxContainer) {
                findBoxContainer.style.display = userSettings.findBoxOn === 'on' ? 'flex' : 'none';
            }

            if (userSettings.findBoxOn === 'off') {
                if (typeof window.stopFind1 === 'function') {
                    window.stopFind1();
                } else {
                    document.getElementById('find1-container')?.remove();
                }
            }

            saveSettings();
        });

        findInput?.addEventListener('focus', () => loadScript('find', () => window.initFind1?.()));
        findInput?.addEventListener('input', e => { userSettings.findQuery = e.target.value; saveSettings(); loadScript('find', () => window.initFind1?.()); });
        findInput?.addEventListener('keypress', e => {
            if (e.key !== 'Enter' || userSettings.googleOn !== 'on') return;
            const query = e.target.value.trim();
            if (!query) return;
            const engine = userSettings.searchEngine || 'google';
            let url = engine === 'youtube' ? 'https://www.youtube.com/results?search_query=' + encodeURIComponent(query) :
                      (engine === 'map' || query.startsWith('ไป')) ? 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query.startsWith('ไป') ? query.replace(/^ไป/, '').trim() : query) :
                      'https://www.google.com/search?q=' + encodeURIComponent(query);
            window.open(url, '_blank');
        });

        sortableContainer.addEventListener('click', e => {
            const el = e.target.closest('.sortable-item');
            if (!el) return;
            const handler = iconConfigs[el.id];
            if (!handler) return;

            const newState = el.dataset.state === 'off' ? 'on' : 'off';
            el.dataset.state = newState;
            if (!userSettings.features) userSettings.features = {};
            userSettings.features[handler.key] = newState;
            window[handler.key] = newState === 'on' ? 1 : 0;
            saveSettings();

            if (window.speaker === 1) window.speakText(`${newState === 'on' ? 'เปิด' : 'ปิด'}${handler.name}`);
            if (newState === 'on') loadScript(handler.script, () => handler.init());
            else { handler.stop?.(); if (handler.script) document.getElementById(handler.script + '-container')?.remove(); }
        });

        let draggedItem = null;
        sortableContainer.addEventListener('dragstart', e => { draggedItem = e.target.closest('.sortable-item'); draggedItem?.classList.add('dragging'); });
        sortableContainer.addEventListener('dragend', () => { draggedItem?.classList.remove('dragging'); userSettings.iconOrder = Array.from(sortableContainer.children).map(c => c.id); saveSettings(); draggedItem = null; });
        sortableContainer.addEventListener('dragover', e => {
            e.preventDefault();
            if (!draggedItem) return;
            const after = [...sortableContainer.querySelectorAll('.sortable-item:not(.dragging)')].reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = e.clientX - box.left - box.width / 2;
                return (offset < 0 && offset > closest.offset) ? { offset, element: child } : closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element;
            if (after == null) sortableContainer.appendChild(draggedItem);
            else sortableContainer.insertBefore(draggedItem, after);
        });

        document.querySelector('.user1-right-group').addEventListener('click', e => {
            if (e.target.closest('#user1ClockStatus')) {
                const state = userSettings.clockOn === 'on' ? 'off' : 'on';
                systemConfigs.user1ClockBtn[state === 'on' ? 'init' : 'stop']();
            } else if (e.target.closest('#user1CalendarTopBtn')) {
                const state = userSettings.calendarOn === 'on' ? 'off' : 'on';
                systemConfigs.user1CalendarBtn[state === 'on' ? 'init' : 'stop']();
            } else if (e.target.closest('#user1SpeakerTopBtn')) {
                const state = userSettings.speakerOn === 'on' ? 'off' : 'on';
                systemConfigs.user1SpeakerBtn[state === 'on' ? 'init' : 'stop']();
            } else if (e.target.closest('#user1MoreTopBtn')) {
                toggleMenuEditInline();
            }
        });

        features.forEach(f => {
            if (userSettings.features?.[f] === 'on') {
                const handler = Object.values(iconConfigs).find(h => h.key === f);
                if (handler) loadScript(handler.script, () => handler.init());
            }
        });

        if (userSettings.calendarOn === 'on') loadScript('calendar', () => window.initCalendar1?.());
    }

    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', initUser1) : initUser1();
})();
</script>
