# Changelog

## 1.3.35 (2026-10-29)

- Updated: One copyright notice, Exponential Foundation and 7x.

## 1.3.34 (2026-10-29)

- Updated: The admin stylesheets use the shared admin tokens for weights, durations, radii, table headers and muted text, with the old values as fallbacks where a design has no tokens.

## 1.3.33 (2026-10-28)

- Added: Translations into 59 more languages: aka-GH, arc-IQ, asm-IN, aze-AZ, bel-BY, bho-IN, bod-CN, ceb-PH, che-RU,
  ckb-IQ, cym-GB, eng-AU, eus-ES, fil-PH, ful-SN, gla-GB, glg-ES, haw-US, huy-IL, hye-AM, ind-ID, jav-ID, kat-GE,
  kaz-KZ, khm-KH, kik-KE, kin-RW, kir-KG, lao-LA, lin-CD, ltz-LU, lug-UG, mai-IN, mkd-MK, mlg-MG, mon-MN, mri-NZ,
  msa-MY, mya-MM, nep-NP, orm-ET, pcm-NG, pnb-PK, run-BI, sin-LK, sna-ZW, snd-PK, sun-ID, syr-IQ, tat-RU, tgk-TJ,
  tha-TH, tir-ET, tuk-TM, uig-CN, uzb-UZ, vie-VN, wol-SN and yid-US.
- Updated: The afr-ZA, amh-ET, ara-SA, ben-BD, bos-BA, bul-BG, cat-ES, chi-CN, chi-TW, cro-HR, cze-CZ, dan-DK, dsb-DE,
  dut-NL, ell-GR, eng-US, esl-ES, esl-MX, est-EE, fas-IR, fin-FI, fre-FR, ger-DE, gle-IE, guj-IN, hau-NG, heb-IL,
  hin-IN, hun-HU, ibo-NG, isl-IS, ita-IT, jpn-JP, kal-GL, kan-IN, kor-KR, kur-TR, lat-VA, lav-LV, lit-LT, mal-IN,
  mar-IN, mlt-MT, nor-NO, ori-IN, pan-IN, pol-PL, por-BR, por-PT, pus-AF, ron-RO, rus-RU, ser-SR, slk-SK, slv-SI,
  som-SO, sqi-AL, srp-RS, swa-KE, swe-SE, tam-IN, tel-IN, tlh-QO, tur-TR, ukr-UA, urd-PK, xho-ZA, yor-NG and zul-ZA
  translations are complete.
- Updated: The layout editor shows the names of block types and their groups in the interface language.

## 1.3.32

- Added: Chinese translations in Simplified (chi-CN) and Traditional (chi-TW) script; chi-HK falls back to chi-TW,
  from ekked's pull request #18.
- Added: Translations into seventeen languages from South Asia and Africa: Bengali, Urdu, Marathi, Telugu, Tamil,
  Gujarati, Kannada, Malayalam, Punjabi, Odia, Hausa, Amharic, Yoruba, Igbo, isiZulu, Somali and isiXhosa, from
  ekked's pull request #17.
- Added: Russian, Catalan, Serbian (Cyrillic and Latin), Bulgarian, Lithuanian, Portuguese (Portugal) and Afrikaans
  translations, from ekked's pull request #16.
- Added: Translations into eighteen more administration languages: Hindi, Korean, Bosnian, Lower Sorbian, Estonian,
  Irish, Icelandic, Greenlandic, Latvian, Hungarian, Maltese, Romanian, Albanian, Slovak, Slovenian, Japanese, Greek
  and Finnish, from ekked's pull request #15.

## 1.3.31

- **"+ Add items" is a button.** The trigger of the content browser in a collection of the sidebar was a `<div>`: it
  could not be reached with Tab, and Enter and Space did nothing. It is a `<button type="button">` now, styled as
  before with a visible focus ring. The dialog gives the focus back to it when it closes, also when the collection
  was drawn again meanwhile (after items were added).
- **Opening a layout without a draft no longer logs a failed request.** The editor asks for the draft with
  `fallback=published`; a layout without a draft answers with its published version instead of a 404, and the
  editor creates the draft as before. `GET layouts/<id>?published=false` without the parameter still answers 404
  when there is no draft.
- Polish buttons and menu items use the infinitive, as the administration of Exponential does.

## 1.3.30

