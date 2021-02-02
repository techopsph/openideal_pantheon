CKEditor Mentions
=================
Ports D7 version of module to work with Core CKEditor implementation using
plugin structure

- Relies on Real Name module to provide basis of name matching.
- Adds a 20x20 image style called mentions_icon that can be customized to
  desired settings.
- JSON callback route is attached to a permission setting to prevent exposure
  to all and sundry.
- In D8 each text format can choose to have mentions enabled -
  eg: /admin/config/content/formats/manage/basic_html
- For each text format, can set whether or not a user's image icon can be
  displayed beside their name
