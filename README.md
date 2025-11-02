<<<<<<< HEAD
# DiANA Chat – WordPress Plugin

DiANA ist ein leichtes Chat-Widget für WordPress. Es nutzt die OpenAI Responses API, rendert Markdown, erkennt YouTube-Links, zeigt PDFs im Inline-Viewer, hat Prompt-Buttons, Tipp-Indicator, Rate-Limit, Origin-Check und eine DSGVO-Einwilligung mit einstellbarem Ablauf in Tagen. Farben, Texte und Regeln sind im Backend konfigurierbar.

## Features

- OpenAI Responses API mit `input` Payload
- Markdown-Ausgabe im Chat mit Listen, Code, Links
- YouTube-Erkennung mit Vorschaubild und Click-to-Play Embed
- PDF-Karten mit Button und Inline-Viewer
- Prompt-Buttons im UI
- Tipp-Indicator
- Rate-Limit: Burst pro 10 s und pro Stunde
- Origin-Check gegen `home_url`
- DSGVO-Consent mit Link zur Datenschutzseite und Ablauf nach X Tagen
- Freie Farbwahl im Backend inkl. Eingabe-Hintergrund
- Lokaler Chatverlauf im Browser, optionaler Auto-Cleanup

## Quickstart

1. Repo klonen oder ZIP installieren
2. Ordner `diana-ai-bot` in `wp-content/plugins` kopieren
3. Plugin im WP Backend aktivieren
4. Unter **Einstellungen → Diana Chat** API Key und Optionen setzen
5. Shortcode `[diana_chat]` in eine Seite einfügen

## Shortcode

```text
[diana_chat]
=======
# <div align="center">

# &nbsp; <img src="assets/logo-diana.png" alt="DiANA Chat Logo" width="120" height="120"/>

# &nbsp; <h1>DiANA – KI-Chat für WordPress</h1>

# &nbsp; <p><strong>Leichtes, datenschutzfreundliches und vollständig anpassbares Chat-Plugin für WordPress – powered by OpenAI (Responses API)</strong></p>

# 

# &nbsp; \[!\[WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg?logo=wordpress)](#)

# &nbsp; \[!\[PHP](https://img.shields.io/badge/PHP-8.0%2B-8892bf.svg?logo=php)](#)

# &nbsp; \[!\[Lizenz: GPL v2](https://img.shields.io/badge/Lizenz-GPLv2-blue.svg)](LICENSE)

# &nbsp; \[!\[Status](https://img.shields.io/badge/Status-aktiv-brightgreen.svg)](#)

# </div>

# 

# ---

# 

# \## ✨ Überblick

# 

# \*\*DiANA\*\* ist ein WordPress-Plugin, das einen modernen KI-Assistenten direkt auf deiner Website bereitstellt.  

# Es nutzt die \*\*OpenAI Responses API (GPT-5)\*\* für Echtzeit-Dialoge und bietet:

# 

# \- saubere \*\*Markdown-Ausgabe\*\*

# \- automatische Erkennung von \*\*YouTube- und PDF-Links\*\*

# \- \*\*Prompt-Buttons\*\* für Schnellaktionen

# \- \*\*Tipp-Indikator\*\* während der Antwort

# \- \*\*Rate-Limit\*\* und \*\*Origin-Check\*\*

# \- \*\*DSGVO-Einwilligung\*\* mit frei wählbarer Ablaufdauer (z. B. 30, 60 oder 90 Tage)

# \- vollständig \*\*anpassbare Farben und Texte\*\*

# \- \*\*lokale Speicherung\*\* des Chatverlaufs im Browser  

# 

# Keine Chat-Daten werden auf deinem Server gespeichert.

# 

# ---

# 

# \## 🚀 Funktionen

# 

# ✅ OpenAI Responses API mit `input` Payload  

# ✅ Markdown-Rendering (Überschriften, Listen, Code, Links)  

# ✅ YouTube-Erkennung mit Vorschaubild + Inline-Player  

# ✅ PDF-Erkennung und Inline-Viewer  

# ✅ Prompt-Buttons für vordefinierte Eingaben  

# ✅ Tipp-Indikator während der Antwort  

# ✅ Rate-Limit \& Origin-Check integriert  

# ✅ DSGVO-Einwilligung mit Ablauf (30–90 Tage)  

# ✅ Anpassbare Farbpalette  

# ✅ Lokale Speicherung \& Löschfunktion  

# 

# ---

# 

# \## 🧩 Verzeichnisstruktur

# 
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

---

## ⚙️ Installation

1. Repository klonen oder ZIP herunterladen:
   ```bash
   git clone https://github.com/mhtechnik/DIANA-AI-Bot.git

    Den Ordner diana-ai-bot nach
    wp-content/plugins/ kopieren

    Im WordPress-Backend „Diana Chat“ aktivieren

    Unter Einstellungen → Diana Chat API-Key und Optionen setzen

    Den Shortcode einfügen:

    [diana_chat]

