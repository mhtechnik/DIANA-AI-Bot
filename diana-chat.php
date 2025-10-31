<?php
/**
 * Plugin Name: Diana Chat
 * Description: Chat Widget mit OpenAI (GPT-5 via Responses API). Markdown, YouTube- und PDF-Einbettung mit Inline-Viewer, Prompt-Buttons, Tipp-Indicator, Rate-Limit, Origin-Check.
 * Version: 1.8.2
 */

if (!defined('ABSPATH')) exit;

/* Limits */
if (!defined('DIANA_BURST_10S')) define('DIANA_BURST_10S', 5);
if (!defined('DIANA_HOURLY'))     define('DIANA_HOURLY', 120);

/* Einstellungen */
add_action('admin_menu', function () {
  add_options_page('Diana Chat Einstellungen', 'Diana Chat', 'manage_options', 'diana-chat', 'diana_chat_settings_page');
});

add_action('admin_init', function () {
  $fields = [
    'diana_openai_api_key' => 'sanitize_text_field',
    'diana_base_url'       => 'esc_url_raw',
    'diana_model'          => 'sanitize_text_field',
    'diana_system_prompt'  => 'wp_kses_post',
    'diana_temperature'    => 'floatval',
    'diana_max_tokens'     => 'intval',
    'diana_stop_sequences' => 'sanitize_text_field',
    'diana_assistant_name' => 'sanitize_text_field',
    'diana_avatar_url'     => 'esc_url_raw',
    'diana_greeting'       => 'wp_kses_post',
    'diana_suggestions'    => 'sanitize_text_field',
    'diana_pdfs'           => 'wp_kses_post',
  ];
  foreach ($fields as $name => $cb) {
    register_setting('diana_chat_settings', $name, ['sanitize_callback' => $cb]);
  }

  add_settings_section('diana_chat_api', 'API', null, 'diana-chat');
  add_settings_field('diana_openai_api_key', 'API Key', function () {
    $v = esc_attr(get_option('diana_openai_api_key', ''));
    echo '<input type="text" name="diana_openai_api_key" value="'.$v.'" style="width:420px">';
  }, 'diana-chat', 'diana_chat_api');
  add_settings_field('diana_base_url', 'Base URL', function () {
    $v = esc_attr(get_option('diana_base_url', 'https://api.openai.com'));
    echo '<input type="url" name="diana_base_url" value="'.$v.'" style="width:420px">';
  }, 'diana-chat', 'diana_chat_api');

  add_settings_section('diana_chat_model', 'Modell', null, 'diana-chat');
  add_settings_field('diana_model', 'Model', function () {
    $v = esc_attr(get_option('diana_model', 'gpt-5'));
    echo '<input type="text" name="diana_model" value="'.$v.'" style="width:240px">
          <p class="description">Für GPT-5 wird automatisch die Responses API genutzt.</p>';
  }, 'diana-chat', 'diana_chat_model');
  add_settings_field('diana_temperature', 'Temperatur', function () {
    $v = esc_attr(get_option('diana_temperature', ''));
    echo '<input type="number" step="0.01" min="0" max="2" name="diana_temperature" value="'.$v.'" style="width:120px">
          <p class="description">Bei gpt-5 wird temperature nicht gesendet.</p>';
  }, 'diana-chat', 'diana_chat_model');
  add_settings_field('diana_max_tokens', 'Max Tokens', function () {
    $v = esc_attr(get_option('diana_max_tokens', '1000'));
    echo '<input type="number" min="1" name="diana_max_tokens" value="'.$v.'" style="width:120px">
          <p class="description">Responses nutzt max_output_tokens.</p>';
  }, 'diana-chat', 'diana_chat_model');
  add_settings_field('diana_stop_sequences', 'Stop Sequenzen', function () {
    $v = esc_attr(get_option('diana_stop_sequences', ''));
    echo '<input type="text" name="diana_stop_sequences" value="'.$v.'" style="width:100%;max-width:780px" placeholder="END,||">';
  }, 'diana-chat', 'diana_chat_model');

  add_settings_section('diana_chat_prompt', 'System Prompt', null, 'diana-chat');
  add_settings_field('diana_system_prompt', 'Prompt', function () {
    $v = esc_textarea(get_option('diana_system_prompt', 'Du bist Diana, eine ruhige Co Moderatorin. Antworte kurz und klar.'));
    echo '<textarea name="diana_system_prompt" rows="8" style="width:100%;max-width:780px">'.$v.'</textarea>';
  }, 'diana-chat', 'diana_chat_prompt');

  add_settings_section('diana_chat_ui', 'UI', null, 'diana-chat');
  add_settings_field('diana_assistant_name', 'Name im UI', function () {
    $v = esc_attr(get_option('diana_assistant_name', 'DiANA'));
    echo '<input type="text" name="diana_assistant_name" value="'.$v.'" style="width:240px">';
  }, 'diana-chat', 'diana_chat_ui');
  add_settings_field('diana_avatar_url', 'Avatar URL', function () {
    $v = esc_attr(get_option('diana_avatar_url', ''));
    echo '<input type="url" name="diana_avatar_url" value="'.$v.'" style="width:420px" placeholder="https://.../avatar.png">';
  }, 'diana-chat', 'diana_chat_ui');
  add_settings_field('diana_greeting', 'Begrüßung', function () {
    $v = esc_textarea(get_option('diana_greeting', 'Hallo, ich bin DiANA. Wie kann ich helfen?'));
    echo '<textarea name="diana_greeting" rows="3" style="width:100%;max-width:780px">'.$v.'</textarea>';
  }, 'diana-chat', 'diana_chat_ui');
  add_settings_field('diana_suggestions', 'Prompt Buttons', function () {
    $v = esc_attr(get_option('diana_suggestions', 'Was kannst du,Moderier die Diskussion,Erstelle eine Agenda,Erkläre ein Fachbegriff,Erzeuge eine Kurz-Zusammenfassung'));
    echo '<input type="text" name="diana_suggestions" value="'.$v.'" style="width:100%;max-width:780px">';
  }, 'diana-chat', 'diana_chat_ui');

  add_settings_section('diana_chat_pdfs', 'PDF-Guides', function () {
    echo '<p>Eine Zeile pro Regel: <code>Regex | Titel | https://.../leitfaden.pdf | optionales Thumbnail</code><br>Beispiel: <code>/Moderationszyklus|Agenda/i | Leitfaden Moderationszyklus | https://deine-seite.de/Methoden.pdf | https://deine-seite.de/thumb.png</code>. Zusätzlich: jede .pdf-URL in Dianas Antwort erzeugt automatisch eine Karte.</p>';
  }, 'diana-chat');
  add_settings_field('diana_pdfs', 'Regeln', function () {
    $def = "/*Moderationszyklus|Agenda|Methoden|Check-in|Check-out*/i | Methoden-Sammlung | https://example.com/Methoden-alle.pdf";
    $v = esc_textarea(get_option('diana_pdfs', $def));
    echo '<textarea name="diana_pdfs" rows="6" style="width:100%;max-width:780px">'.$v.'</textarea>';
  }, 'diana-chat', 'diana_chat_pdfs');
});

