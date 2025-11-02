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

