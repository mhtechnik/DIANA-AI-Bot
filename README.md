![DiANA Logo](https://zerap-germany.de/wp-content/uploads/2025/10/Dianaklein.png)

# DiANA – KI-Chat für WordPress

Leichtes, datenschutzfreundliches und vollständig anpassbares Chat-Plugin für WordPress – powered by OpenAI (Responses API)

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg?logo=wordpress)](#)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-8892bf.svg?logo=php)](#)
[![Lizenz: GPL v2](https://img.shields.io/badge/Lizenz-GPLv2-blue.svg)](LICENSE)

---

## ✨ Überblick

**DiANA** ist ein WordPress-Plugin, das einen modernen KI-Assistenten direkt auf deiner Website bereitstellt.  
Es nutzt die **OpenAI Responses API (GPT-5)** für Echtzeit-Dialoge und bietet:

- saubere **Markdown-Ausgabe**
- automatische Erkennung von **YouTube- und PDF-Links**
- **Prompt-Buttons** für Schnellaktionen
- **Tipp-Indikator** während der Antwort
- **Rate-Limit** und **Origin-Check**
- **DSGVO-Einwilligung** mit frei wählbarer Ablaufdauer (z. B. 30, 60 oder 90 Tage)
- vollständig **anpassbare Farben und Texte**
- **lokale Speicherung** des Chatverlaufs im Browser  

Keine Chat-Daten werden auf deinem Server gespeichert.

---

## 🚀 Funktionen

✅ OpenAI Responses API mit `input`-Payload  
✅ Markdown-Rendering (Überschriften, Listen, Code, Links)  
✅ YouTube-Erkennung mit Vorschaubild + Inline-Player  
✅ PDF-Erkennung und Inline-Viewer  
✅ Prompt-Buttons für vordefinierte Eingaben  
✅ Tipp-Indikator während der Antwort  
✅ Rate-Limit & Origin-Check integriert  
✅ DSGVO-Einwilligung mit Ablauf (30–90 Tage)  
✅ Anpassbare Farbpalette  
✅ Lokale Speicherung & Löschfunktion  

---
```
## 🧩 Verzeichnisstruktur
diana-ai-bot/
├─ diana-chat.php
├─ includes/
│ ├─ settings.php → Admin-Einstellungen & Farbauswahl
│ ├─ rest.php → REST-Proxy zur OpenAI-API
│ ├─ helpers.php → Rate-Limit- und Origin-Funktionen
│ ├─ curl-hardening.php → Timeout & Stabilität für API-Anfragen
│ └─ cleanup.php → tägliche Bereinigung alter Transients
├─ assets/
│ ├─ css/diana-chat.css → Layout & Styles
│ ├─ js/diana-chat.js → Chat-Logik (Markdown, PDF, YouTube)
│ ├─ js/diana-consent.js → Einwilligungsdialog (DSGVO)
│ └─ admin/colorpicker.js → Farbauswahl im Backend
└─ README.md
```

---

## ⚙️ Installation

1. Repository klonen oder ZIP herunterladen:
   ```bash
   git clone https://github.com/mhtechnik/DIANA-AI-Bot.git
2. Den Ordner diana-ai-bot nach
wp-content/plugins/ kopieren

3. Im WordPress-Backend „Diana Chat“ aktivieren

4. Unter Einstellungen → Diana Chat API-Key und Optionen setzen

5. Shortcode einfügen:
6. [diana_chat]

##🔧 Einstellungen im Backend

🔐 API
Feld	- Beschreibung
API Key	- Dein OpenAI-API-Schlüssel
Base URL	Optional, Standard: - https://api.openai.com
Modell	- z. B. gpt-5
Temperatur	- Optional (wird bei GPT-5 ignoriert)
Max Tokens	- Maximale Ausgabegröße
Stop-Sequenzen	- Kommagetrennte Liste von Stop-Wörtern

## 💬 Prompt
Definiert, wie DiANA spricht.
Du bist DiANA, eine ruhige Co-Moderatorin. Antworte klar und freundlich.

## 🎨 Farben
Alle Farben sind über den Adminbereich frei wählbar und werden als CSS-Variablen gesetzt.

Bereich	-- Standardfarbe
Primärfarbe	         --    #1a6ce6
Akzentfarbe	         --    #09a3e3
Dunkel	            --    #0e2a4a
Text	               --    #0b1220
Hintergrund	         --    #f7fafc
Rahmenlinie	         --    #dbe5f1
Eingabe-Hintergrund	--    #eef6ff

## 📄 DF-Regeln
Jede Zeile definiert eine Regel zur automatischen PDF-Einbettung:
/*Moderationszyklus|Agenda|Methoden*/i | Methoden-Sammlung | https://example.com/Methoden.pdf | https://example.com/thumb.png

## 🔒 Datenschutz & Einwilligung
Feld	                     Beschreibung
Einwilligungstext	         Text, der vor der ersten Nutzung angezeigt wird
Link zur Datenschutzseite	URL zur DSGVO-Seite
Einwilligungsdauer (Tage)	Gültigkeitsdauer, z. B. 30, 60 oder 90
Nach Ablauf wird der Nutzer erneut um Zustimmung gebeten.

## Beispiel-Screenshot
<p align="center"> <img src="https://zerap-germany.de/wp-content/uploads/2025/11/Chatdemo.png" width="600" alt="Screenshot DiANA Chat" /> </p>

## 🔍 REST-API-Schnittstelle
Pfad	                  Methode	   Beschreibung
/wp-json/diana/v1/chat	POST	      Weiterleitung zur OpenAI-API

{ "message": "Wie leite ich eine Gruppenentscheidung an?" }

{ "reply": "Hier sind drei Moderationsmethoden..." }

## 🧠 Sicherheit & Datenschutz
- Origin-Check verhindert Fremdzugriffe
- Rate-Limit: 5 Anfragen / 10 s und 120 / Stunde pro IP
- Keine Speicherung von Chat-Inhalten auf dem Server
- Cron-Job entfernt alte Transients täglich
- Einwilligungspflicht vor Nutzung
- Consent-Speicherung lokal (Ablauf nach konfigurierter Dauer)

## 🧰 Entwicklung
1. Lokale WordPress-Installation vorbereiten
2. Plugin in wp-content/plugins/ kopieren
3. Debug-Modus aktivieren:
   
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

4. JS → ES2020, PHP → PSR-12
5. Commits nach Conventional Commits
Beispiele:
feat: Einwilligungsdauer konfigurierbar gemacht
fix: Leere API-Antworten stabil abgefangen
docs: README aktualisiert

## 🧾 Versionsverlauf
Siehe CHANGELOG.md

## 🧑‍💻 Beiträge
Beiträge sind willkommen!
Lies bitte CONTRIBUTING.md

## 🔐 Sicherheit
Sicherheitsrelevante Hinweise bitte nicht öffentlich posten.
Melde potenzielle Schwachstellen vertraulich an:
📧 info@zerap-germany.de

## 🪪 Lizenz
Dieses Plugin steht unter der GNU General Public License v2.0 oder später.
DiANA Chat – WordPress-Plugin  
Copyright (C) 2025  
ZERAP Germany e. V.
➡ Vollständiger Lizenztext: LICENSE

## 🧭 Projekt-Infos
ZERAP Germany e. V.
🌐 https://www.zerap-germany.de
📍 Straße der Freundschaft 2, 15518 Steinhöfel
📧 info@zerap-germany.de
📞 +49 (0)33636 679 798

## ❤️ Danksagung
- OpenAI für die GPT-5 Responses API
- WordPress.org für das beste Plugin-Ökosystem
- Alle Mitwirkenden, die DiANA weiter verbessern