🔧 Einstellungen im Backend
🔐 API
Feld	Beschreibung
API Key	Dein OpenAI-API-Schlüssel
Base URL	Optional, Standard: https://api.openai.com
Modell	z. B. gpt-5
Temperatur	Optional (wird bei GPT-5 ignoriert)
Max Tokens	Maximale Ausgabegröße
Stop-Sequenzen	Kommagetrennte Liste von Stop-Wörtern
💬 Prompt

Definiert, wie DiANA spricht.
Beispiel:

Du bist DiANA, eine ruhige Co-Moderatorin. Antworte klar und freundlich.

🎨 Farben

Alle Farben sind über den Adminbereich frei wählbar und werden als CSS-Variablen gesetzt.
Bereich	Standardfarbe
Primärfarbe	#1a6ce6
Akzentfarbe	#09a3e3
Dunkel	#0e2a4a
Text	#0b1220
Hintergrund	#f7fafc
Rahmenlinie	#dbe5f1
Eingabe-Hintergrund	#eef6ff
📄 PDF-Regeln

Jede Zeile definiert eine Regel zur automatischen PDF-Einbettung:

/*Moderationszyklus|Agenda|Methoden*/i | Methoden-Sammlung | https://example.com/Methoden.pdf | https://example.com/thumb.png

🔒 Datenschutz & Einwilligung
Feld	Beschreibung
Einwilligungstext	Text, der vor der ersten Nutzung angezeigt wird
Link zur Datenschutzseite	URL zur DSGVO-Seite
Einwilligungsdauer (Tage)	Gültigkeitsdauer, z. B. 30, 60 oder 90

Nach Ablauf wird der Nutzer erneut um Zustimmung gebeten.
💡 Beispiel-Screenshot
<p align="center"> <img src="assets/screenshots/diana-chat-example.png" width="600" alt="Screenshot DiANA Chat" /> </p>
🔍 REST-API-Schnittstelle
Pfad	Methode	Beschreibung
/wp-json/diana/v1/chat	POST	Weiterleitung zur OpenAI-API

Beispiel-Request

{ "message": "Wie leite ich eine Gruppenentscheidung an?" }

Beispiel-Response

{ "reply": "Hier sind drei Moderationsmethoden..." }

🧠 Sicherheit & Datenschutz

    Origin-Check verhindert Fremdzugriffe

    Rate-Limit: 5 Anfragen / 10 s und 120 / Stunde pro IP

    Keine Speicherung von Chat-Inhalten auf dem Server

    Cron-Job entfernt alte Transients täglich

    Einwilligungspflicht vor Nutzung

    Consent-Speicherung lokal (Browser, Ablauf nach konfigurierter Dauer)

🧰 Entwicklung

    Lokale WordPress-Installation vorbereiten

    Plugin in wp-content/plugins/ kopieren

    Debug-Modus aktivieren:

define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

JS → ES2020, PHP → PSR-12

Commits nach Conventional Commits


Beispiel:

    feat: Einwilligungsdauer konfigurierbar gemacht
    fix: Leere API-Antworten stabil abgefangen
    docs: README aktualisiert

🧾 Versionsverlauf

Siehe CHANGELOG.md
🧑‍💻 Beiträge

Beiträge sind willkommen!
Lies bitte CONTRIBUTING.md

für Hinweise zu Code-Stil und Pull-Requests.
🔐 Sicherheit

Sicherheitsrelevante Hinweise bitte nicht öffentlich posten.
Melde potenzielle Schwachstellen vertraulich an:
📧 security@zerap-germany.de
Weitere Infos in SECURITY.md
🪪 Lizenz

Dieses Plugin steht unter der GNU General Public License v2.0 oder später.

DiANA Chat – WordPress-Plugin  
Copyright (C) 2025  
Thierbachshof / ZERAP Germany e.V.

Dieses Programm ist freie Software; Sie können es unter den Bedingungen
der GNU General Public License weitergeben und/oder modifizieren.

➡ Vollständiger Lizenztext: LICENSE
🧭 Projekt-Infos

ZERAP Germany e.V.
🌐 https://www.zerap-germany.de

📍 Straße der Freundschaft 2, 15518 Steinhöfel
📧 info@zerap-germany.de


📞 +49 (0)33636 679 798
❤️ Danksagung

    OpenAI

– für die GPT-5 Responses API

WordPress.org

    – für das beste Plugin-Ökosystem

    Alle Mitwirkenden, die DiANA weiter verbessern

<div align="center"> <sub>Entwickelt mit ☕ und 🌾 auf dem Thierbachshof in Brandenburg</sub> </div> ```
🗂️ Empfohlene Zusatzdateien im Repo
Datei	Zweck
LICENSE	GPL-2.0-Text
CHANGELOG.md	Versionen & Änderungen
CONTRIBUTING.md	Hinweise für Mitwirkende
SECURITY.md	Meldeverfahren für Sicherheitsprobleme
assets/screenshots/	Screenshots der UI
assets/logo-diana.png	Logo für GitHub-Header

>>>>>>> 276f471 (docs: deutsche Readme & Begleitdateien hinzugefügt)