- **The layout editor loads no jQuery.** The editor page (`app.tpl`) no longer loads ezjscore's jQuery 4 (nor jQuery
  UI, see below); the app bundle's module `jquery` is now the editor's own DOM layer: exactly the part of jQuery the
  bundle calls (collections, events with delegation and Backbone's namespaces, data, `$.ajax` with its global events,
  `$.Deferred`, `$.when` ...) on plain DOM, with jQuery's behaviour. Backbone's views, events and requests run on it,
  and nothing changes for the user: every flow of the editor was compared request by request and DOM by DOM with
  jQuery 4 (`tests/js/editor-without-jquery.test.mjs`). With the libraries gone from the bundle (below) the editor
  loads 529 KB less script, 143 KB gzipped: the bundle went from 932 KB to 737 KB (gzipped 253 KB to 206 KB), and
  jQuery 4 (79 KB) and jQuery UI (255 KB, minified) are no longer loaded. `window.jQuery` is no longer set: scripts
  added through `[App] Javascripts[]` must bring their own.
- A Backbone view is kept on its element as `el.ngl_view` only (jQuery's data `_view` is that property now);
  `javascript/explayouts-app.js` finds the collection of "+ Add items" there, sends its requests through the app's
  `Core.$.ajax` and shows the content browser as one of the app's dialogs (`Core.ui.dialog`) instead of a copy of
  its own.
- The menus of the header and of a block are opened by the app's dropdown code alone: `aria-expanded` now follows
  (a handler of the page opened them before it and left `aria-expanded` at `false`).
- The page no longer makes block types natively draggable (HTML5 drag and drop with a request of its own); block
  types are dragged by the editor's drag and drop only.
- An item of a manual collection with an offset that is put back where it was (dropped on its own place, Escape) is
  no longer sent to the server as moved.
- **The editor page's own scripts run without jQuery.** The content browser dialog of "+ Add items" and of the
  browse fields of a block form (`javascript/explayouts-app.js`) is plain DOM: it no longer needs jQuery or
  Bootstrap's modal plugin, and shows and hides itself as that plugin did (fade of backdrop and dialog, Escape, a
  click beside the dialog, the focus kept inside while it is open). Closing it now returns the focus to the link or
  button it was opened from. Every text of the dialog, the translated ones too, is escaped through one helper that
  also escapes quotes. A browse field tells the block form about a change with a native `change` event, which
  listeners added with `addEventListener` now get as well. Its requests go through the app's request function
  (progress bar, session timeout dialog, warning on leaving). `explayouts-app-head.js` and the Tiptap glue (`src/editors/`) did not use jQuery.
- **Dialogs, collapsible panels, dropdown menus, tooltips, the date field and the inline switches of the sidebar
  run without jQuery plugins.** Bootstrap 3's JavaScript (modal, collapse, dropdown, transition), Moment.js with its
  locales and eonasdan-bootstrap-datetimepicker are no longer in the editor bundle; small DOM code of this package
  takes their place (`Core.ui`, see README) with the same markup, classes, transitions and keys, so the stylesheets
  apply as before. A dialog opened again while it is still closing (Cancel, then "+ Add items" at once) simply stays
  open, with one backdrop.
- Dialogs say what they are (`role="dialog"`, `aria-modal`, named by their title, `aria-hidden` when closed), Tab and
  Shift+Tab stay inside an open dialog, and on closing the focus goes back to where it was.
- The Options, Items and Advanced panels of the sidebar fold and unfold on a click again: under jQuery 4 Bootstrap's
  collapse stopped with an error on the first click.
- Dropdown menus: the toggles say they are closed (`aria-expanded="false"`) until opened. Escape closes an open
  menu also when the focus has moved on (a block's menu opened with the mouse selects the block, and its rich text
  editor takes the focus).
- The date and time field of a block form is the browser's own (`<input type="datetime-local">`); it sends the same
  value as before (`YYYY-MM-DDTHH:mm`, local time). The date in the draft dialog is formatted with
  `Intl.DateTimeFormat`: the same text as before in English, German, French and Italian, in other languages the
  browser's conventions.