function diana_chat_settings_page() { ?>
<div class="wrap">
  <h1>Diana Chat</h1>
  <form method="post" action="options.php">
    <?php settings_fields('diana_chat_settings'); do_settings_sections('diana-chat'); submit_button(); ?>
  </form>
  <p>Shortcode: <code>[diana_chat]</code></p>
</div>
<?php }

/**
 * cURL Hardening fuer OpenAI:
 * IPv4 + HTTP/1.1, kurze Connect-Zeit, 60s Gesamt, Expect aus, Keep-Alive aus
 */
add_action('http_api_curl', function($handle, $r, $url){
  if (strpos($url, 'api.openai.com') !== false) {
    if (defined('CURL_IPRESOLVE_V4')) curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    if (defined('CURL_HTTP_VERSION_1_1')) curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);

    $hdrs = [];
    if (!empty($r['headers'])) {
      foreach ($r['headers'] as $k => $v) { $hdrs[] = $k . ': ' . $v; }
    }
    $hdrs[] = 'Expect:';            // 100-continue unterdrücken
    $hdrs[] = 'Connection: close';  // keine Wiederverwendung
    curl_setopt($handle, CURLOPT_HTTPHEADER, $hdrs);
  }
}, 10, 3);

/* Shortcode: UI + Logik */
add_shortcode('diana_chat', function () {
  $name      = esc_html(get_option('diana_assistant_name', 'DiANA'));
  $avatar    = esc_url(get_option('diana_avatar_url', ''));
  $greeting  = wp_kses_post(get_option('diana_greeting', ''));
  $suggest   = get_option('diana_suggestions', '');
  $endpoint  = esc_url(rest_url('diana/v1/chat'));

  // PDF-Regeln parsen
  $pdfRulesRaw = (string) get_option('diana_pdfs', '');
  $pdfRules = [];
  if (trim($pdfRulesRaw) !== '') {
    $lines = preg_split('/\r\n|\r|\n/', $pdfRulesRaw);
    foreach ($lines as $line) {
      if (!trim($line)) continue;
      $parts = array_map('trim', explode('|', $line));
      if (count($parts) >= 3) {
        $pdfRules[] = [
          'regex' => $parts[0],
          'title' => $parts[1],
          'url'   => $parts[2],
          'thumb' => $parts[3] ?? ''
        ];
      }
    }
  }

  $buttons = array_values(array_filter(array_map('trim', explode(',', $suggest))));

  ob_start(); ?>
  <div id="diana-wrap" style="width:100%;box-sizing:border-box;max-width:100%;margin:1rem 0;">
    <style>
      :root{
        --diana-primary:#1a6ce6;
        --diana-accent:#09a3e3;
        --diana-dark:#0e2a4a;
        --diana-text:#0b1220;
        --diana-bg:#f7fafc;
        --diana-border:#dbe5f1;
      }
      .diana-box{width:100%;border:1px solid var(--diana-border);border-radius:12px;padding:12px;background:#fff;font:16px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Arial;color:var(--diana-text)}
      .diana-log{min-height:220px;max-height:520px;overflow:auto;padding:8px 4px;background:var(--diana-bg);border-radius:10px}
      .diana-row{display:flex;gap:10px;margin:10px 0;align-items:flex-end}
      .diana-me{justify-content:flex-end}
      .diana-bubble{max-width:80%;padding:10px 12px;border-radius:14px;word-wrap:break-word;white-space:pre-wrap;background:#fff;border:1px solid var(--diana-border)}
      .diana-me .diana-bubble{background:var(--diana-dark);color:#fff;border-color:var(--diana-dark)}
      .diana-ava{width:36px;height:36px;border-radius:50%;flex:0 0 36px;background:linear-gradient(135deg,var(--diana-primary),var(--diana-accent));display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;overflow:hidden}
      .diana-ava img{width:100%;height:100%;object-fit:cover;border-radius:50%}
      .diana-input{display:flex;gap:8px;margin-top:10px}
      .diana-input input{flex:1;padding:.7rem;border:1px solid var(--diana-border);border-radius:10px;outline:none}
      .diana-input input:focus{border-color:var(--diana-primary);box-shadow:0 0 0 3px rgba(26,108,230,.15)}
      .diana-input button{padding:.7rem 1rem;border:0;border-radius:10px;background:var(--diana-primary);color:#fff}
      .diana-toolbar{display:flex;gap:8px;justify-content:space-between;margin-top:8px;flex-wrap:wrap}
      .diana-right{display:flex;gap:8px}
      .diana-suggest{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0}
      .diana-suggest button{background:#fff;border:1px solid var(--diana-border);color:var(--diana-dark);border-radius:18px;padding:.35rem .7rem;cursor:pointer}
      .diana-suggest button:hover{border-color:var(--diana-primary);color:var(--diana-primary)}
      .diana-thinking{display:inline-flex;gap:6px;align-items:center}
      .diana-dot{width:6px;height:6px;border-radius:50%;background:var(--diana-primary);opacity:.4;animation:diana-blink 1.4s infinite}
      .diana-dot:nth-child(2){animation-delay:.2s}.diana-dot:nth-child(3){animation-delay:.4s}
      @keyframes diana-blink{0%,80%,100%{opacity:.2}40%{opacity:1}}
      .diana-busy{opacity:.6;pointer-events:none}
      .diana-vthumb{width:100%;height:100%;object-fit:cover;border:0}
      .diana-pdfcard{display:flex;gap:12px;align-items:center;background:#fff;border:1px solid var(--diana-border);border-radius:10px;padding:10px 12px;margin-top:6px}
      .diana-pdfthumb{width:64px;height:64px;background:#eef3fb;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;overflow:hidden}
      .diana-pdfthumb img{width:100%;height:100%;object-fit:cover;border-radius:6px}
      .diana-pdfmeta{flex:1;min-width:0}
      .diana-pdftitle{font-weight:600}
      .diana-pdfbtns{display:flex;gap:8px}
      .diana-linkbtn{padding:.45rem .7rem;border:1px solid var(--diana-border);border-radius:8px;background:#fff;cursor:pointer;text-decoration:none}
      .diana-linkbtn.primary{background:var(--diana-primary);color:#fff;border-color:var(--diana-primary)}
      .diana-pdfviewer{width:100%;max-width:640px;aspect-ratio:4/3;border:0}
      .diana-bubble .diana-md{line-height:1.55}
      .diana-bubble .diana-md h1,.diana-bubble .diana-md h2,.diana-bubble .diana-md h3{margin:.2rem 0 .4rem;font-weight:700;color:var(--diana-dark)}
      .diana-bubble .diana-md h1{font-size:1.25rem}.diana-bubble .diana-md h2{font-size:1.15rem}.diana-bubble .diana-md h3{font-size:1.05rem}
      .diana-bubble .diana-md p{margin:.4rem 0}
      .diana-bubble .diana-md ul,.diana-bubble .diana-md ol{padding-left:1.25rem;margin:.4rem 0}
      .diana-bubble .diana-md li{margin:.15rem 0}
      .diana-bubble .diana-md a{color:var(--diana-primary);text-decoration:underline}
      .diana-bubble .diana-md code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.9em;background:#f2f6fb;border:1px solid var(--diana-border);border-radius:4px;padding:.05rem .3rem}
    </style>

    <div class="diana-box">
      <div id="diana-log" class="diana-log"></div>

      <?php if (!empty($buttons)) : ?>
        <div class="diana-suggest" id="diana-suggest">
          <?php foreach ($buttons as $b) {
            $label = esc_html($b);
            echo '<button type="button" class="diana-sbtn" data-q="'.esc_attr($label).'">'.$label.'</button>';
          } ?>
        </div>
      <?php endif; ?>

      <div class="diana-input" id="diana-input-wrap">
        <input id="diana-input" type="text" placeholder="Frag <?php echo $name; ?>...">
        <button id="diana-send">Senden</button>
      </div>

      <div class="diana-toolbar">
        <div></div>
        <div class="diana-right">
          <button id="diana-clear" type="button" title="Verlauf löschen">Verlauf löschen</button>
        </div>
      </div>
    </div>
  </div>

  <script>
  (() => {
    const log = document.getElementById('diana-log');
    const input = document.getElementById('diana-input');
    const sendBtn = document.getElementById('diana-send');
    const inputWrap = document.getElementById('diana-input-wrap');
    const btnClear = document.getElementById('diana-clear');
    const suggest = document.getElementById('diana-suggest');

    const endpoint = <?php echo json_encode($endpoint); ?>;
    const name = <?php echo json_encode($name); ?>;
    const avatar = <?php echo json_encode($avatar); ?>;
    const greet = <?php echo json_encode($greeting); ?>;
    const pdfRules = <?php echo wp_json_encode($pdfRules, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
    const LS_KEY = 'diana_chat_log_v8';

    function assistantAvatar(){
      const d = document.createElement('div');
      d.className = 'diana-ava';
      if (avatar) { const img = document.createElement('img'); img.src = avatar; img.alt = name; d.appendChild(img); }
      else { d.textContent = name?.trim()?.charAt(0)?.toUpperCase() || 'D'; }
      return d;
    }

    function addBubble(role, nodeOrText){
      const row = document.createElement('div');
      row.className = 'diana-row ' + (role === 'user' ? 'diana-me' : 'diana-bot');
      if (role !== 'user') row.appendChild(assistantAvatar());
      const bubble = document.createElement('div');
      bubble.className = 'diana-bubble';
      if (nodeOrText instanceof Node) bubble.appendChild(nodeOrText);
      else bubble.textContent = nodeOrText;
      row.appendChild(bubble);
      log.appendChild(row);
      log.scrollTop = log.scrollHeight;
      persist();
      return {row, bubble};
    }
    function addText(role, text){ return addBubble(role, String(text)); }

    /* Markdown Renderer */
    function escapeHtml(s){ return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
    function linkify(s){ return s.replace(/(https?:\/\/[^\s)]+)(?=\)?)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>'); }
    function mdToHtml(md){
      const esc = escapeHtml(md).replace(/\r\n/g,'\n'); const lines = esc.split('\n');
      let out='', inUL=false, inOL=false;
      const flush=()=>{ if(inUL){out+='</ul>';inUL=false;} if(inOL){out+='</ol>';inOL=false;} };
      for(let raw of lines){
        const line = raw.trimEnd();
        if(!line.trim()){ flush(); out += '<p></p>'; continue; }
        if(/^###\s+/.test(line)){ flush(); out += `<h3>${linkify(line.replace(/^###\s+/,''))}</h3>`; continue; }
        if(/^##\s+/.test(line)){ flush(); out += `<h2>${linkify(line.replace(/^##\s+/,''))}</h2>`; continue; }
        if(/^#\s+/.test(line)){  flush(); out += `<h1>${linkify(line.replace(/^#\s+/,''))}</h1>`;  continue; }
        if(/^\d+\s*[\.\)]\s+/.test(line)){ if(!inOL){flush(); out+='<ol>'; inOL=true;} out+=`<li>${linkify(line.replace(/^\d+\s*[\.\)]\s+/,''))}</li>`; continue; }
        if(/^[*-]\s+/.test(line)){ if(!inUL){flush(); out+='<ul>'; inUL=true;} out+=`<li>${linkify(line.replace(/^[*-]\s+/,''))}</li>`; continue; }
        let t=line; t=t.replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>').replace(/\*([^*]+)\*/g,'<em>$1</em>').replace(/`([^`]+)`/g,'<code>$1</code>'); t=linkify(t);
        flush(); out+=`<p>${t}</p>`;
      }
      flush(); return out;
    }
    function addMarkdown(role, mdText){
      const wrapper = document.createElement('div');
      wrapper.className = 'diana-md';
      wrapper.innerHTML = mdToHtml(mdText);
      const {row, bubble} = addBubble(role, wrapper);
      bubble.dataset.md = mdText;
      return {row, bubble};
    }

    /* Persistenz */
    function persist(){
      const items = [];
      log.querySelectorAll('.diana-row').forEach(row => {
        const isUser = row.classList.contains('diana-me');
        const b = row.querySelector('.diana-bubble'); if (!b) return;
        const ifr = b.querySelector('iframe');
        if (ifr){ items.push({role:isUser?'user':'assistant', type:'iframe', src:ifr.src}); }
        else if (b.dataset.md){ items.push({role:isUser?'user':'assistant', type:'md', text:b.dataset.md}); }
        else { items.push({role:isUser?'user':'assistant', type:'text', text:b.textContent || ''}); }
      });
      localStorage.setItem(LS_KEY, JSON.stringify(items));
    }
    function restore(){
      const raw = localStorage.getItem(LS_KEY); if (!raw) return;
      try{
        const items = JSON.parse(raw);
        items.forEach(m => {
          if (m.type==='iframe' && m.src) addIframe('assistant', m.src);
          else if (m.type==='md' && m.text) addMarkdown(m.role, m.text);
          else addText(m.role, m.text || '');
        });
      }catch(e){}
    }

    function setBusy(on){
      input.disabled = on; sendBtn.disabled = on;
      inputWrap.classList.toggle('diana-busy', on);
    }

    /* Tipp Indicator */
    let thinkingNode = null;
    function showThinking(){
      if (thinkingNode) return;
      const dots = document.createElement('div'); dots.className='diana-thinking';
      for (let i=0;i<3;i++){ const d=document.createElement('div'); d.className='diana-dot'; dots.appendChild(d); }
      thinkingNode = addBubble('assistant', dots);
    }
    function hideThinking(){
      if (thinkingNode && thinkingNode.row && thinkingNode.row.parentNode) {
        thinkingNode.row.parentNode.removeChild(thinkingNode.row); thinkingNode=null;
      }
    }

    /* YouTube */
    function ytIdFromUrl(url){ try{ const u=new URL(url); if(u.hostname.includes('youtube.com'))return u.searchParams.get('v'); if(u.hostname.includes('youtu.be'))return u.pathname.slice(1);}catch(e){} return ''; }
    function addVideoBubbleFromUrl(url, title=''){
      const id = ytIdFromUrl(url); if (!id) return;
      const container = document.createElement('div'); container.style.width='100%'; container.style.maxWidth='520px'; container.style.position='relative'; container.style.aspectRatio='16/9'; container.style.background='#000';
      const btn = document.createElement('button'); btn.style.all='unset'; btn.style.cursor='pointer'; btn.style.display='block'; btn.style.width='100%'; btn.style.height='100%'; btn.style.position='relative';
      const thumb = document.createElement('img'); thumb.className='diana-vthumb'; thumb.src='https://i.ytimg.com/vi/'+id+'/hqdefault.jpg'; thumb.alt=title||'Video';
      const play = document.createElement('div'); play.style.position='absolute'; play.style.left='50%'; play.style.top='50%'; play.style.transform='translate(-50%,-50%)'; play.style.width='64px'; play.style.height='64px'; play.style.borderRadius='50%'; play.style.background='rgba(0,0,0,.6)'; play.style.display='flex'; play.style.alignItems='center'; play.style.justifyContent='center'; play.style.color='#fff'; play.style.fontSize='28px'; play.textContent='▶';
      btn.appendChild(thumb); btn.appendChild(play); container.appendChild(btn);
      btn.addEventListener('click', () => {
        const src='https://www.youtube.com/embed/'+id+'?autoplay=1&rel=0&modestbranding=1';
        container.innerHTML=''; const ifr=document.createElement('iframe'); ifr.style.width='100%'; ifr.style.height='100%'; ifr.style.border='0'; ifr.src=src;
        ifr.setAttribute('allow','accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'); ifr.setAttribute('allowfullscreen','true'); container.appendChild(ifr); persist();
      });
      if (title){ const meta=document.createElement('div'); meta.style.marginTop='6px'; meta.style.fontSize='14px'; meta.style.opacity='.8'; meta.textContent=title; const wrap=document.createElement('div'); wrap.appendChild(container); wrap.appendChild(meta); addBubble('assistant', wrap); }
      else { addBubble('assistant', container); }
    }

    /* PDF */
    function addIframe(role, src){
      const ifr = document.createElement('iframe'); ifr.className='diana-pdfviewer';
      ifr.src = src + (src.includes('#') ? '' : '#view=FitH'); addBubble(role, ifr);
    }
    function addPdfCard(title, url, thumb=''){
      const wrap=document.createElement('div'); wrap.className='diana-pdfcard';
      const left=document.createElement('div'); left.className='diana-pdfthumb';
      if (thumb){ const img=document.createElement('img'); img.src=thumb; img.alt=title||'PDF'; left.appendChild(img); } else { left.textContent='PDF'; }
      const meta=document.createElement('div'); meta.className='diana-pdfmeta';
      const h=document.createElement('div'); h.className='diana-pdftitle'; h.textContent=title||'Leitfaden';
      const small=document.createElement('div'); small.style.fontSize='12px'; small.style.opacity='.75'; try{ small.textContent=new URL(url,window.location.origin).href; }catch(e){ small.textContent=url; }
      meta.appendChild(h); meta.appendChild(small);
      const btns=document.createElement('div'); btns.className='diana-pdfbtns';
      const open=document.createElement('a'); open.href=url; open.target='_blank'; open.rel='noopener'; open.className='diana-linkbtn primary'; open.textContent='PDF öffnen';
      const dl=document.createElement('a'); dl.href=url; dl.setAttribute('download',''); dl.className='diana-linkbtn'; dl.textContent='Download';
      const view=document.createElement('button'); view.type='button'; view.className='diana-linkbtn'; view.textContent='Im Chat ansehen';
      view.addEventListener('click', ()=>{ const p=wrap.parentNode; if(!p) return; p.removeChild(wrap); addIframe('assistant', url); persist(); });
      btns.appendChild(open); btns.appendChild(dl); btns.appendChild(view);
      wrap.appendChild(left); wrap.appendChild(meta); wrap.appendChild(btns);
      addBubble('assistant', wrap);
    }

    /* Antwort analysieren: YouTube + PDF + Text säubern */
    function processAssistantReply(text){
      const yt=[], pdfs=[];
      const reYT=/(https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=[\w-]{6,}|youtu\.be\/[\w-]{6,})[^\s]*)/gi;
      const rePDF=/(https?:\/\/[^\s]+?\.pdf)(?:[^\S\r\n]|$)/gi;
      let clean=text;
      let m; while((m=reYT.exec(text))!==null) yt.push(m[0]); if(yt.length) clean=clean.replace(reYT,'').replace(/\s{2,}/g,' ').trim();
      let p; while((p=rePDF.exec(text))!==null) pdfs.push({url:p[1], title:''}); if(pdfs.length) clean=clean.replace(rePDF,'').replace(/\s{2,}/g,' ').trim();
      if (pdfRules && pdfRules.length){
        pdfRules.forEach(rule => {
          try{
            const s=(rule.regex||'').trim(); if(!s) return;
            let rx=null; if(s.startsWith('/') && s.lastIndexOf('/')>0){ const last=s.lastIndexOf('/'); rx=new RegExp(s.slice(1,last), s.slice(last+1)); }
            else { rx=new RegExp(s.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'i'); }
            if (rx.test(text)) pdfs.push({url:rule.url, title:rule.title, thumb:rule.thumb||''});
          }catch(e){}
        });
      }
      return { clean, yt, pdfs };
    }

    /* robuster Fetch: JSON bevorzugt, Text-Fallback, sinnvolle Fehlermeldung */
    async function callApi(q){
      const res = await fetch(endpoint, {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'Diana-Origin':window.location.origin
        },
        body:JSON.stringify({message:q})
      });

      const ctype = (res.headers.get('content-type') || '').toLowerCase();
      let parsed = null, raw = '';
      if (ctype.includes('application/json')) {
        try { parsed = await res.json(); }
        catch(e){ /* JSON kaputt, auf Text fallen */ raw = await res.text(); }
      } else {
        raw = await res.text();
      }
      return {res, data:parsed, raw};
    }

    function showError(text){
      addText('assistant', 'Fehler: ' + text);
    }

    async function ask(q){
      try{
        setBusy(true); showThinking();
        const {res, data, raw} = await callApi(q);
        hideThinking(); setBusy(false);

        if (!res.ok) {
          // Server hat evtl. HTML ausgeliefert
          if (data && data.error) return showError(data.error);
          const snippet = (raw || '').slice(0, 280).trim();
          return showError('HTTP ' + res.status + (snippet ? ' – ' + snippet : ''));
        }

        if (!data) {
          // Kein JSON, aber 2xx
          const snippet = (raw || '').slice(0, 280).trim();
          return showError(snippet || 'Ungültige Antwort vom Server');
        }

        if (data.error) return showError(data.error);

        const reply = data.reply || '';
        const { clean, yt, pdfs } = processAssistantReply(reply);
        if (clean) addMarkdown('assistant', clean);
        if (yt && yt.length) yt.forEach(u => addVideoBubbleFromUrl(u));
        if (pdfs && pdfs.length) pdfs.forEach(p => addPdfCard(p.title || 'Leitfaden', p.url, p.thumb || ''));
        if (!clean && (!yt || !yt.length) && (!pdfs || !pdfs.length)) addText('assistant', 'Keine Antwort');
      }catch(e){
        hideThinking(); setBusy(false);
        showError(e.message || 'Unbekannter Fehler');
      }
    }

    function sendNow(){
      const q = input.value.trim(); if (!q) return;
      addText('user', q); input.value=''; ask(q);
    }
    sendBtn.addEventListener('click', sendNow);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') sendNow(); });

    if (suggest){
      suggest.addEventListener('click', e => {
        const b = e.target.closest('.diana-sbtn'); if (!b) return;
        const q = b.getAttribute('data-q') || ''; if (!q) return;
        addText('user', q); ask(q);
      });
    }
    btnClear.addEventListener('click', () => { localStorage.removeItem(LS_KEY); log.innerHTML=''; if (greet) addText('assistant', greet); });

    const raw = localStorage.getItem(LS_KEY); if (raw) restore(); else if (greet) addText('assistant', greet);
  })();
  </script>
  <?php
  return ob_get_clean();
});

/* Helpers: Rate Limit + Origin */
function diana_rl_key($suffix){ $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; return 'diana_rl_' . md5($ip . $suffix); }
function diana_check_rate_limit(){
  $now=time(); $k10=diana_rl_key('10s'); $c10=(int)get_transient($k10);
  if($c10 >= DIANA_BURST_10S) return new WP_Error('too_many','Zu viele Anfragen. Bitte kurz warten.',['status'=>429]);
  set_transient($k10,$c10+1,10);
  $kh=diana_rl_key('hour'); $ch=(int)get_transient($kh);
  if($ch >= DIANA_HOURLY) return new WP_Error('too_many','Limit pro Stunde erreicht.',['status'=>429]);
  $ttl=get_option('_transient_timeout_'.$kh);
  if(!$ttl || $ttl < $now) set_transient($kh,1,HOUR_IN_SECONDS); else set_transient($kh,$ch+1,$ttl - $now);
  return true;
}
function diana_check_origin(){
  $expect = wp_parse_url(home_url(), PHP_URL_HOST);
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  $refer  = $_SERVER['HTTP_REFERER'] ?? '';
  $got=''; if($origin) $got=wp_parse_url($origin, PHP_URL_HOST); elseif($refer) $got=wp_parse_url($refer, PHP_URL_HOST);
  if ($expect && $got && strtolower($expect)!==strtolower($got)) return new WP_Error('bad_origin','Ungültige Herkunft',['status'=>403]);
  return true;
}

/* REST API Proxy */
add_action('rest_api_init', function () {
  register_rest_route('diana/v1', '/chat', [
    'methods' => 'POST',
    'permission_callback' => '__return_true',
    'callback' => function ($req) {
      $rl = diana_check_rate_limit(); if (is_wp_error($rl)) return new WP_REST_Response(['error'=>$rl->get_error_message()], (int)$rl->get_error_data()['status']);
      $oc = diana_check_origin();    if (is_wp_error($oc)) return new WP_REST_Response(['error'=>$oc->get_error_message()], (int)$oc->get_error_data()['status']);

      $msg = trim(wp_strip_all_tags($req->get_param('message') ?? ''));
      if ($msg === '') return new WP_REST_Response(['error'=>'no message'], 400);
      if (strlen($msg) > 4000) return new WP_REST_Response(['error'=>'Nachricht zu lang'], 413);

      $apiKey = trim(get_option('diana_openai_api_key',''));
      if ($apiKey === '') return new WP_REST_Response(['error'=>'API Key fehlt'], 500);

      $base = trim(get_option('diana_base_url','https://api.openai.com'));
      if ($base === '' || !wp_http_validate_url($base)) $base = 'https://api.openai.com';
      $base = rtrim($base, '/');

      $model   = get_option('diana_model','gpt-5');
      $maxTok  = (int) get_option('diana_max_tokens', 1000);
      $tempOpt = get_option('diana_temperature','');
      $stopCsv = trim(get_option('diana_stop_sequences',''));
      $system  = get_option('diana_system_prompt','Du bist Diana, eine ruhige Co Moderatorin. Antworte kurz und klar.');

      $url = $base . '/v1/responses';
      $sendTemperature = !(stripos($model,'gpt-5') === 0);

      $stop = [];
      if ($stopCsv !== '') $stop = array_values(array_filter(array_map('trim', explode(',', $stopCsv))));

      $payload = [
        'model' => $model,
        'input' => [
          ['role'=>'system','content'=>$system],
          ['role'=>'user',  'content'=>$msg],
        ],
        'max_output_tokens' => $maxTok
      ];
      if ($sendTemperature && $tempOpt !== '') $payload['temperature'] = (float) $tempOpt;
      if (!empty($stop)) $payload['stop'] = $stop;

      $make_request = function(array $payload) use ($url, $apiKey) {
        return wp_remote_post($url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
          ],
          'timeout' => 60,
          'body'    => wp_json_encode($payload),
        ]);
      };

      // 1. Versuch
      $res  = $make_request($payload);
      $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
      $json = is_wp_error($res) ? null : json_decode(wp_remote_retrieve_body($res), true);

      // Unsupported-Parameter bereinigen und erneut
      if (!$code || $code >= 400) {
        $msgErr = is_array($json) ? ($json['error']['message'] ?? '') : '';
        $changed = false;
        if (stripos($msgErr, "Unsupported parameter: 'temperature'") !== false && isset($payload['temperature'])) { unset($payload['temperature']); $changed = true; }
        if (stripos($msgErr, "Unsupported parameter: 'stop'") !== false && isset($payload['stop'])) { unset($payload['stop']); $changed = true; }
        if ($changed) {
          $res  = $make_request($payload);
          $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
          $json = is_wp_error($res) ? null : json_decode(wp_remote_retrieve_body($res), true);
        }
      }

      // Retry bei Zeitueberschreitung
      if (is_wp_error($res)) {
        $err = $res->get_error_message();
        if (stripos($err, 'cURL error 28') !== false || stripos($err, 'timed out') !== false || stripos($err, 'Operation timed out') !== false) {
          usleep(2000000);
          $res  = $make_request($payload);
          if (is_wp_error($res)) {
            usleep(5000000);
            $res = $make_request($payload);
          }
          $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
          $json = is_wp_error($res) ? null : json_decode(wp_remote_retrieve_body($res), true);
        }
      }

      if (is_wp_error($res)) {
        error_log('[Diana Chat] HTTP Fehler: ' . $res->get_error_message());
        return new WP_REST_Response(['error'=>$res->get_error_message()], 502);
      }

      if ($code < 200 || $code >= 300) {
        $err = $json['error']['message'] ?? ('HTTP ' . $code);
        if ($code === 401) $err .= ' - API Key prüfen';
        if ($code === 429) $err .= ' - Rate Limit erreicht';
        return new WP_REST_Response(['error'=>$err], $code);
      }

      // Antwort extrahieren
      $reply = '';
      if (isset($json['output_text'])) $reply = is_array($json['output_text']) ? implode('', $json['output_text']) : (string)$json['output_text'];
      if ($reply === '' && !empty($json['output']) && is_array($json['output'])) {
        $buf=''; foreach ($json['output'] as $blk) {
          if (!empty($blk['text'])) $buf .= $blk['text'];
          if (!empty($blk['content']) && is_array($blk['content'])) { foreach ($blk['content'] as $c) if (!empty($c['text'])) $buf .= $c['text']; }
        } $reply=$buf;
      }
      if ($reply === '' && isset($json['choices'][0]['message']['content'])) $reply = (string)$json['choices'][0]['message']['content'];
      if ($reply === '' && isset($json['choices'][0]['text'])) $reply = (string)$json['choices'][0]['text'];
      if ($reply === '') return new WP_REST_Response(['error'=>'empty response'], 502);

      return new WP_REST_Response(['reply'=>$reply], 200);
    }
  ]);
});
