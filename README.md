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

diana-ai-bot/
├─ diana-chat.php
├─ includes/
│  ├─ settings.php
│  ├─ rest.php
│  ├─ helpers.php
│  ├─ curl-hardening.php
│  └─ cleanup.php
├─ assets/
│  ├─ css/
│  │  └─ diana-chat.css
│  ├─ js/
│  │  ├─ diana-chat.js
│  │  └─ diana-consent.js
│  └─ admin/
│     └─ colorpicker.js
└─ README.md


Kurz erklärt

diana-chat.php: Plugin Bootstrap, Assets, Shortcode, WP Cron Hooks

includes/settings.php: Backend-Formular und Optionen

includes/rest.php: REST Proxy zu OpenAI, Rate-Limit, Origin-Check

includes/helpers.php: Hilfsfunktionen wie Rate-Limit Keys

includes/curl-hardening.php: Zeitlimits, Retries, saubere Fehler

includes/cleanup.php: tägliche Bereinigung von Transients und optional debug.log

assets/js/diana-chat.js: UI Logik, Markdown, YouTube, PDF, Persistenz

assets/js/diana-consent.js: Consent-Dialog, Ablauf in Tagen

assets/css/diana-chat.css: Styles, responsive Typografie, Farben

Anforderungen

WordPress 6.0 oder neuer

PHP 8.0 oder neuer

OpenAI API Key

HTTPS aktiv

Konfiguration im Backend
API

API Key: dein OpenAI Key

Base URL: optional, Standard ist https://api.openai.com

Model: z. B. gpt-5

Temperatur: Zahl, greift nicht bei allen Modellen

Max Tokens: Responses Feld max_output_tokens

Stop Sequenzen: kommagetrennt

Prompt

System Prompt: z. B. Rolle und Tonalität

UI

Name im UI: z. B. DiANA

Avatar URL: rundes Bild

Begrüßung: erster Bot-Text

Prompt Buttons: kommagetrennte Liste

PDF-Guides

Jede Zeile: Regex | Titel | https://.../leitfaden.pdf | optionales Thumbnail
Beispiel:

/*Moderationszyklus|Agenda|Methoden*/i | Methoden-Sammlung | https://example.com/Methoden.pdf

Farben

Primär, Akzent, Dunkel, Text, Hintergrund, Rahmenlinie, Eingabe Hintergrund
Alle als HEX, werden als CSS Variablen injiziert.

Datenschutz

Einwilligungstext: wird im Consent-Dialog angezeigt

Link zur Datenschutzseite: z. B. /datenschutz/

Einwilligungsdauer (Tage): z. B. 30, 60, 90

REST Proxy

Pfad: POST /wp-json/diana/v1/chat
Header: Content-Type: application/json, Diana-Origin: <window.location.origin>

Body:
{ "message": "Deine Frage" }

Antwort:
{ "reply": "Antwort-Text" }

Fehler:
{ "error": "Beschreibung" }

Sicherheit

Origin-Check: nur dieselbe Site darf posten

Rate-Limit: 10 s Burst und stündlich per Transients

Timeouts: cURL Zeitlimit, Retry ohne Temperatur oder Stop, wenn API das nicht erlaubt

Keine Server-Logs der Chats: nur kurzlebige Transients

DSGVO

Consent Pflicht vor dem ersten Request

Consent-Lebensdauer in Tagen konfigurierbar, Standard 30

Link zur Datenschutzseite im Dialog

Lokaler Verlauf im Browser, kann über UI gelöscht werden

Server-Logs durch Hoster, empfohlen 30 Tage

Troubleshooting

cURL error 28, Timeout

Prüfe Firewall und openai.com Erreichbarkeit

Erhöhe PHP default_socket_timeout und WP HTTP Timeout

Prüfe includes/curl-hardening.php und ggf. Timeout anheben

Log-Level in Produktion gering halten

HTTP 500

PHP Error Log prüfen

Fehlende PHP Extensions oder Syntaxfehler ausschließen

WP_DEBUG_DISPLAY auf false, WP_DEBUG_LOG an

JSON.parse: unexpected character

API gibt HTML oder Plain-Text zurück

REST Proxy weicht automatisch auf Text aus und formatiert Fehler

Browser Cache leeren, prüfen ob Plugin doppelt eingebunden ist

Fehler: empty response

Responses API gab leere Ausgabe zurück

Client versucht einmal neu

Falls wieder leer: API Limits oder Model prüfen

Roadmap

Streaming der Antworten

Datei-Uploads mit Embedding-Store

Mehrsprachige UI per i18n

Tests und CI

Entwicklung

Repo klonen

In WordPress als Plugin-Ordner ablegen

PHP Code Style: PSR-12

JS Stil: ES2020, keine Frameworks, nur DOM API

Commits nach Conventional Commits

Beiträge

Pull Requests willkommen. Lies bitte CONTRIBUTING.md und die PR-Vorlage.

Lizenz

GPL-2.0-or-later. Siehe LICENSE.

Maintainer
ZERAP Germany e. V., Community


---

## CONTRIBUTING.md

```markdown
# Contributing

Danke für deinen Beitrag.

## Wie starten
- Fork erstellen
- Branch vom aktuellen `main` abspalten
- Konventionelle Commits nutzen: feat, fix, docs, chore, refactor
- PHP nach PSR-12, JS ohne Linter-Warnungen

## Dev-Setup
- WordPress lokal
- Ordner als `wp-content/plugins/diana-ai-bot`
- Debug: `WP_DEBUG` true, `WP_DEBUG_LOG` true

## Tests
- Manuelle Tests in einer leeren Seite mit `[diana_chat]`
- Szenarien: Consent neu, Ablauf nach X Tagen, Timeout, YouTube, PDF-Regel

## PR-Richtlinien
- Issue referenzieren
- Kurze Beschreibung was und warum
- Screenshots bei UI Änderungen