- **Drag and drop without jQuery UI, by mouse, finger and pen.** Moving blocks in and between zones, block types
  from the toolbar into a zone, blocks onto the trash and the items of a collection now run on the editor's own drag
  and drop (Pointer Events), and the page no longer loads jQuery UI. What is dragged where, and what is saved, is as
  before: zones of a shared layout and zones that do not take a block type still refuse it, the trash still asks.
  New: **Escape** cancels a drag and puts everything back; touch and pen work on every screen size (until now only
  below 820px, through a bridge in `explayouts-mobile.js` that is gone); a finger on a grip (a block's header, an
  item's handle, a block type) moves it, also after the block was drawn anew, a finger anywhere else scrolls the
  page; the canvas keeps scrolling while the pointer stays near its edge, and the block being dragged moves along
  with what scrolls by. In a dynamic collection the slots light up again while an item is dragged (the
  classes `sorting` and `highlight`, which jQuery UI 1.14 no longer set), and an item dropped on no slot leaves no
  preview behind.
- **Blocks, block types and collection items move with the keyboard.** Their grips are buttons now (a block's
  handle, a block type of the toolbar, an item's handle): Space or Enter picks up, the arrow keys move one place (at
  a zone's edge into the zone before or after it), Home and End go to the first and last place, Space or Enter
  drops, Escape or leaving the grip puts everything back; Alt with the up or down arrow moves one place at once.
  Linked zones and zones that do not take the block are skipped, as with the pointer. A block type goes from the
  toolbar into the first zone that takes it and becomes a new block where it is dropped. Each step is said by screen
  readers in one polite live region, translated in every language of the package; after a drop the grip keeps the
  focus, until you click, type or tab anywhere else. A keyboard move is saved exactly as one made with the pointer.
  A move put back with Escape is announced as "Move canceled." (American English, as the rest of the interface).
- **Swahili (Kenya)** (`swa-KE`): a new translation of the whole editor, the texts of the keyboard moves included.
- Translations that contradicted their language guide were corrected (wrong meanings, glossary breaches, forms of
  address, typos and English left in the text) in esl-ES, esl-MX, fas-IR, heb-IL, kur-TR, pol-PL, por-BR, pus-AF
  and ukr-UA.

## 1.3.29

- **Design tab: changing only the View type of a list is saved** also when the new view type leaves no item view
  type to choose (`list_zigzag`, `list_accordion`, a view type of your own). The View type of a list is the master of
  the Item view type select, and its change was saved only through the change it made to the item view type; with
  none left, the block kept its old view type until another field of the form changed.
- **"+ Add items" gives every new item a position of its own.** The editor sends the chosen items at the
  collection's offset, and each was stored at exactly that position, so two items chosen together ended up at
  position 0 next to the item already there, in the reverse order. Now each goes in at its position and the items
  from there on move down: the new items come first, in the order chosen. Moving an item (drag in **Items**, *Set
  position*) moves the items in between the same way. A manual collection keeps its positions 0, 1, 2 ... without
  gaps (positions shared by items stored before are sorted out on the next change); in a dynamic collection, where
  a manual item is pinned to a slot of the query's results, the slots are kept and only the run of pinned items
  behind the slot taken moves down.

## 1.3.28

- **Imports create a draft** with draft zones and blocks; rules come along disabled with known types only; layout
  type, block definitions and view types are checked against the settings (with explayouts' importer rules when
  available); one transaction; a refused export answers 400 with the reason. A zone links to a shared layout by its
  identifier only. Both export forms import; the export carries `linked_layout_identifier`.
- The rule endpoints keep only known target and condition types.
- The editor returns only to a page of this site (`return_to`, the kept referrer).
- Cancel of the new layout dialog no longer creates the layout.
- Design tab: a parameter of the type `boolean` is a checkbox (it was a text field); number fields take `min` and
  `max` from the parameter. The new parameters, options and view type names of explayouts' blocks (parity with
  netgen/layouts-standard) are translated in every catalogue.
- **Content tab as in the reference**: the parameters of the group "content" (explayouts' `[BlockDefinition_<id>]
  ContentParameters[]`: text, links, images, video, map) are edited in the Content tab with the block label and the
  collection, the others in the Design tab (`expLayoutsUIApplicationApi::blockFormParameters()`); the Content tab
  opens first when it has parameters. Without the list every parameter stays in the Design tab.
- A select keeps a stored value that is none of its options as an option of its own, "(saved) <value>"; the next
  auto-submit no longer replaces it with the first option.
- Item view types and the view types of master/slave selects are named and translated; the catalogues translate
  every parameter text of explayouts' blocks.
- The changes to the app bundle are versioned: `src/app-patches/` holds the modules that differ from netgen/layouts-ui
  2.0.0, `apply.mjs` builds the bundle from them, `tests/js/app-patches.test.mjs` checks it byte for byte.
- Leaving the layout editor asks only when something can still be lost (a running write, typing in a block within the
  last second, a form of the sidebar or of a dialog that is not saved), no longer right after opening a layout.
- An inline text in the canvas that is emptied completely is saved (it was skipped); a form without a method attribute
  is saved as POST.
- The content browser of the layout editor escapes class and object names, quotes included; ids are read as numbers.
- The gallery block preview, the Design tab, view type saving and the canvas preview (the block's own template,
  rebuilt from an allowlist) are fixed.
- Arabic, French, Ukrainian, Persian, Pashto and Kurdish (Kurmanji) translations; the ten translations of 1.3.27 and
  the new ones carry every parameter text of the editor.
- The accessible tabs, palette and toolbar buttons of 1.3.22 are kept in the bundle as patches of modules 17, 54 and
  85; the safe return target and the Cancel link live in the page's script files (no inline script).

Open: the import is still a second implementation beside explayouts' `expLayoutsImporter` (shared are the
validation and the handling of rules).
