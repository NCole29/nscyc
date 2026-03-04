README.txt
==========

Allows users to create & display content in an accordion.
------------------------
Requires
- Drupal 9/10
- CKEditor 5


Overview:
--------
Adds a new button to Drupal's CKEditor 5, which allows the user
to create & display any type of content in an accordion format.

The styling is minimal blue and easily over writeable by developers.

Once the Accordion has been created, "ckeditorAccordionAttached" event
gets triggered on the accordion element.


INSTALLATION:
--------
1. Install & Enable the module
2. Open Administration > Configuration > Content authoring >
   Text formats and editors (admin/config/content/formats)
3. Edit a text format's settings (usually Basic HTML)
4. Drag n Drop the Accordion -button to the toolbar to show it to the editors
5. Review available options at /admin/config/content/ckeditor-accordion


TODO:
--------
- Balloon toolbar should hover over selected row
- Icons in the CKEditor 5 Balloon are not displaying
- The inserted title row should get selected after insertion
