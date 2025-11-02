(() => {
  const C = window.DIANA_CHAT_CONFIG || {};
  const log = byId('diana-log'), sugg = byId('diana-suggest'), inp = byId('diana-input');
  const btn = byId('diana-send'), wrap = byId('diana-input-wrap'), clr = byId('diana-clear');
  const EP = C.endpoint || "", NAME = C.name || "DiANA", AVA = C.avatar || "", HELLO = C.greeting || "";
  const PDFR = Array.isArray(C.pdfRules) ? C.pdfRules : [], LS = C.lsKey || "diana_chat_log";

  if (sugg && Array.isArray(C.suggest) && C.suggest.length) {
    sugg.innerHTML = C.suggest.map(x => `<button type="button" class="diana-sbtn" data-q="${escAttr(x)}">${esc(x)}</button>`).join("");
  }

  function byId(id){ return document.getElementById(id); }
  function avatar(){
    const d = document.createElement("div"); d.className = "diana-ava";
    if (AVA){ const i = new Image(); i.src = AVA; i.alt = NAME; d.appendChild(i); }
    else d.textContent = (NAME.trim()[0] || "D").toUpperCase();
    return d;
  }
  function add(role, nodeOrText){
    const r = document.createElement("div"); r.className = "diana-row " + (role==="user"?"diana-me":"diana-bot");
    if (role!=="user") r.appendChild(avatar());
    const b = document.createElement("div"); b.className = "diana-bubble";
    nodeOrText instanceof Node ? b.appendChild(nodeOrText) : b.textContent = String(nodeOrText);
    r.appendChild(b); log.appendChild(r); log.scrollTop = log.scrollHeight; save();
    return { r, b };
  }
  function addText(role, t){ return add(role, String(t)); }

  // Markdown: keine Überschriften. Führende '#' werden entfernt und als normaler Absatz gerendert.
  function esc(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function escAttr(s){ return esc(s).replace(/"/g,"&quot;"); }
  function linkify(s){ return s.replace(/(https?:\/\/[^\s)]+)(?=\)?)/g,'<a href="$1" target="_blank" rel="noopener">$1</a>'); }
  function md2html(md){
    const lines = esc(String(md).replace(/\r\n/g,"\n")).split("\n");
    let out = "", inUL = false, inOL = false;
    const flush = () => { if(inUL){ out+="</ul>"; inUL=false; } if(inOL){ out+="</ol>"; inOL=false; } };
    for (let raw of lines){
      let line = raw.trimEnd();
      if (!line.trim()){ flush(); out+="<p></p>"; continue; }
      // strip heading markers
      if (/^#{1,6}\s+/.test(line)) line = line.replace(/^#{1,6}\s+/, "");
      // lists
      if (/^\d+[\.)]\s+/.test(line)){ if(!inOL){ flush(); out+="<ol>"; inOL=true; } out+=`<li>${linkify(line.replace(/^\d+[\.)]\s+/,""))}</li>`; continue; }
      if (/^[*-]\s+/.test(line)){ if(!inUL){ flush(); out+="<ul>"; inUL=true; } out+=`<li>${linkify(line.replace(/^[*-]\s+/,""))}</li>`; continue; }
      // inline
      line = line.replace(/\*\*([^*]+)\*\*/g,"<strong>$1</strong>")
                 .replace(/\*([^*]+)\*/g,"<em>$1</em>")
                 .replace(/`([^`]+)`/g,"<code>$1</code>");
      flush(); out += `<p>${linkify(line)}</p>`;
    }
    flush(); return out;
  }
  function addMD(role, md){ const w = document.createElement("div"); w.className="diana-md"; w.innerHTML = md2html(md); const { b } = add(role, w); b.dataset.md = md; }

  // Persistenz
  function save(){
    const A = [];
    log.querySelectorAll(".diana-row").forEach(r => {
      const isUser = r.classList.contains("diana-me");
      const b = r.querySelector(".diana-bubble"); if (!b) return;
      const i = b.querySelector("iframe");
      if (i) A.push({role:isUser?"user":"assistant", type:"iframe", src:i.src});
      else if (b.dataset.md) A.push({role:isUser?"user":"assistant", type:"md", text:b.dataset.md});
      else A.push({role:isUser?"user":"assistant", type:"text", text:b.textContent||""});
    });
    localStorage.setItem(LS, JSON.stringify(A));
  }
  function restore(){
    const raw = localStorage.getItem(LS); if (!raw) return;
    try{ JSON.parse(raw).forEach(m => {
      if (m.type==="iframe") addIframe("assistant", m.src);
      else if (m.type==="md") addMD(m.role, m.text);
      else addText(m.role, m.text||"");
    }); }catch{}
  }
  function busy(on){ inp.disabled = on; btn.disabled = on; wrap.classList.toggle("diana-busy", on); }

  // Thinking indicator
  let think=null;
  function showThink(){ if(think) return; const d=document.createElement("div"); d.className="diana-thinking"; for(let i=0;i<3;i++){const x=document.createElement("div"); x.className="diana-dot"; d.appendChild(x);} think = add("assistant", d); }
  function hideThink(){ if(think && think.r && think.r.parentNode){ think.r.parentNode.removeChild(think.r); think=null; } }

  // Media helpers
  const reYT = /(https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=[\w-]{6,}|youtu\.be\/[\w-]{6,}))[^\s]*/i;
  const rePDF = /(https?:\/\/[^\s]+?\.pdf)(?:[^\S\r\n]|$)/i;
  const reThumbLine = /^\s*-?\s*Vorschaubild\s*:\s*https?:\/\/i\.ytimg\.com\/\S+$/i;
  const reAnyThumb = /https?:\/\/i\.ytimg\.com\/\S+/ig;
  const reBareLink = /^\s*-?\s*link\s*:?\s*$/i;

  function ytId(u){ try{ const x=new URL(u); if(x.hostname.includes("youtube.com")) return x.searchParams.get("v"); if(x.hostname.includes("youtu.be")) return x.pathname.slice(1); }catch{} return ""; }
  function addYT(url, title){
    const id = ytId(url); if (!id) return;
    const c = document.createElement("div"); c.style.cssText = "width:100%;max-width:520px;position:relative;aspect-ratio:16/9;background:#000";
    const b = document.createElement("button"); b.style.cssText = "all:unset;cursor:pointer;display:block;width:100%;height:100%;position:relative";
    const img = new Image(); img.className="diana-vthumb"; img.src=`https://i.ytimg.com/vi/${id}/hqdefault.jpg`; img.alt=title||"Video";
    const p = document.createElement("div"); p.style.cssText = "position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:64px;height:64px;border-radius:50%;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px"; p.textContent="▶";
    b.append(img,p); c.appendChild(b);
    b.addEventListener("click", () => {
      c.innerHTML=""; const f=document.createElement("iframe"); f.style.cssText="width:100%;height:100%;border:0"; f.src=`https://www.youtube.com/embed/${id}?autoplay=1&rel=0&modestbranding=1`;
      f.setAttribute("allow","accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"); f.setAttribute("allowfullscreen","true"); c.appendChild(f); save();
    });
    if (title){ const meta=document.createElement("div"); meta.style.cssText="margin-top:6px;font-size:.95em;opacity:.9"; meta.textContent=title; const w=document.createElement("div"); w.append(c,meta); add("assistant", w); }
    else add("assistant", c);
  }
  function addIframe(role, src){ const f=document.createElement("iframe"); f.className="diana-pdfviewer"; f.src = src + (src.includes("#")?"":"#view=FitH"); add(role, f); }
  function addPDF(title,url,thumb){
    const w=document.createElement("div"); w.className="diana-pdfcard";
    const L=document.createElement("div"); L.className="diana-pdfthumb";
    if (thumb){ const i=new Image(); i.src=thumb; i.alt=title||"PDF"; L.appendChild(i); } else L.textContent="PDF";
    const m=document.createElement("div"); m.className="diana-pdfmeta";
    const H=document.createElement("div"); H.className="diana-pdftitle"; H.textContent=title||"Leitfaden";
    const S=document.createElement("div"); S.style.cssText="font-size:12px;opacity:.75"; try{ S.textContent = new URL(url, location.origin).href; }catch{ S.textContent = url; }
    m.append(H,S);
    const B=document.createElement("div"); B.className="diana-pdfbtns";
    const a=document.createElement("a"); a.href=url; a.target="_blank"; a.rel="noopener"; a.className="diana-linkbtn primary"; a.textContent="PDF öffnen";
    const dl=document.createElement("a"); dl.href=url; dl.setAttribute("download",""); dl.className="diana-linkbtn"; dl.textContent="Download";
    const v=document.createElement("button"); v.type="button"; v.className="diana-linkbtn"; v.textContent="Im Chat ansehen"; v.onclick=()=>{ const p=w.parentNode; if(!p) return; p.removeChild(w); addIframe("assistant", url); save(); };
    B.append(a,dl,v); w.append(L,m,B); add("assistant", w);
  }

  // Antwort vorverarbeiten
  function preprocess(txt){
    const lines = String(txt||"").split(/\r?\n/), keep=[], yt=[], pdf=[];
    for (let i=0;i<lines.length;i++){
      let line = lines[i].trim(); if (!line) continue;
      if (reThumbLine.test(line)) continue;
      if (reBareLink.test(line)) continue;

      const mp = line.match(rePDF);
      if (mp){ pdf.push({url:mp[1], title:""}); line = line.replace(rePDF,"").trim(); if (!line) continue; }

      const my = line.match(reYT);
      if (my){
        const url = my[1]; let title = "";
        for (let k=i-1;k>=0;k--){
          const prev = lines[k].trim(); if (!prev) continue;
          if (reBareLink.test(prev)) continue;
          if (!reYT.test(prev) && !rePDF.test(prev) && !reThumbLine.test(prev) && !reAnyThumb.test(prev)) { title = prev.replace(reAnyThumb,"").trim(); break; }
        }
        yt.push({url, title}); line = line.replace(reYT,"").trim(); if (!line) continue;
      }

      line = line.replace(reAnyThumb,"").replace(/\s{2,}/g," ").trim();
      if (reBareLink.test(line)) continue;
      if (line) keep.push(line);
    }

    let clean = keep.join("\n").replace(reAnyThumb,"").replace(/\n{3,}/g,"\n\n").trim();

    // PDF-Regeln anwenden
    PDFR.forEach(rule => {
      try{
        const s = (rule.regex||"").trim(); if (!s) return;
        const rx = s.startsWith("/") && s.lastIndexOf("/")>0
          ? new RegExp(s.slice(1, s.lastIndexOf("/")), s.slice(s.lastIndexOf("/")+1))
          : new RegExp(s.replace(/[.*+?^${}()|[\]\\]/g,"\\$&"), "i");
        if (rx.test(clean)) pdf.push({url:rule.url, title:rule.title, thumb:rule.thumb||""});
      }catch{}
    });

    return { clean, yt, pdf };
  }

  // API
  async function call(q){
    const res = await fetch(EP, { method:"POST", headers:{ "Content-Type":"application/json", "Diana-Origin": location.origin }, body: JSON.stringify({message:q}) });
    const ct = (res.headers.get("content-type")||"").toLowerCase(); let data=null, raw="";
    if (ct.includes("application/json")) { try{ data = await res.json(); }catch{ raw = await res.text(); } }
    else raw = await res.text();
    return { res, data, raw };
  }
  function err(t){ addText("assistant", "Fehler: " + t); }

  async function ask(q){
    try{
      busy(true); showThink();
      const {res, data, raw} = await call(q);
      hideThink(); busy(false);
      if (!res.ok) return err((data&&data.error)||("HTTP "+res.status+(raw?" / "+raw.slice(0,280).trim():"")));
      if (!data) return err((raw||"").slice(0,280).trim()||"Ungültige Antwort vom Server");
      if (data.error) return err(data.error);

      const { clean, yt, pdf } = preprocess(data.reply || "");
      if (clean) addMD("assistant", clean);
      yt.forEach(o => addYT(o.url, o.title||""));
      pdf.forEach(p => addPDF(p.title||"Leitfaden", p.url, p.thumb||""));
      if (!clean && yt.length===0 && pdf.length===0) addText("assistant", "Keine Antwort");
    }catch(e){ hideThink(); busy(false); err(e.message||"Unbekannter Fehler"); }
  }

  // Events
  function send(){ const q = inp.value.trim(); if (!q) return; addText("user", q); inp.value=""; ask(q); }
  btn.addEventListener("click", send);
  inp.addEventListener("keydown", e => { if (e.key === "Enter") send(); });
  if (sugg){ sugg.addEventListener("click", e => { const b=e.target.closest(".diana-sbtn"); if (!b) return; const q=b.getAttribute("data-q")||""; if (!q) return; addText("user", q); ask(q); }); }
  clr.addEventListener("click", () => { localStorage.removeItem(LS); log.innerHTML=""; if (HELLO) addText("assistant", HELLO); });

  // Start
  const cached = localStorage.getItem(LS); if (cached) restore(); else if (HELLO) addText("assistant", HELLO);
})();
