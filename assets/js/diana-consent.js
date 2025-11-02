document.addEventListener("DOMContentLoaded", () => {
  const cfg  = window.DIANA_CHAT_CONFIG || {};
  const wrap = document.getElementById("diana-wrap");
  if (!wrap) return;

  const KEY    = "diana_consent";
  const KEY_AT = KEY + "_at";

  const days   = parseInt(cfg.consentDays || 30, 10);
  const MAX_AGE = Math.max(1, days) * 24 * 60 * 60 * 1000;

  const hasYes = localStorage.getItem(KEY) === "yes";
  const ts     = parseInt(localStorage.getItem(KEY_AT) || "0", 10);
  const valid  = hasYes && ts && (Date.now() - ts <= MAX_AGE);

  if (valid) return;

  const infoText = hasYes
    ? "Deine vorherige Einwilligung ist abgelaufen. Bitte stimme erneut zu, um den Chat zu nutzen."
    : (cfg.consentText || "Um mit DiANA zu chatten, stimme bitte der Datenverarbeitung durch OpenAI (USA) zu.");

  const linkHtml = cfg.privacyUrl
    ? `<p style="margin:.5rem 0;"><a href="${cfg.privacyUrl}" target="_blank" rel="noopener">Datenschutzerklärung ansehen</a></p>`
    : "";

  wrap.innerHTML = `
    <div style="background:#fff;border:1px solid #dbe5f1;border-radius:10px;padding:1rem;text-align:center;max-width:640px;margin:2rem auto;">
      <p style="font-size:0.95em;line-height:1.5;margin:.5rem 0;">${infoText}</p>
      ${linkHtml}
      <div style="margin-top:1rem;display:flex;justify-content:center;gap:10px;flex-wrap:wrap">
        <button id="diana-consent-yes" style="background:#1a6ce6;color:#fff;border:0;border-radius:8px;padding:.6rem 1.2rem;cursor:pointer;">Zustimmen und Chat starten</button>
        <button id="diana-consent-no"  style="background:#fff;border:1px solid #dbe5f1;border-radius:8px;padding:.6rem 1.2rem;cursor:pointer;">Abbrechen</button>
      </div>
    </div>
  `;

  document.getElementById("diana-consent-yes").onclick = () => {
    localStorage.setItem(KEY, "yes");
    localStorage.setItem(KEY_AT, String(Date.now()));
    location.reload();
  };
  document.getElementById("diana-consent-no").onclick = () => {
    localStorage.setItem(KEY, "no");
    localStorage.setItem(KEY_AT, String(Date.now()));
    wrap.innerHTML = "<p style='text-align:center;padding:1rem;'>Chat deaktiviert. Du kannst die Einwilligung später erteilen.</p>";
  };
});
