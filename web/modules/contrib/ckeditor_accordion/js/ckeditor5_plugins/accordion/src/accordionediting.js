import { Plugin } from 'ckeditor5/src/core';
import { toWidget, toWidgetEditable } from 'ckeditor5/src/widget';
import { Widget } from 'ckeditor5/src/widget';
import InsertAccordionCommand from './insertaccordioncommand';
import InsertAccordionRowCommand from './insertaccordionrowcommand';
import DeleteAccordionRowCommand from './deleteaccordionrowcommand';

// cSpell:ignore accordion insertsimpleboxcommand

/**
 * CKEditor 5 plugins do not work directly with the DOM. They are defined as
 * plugin-specific data models that are then converted to markup that
 * is inserted in the DOM.
 *
 * CKEditor 5 internally interacts with accordion as this model:
 * <accordion>
 *    <accordionTitle></accordionTitle>
 *    <accordionContent></accordionContent>
 * </accordion>
 *
 * Which is converted for the browser/user as this markup
 * <section class="simple-box">
 *   <h2 class="simple-box-title"></h1>
 *   <div class="simple-box-description"></div>
 * </section>
 *
 * This file has the logic for defining the accordion model, and for how it is
 * converted to standard DOM markup.
 */
export default class AccordionEditing extends Plugin {
  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'AccordionEditing';
  }

  static get requires() {
    return [Widget];
  }

  init() {
    this._defineSchema();
    this._defineConverters();
    this.editor.commands.add(
      'insertAccordion',
      new InsertAccordionCommand(this.editor),
    );

    this.editor.commands.add(
      'insertAccordionRowAbove',
      new InsertAccordionRowCommand(this.editor, { order: 'above' })
    );
    this.editor.commands.add(
      'insertAccordionRowBelow',
      new InsertAccordionRowCommand( this.editor, { order: 'below' } )
    );
    this.editor.commands.add(
      'deleteAccordionRow',
      new DeleteAccordionRowCommand( this.editor, {} )
    );

  }

  /*
   * This registers the structure that will be seen by CKEditor 5 as
   * <accordion>
   *    <accordionTitle></accordionTitle>
   *    <accordionContent></accordionContent>
   * </accordion>
   *
   * The logic in _defineConverters() will determine how this is converted to
   * markup.
   */
  _defineSchema() {
    // Schemas are registered via the central `editor` object.
    const schema = this.editor.model.schema;

    schema.register('accordion', {
      // Behaves like a self-contained object (e.g. an image).
      isObject: true,
      // Allow in places where other blocks are allowed (e.g. directly in the root).
      allowWhere: '$block',
    });

    schema.register( 'accordionRow', {
      allowIn: 'accordion',
      isLimit: true
    } );

    schema.register('accordionTitle', {
      // This creates a boundary for external actions such as clicking and
      // and keypress. For example, when the cursor is inside this box, the
      // keyboard shortcut for "select all" will be limited to the contents of
      // the box.
      isLimit: true,
      // This is only to be used within accordion.
      allowIn: 'accordion',
      // Allow content that is allowed in blocks (e.g. text with attributes).
      allowContentOf: '$block',
    });

    schema.register('accordionContent', {
      isLimit: true,
      allowIn: 'accordion',
      allowContentOf: '$root',
    });

    schema.extend('accordion', {
      allowIn: 'accordionContent'
    });
  }

  /**
   * Converters determine how CKEditor 5 models are converted into markup and
   * vice-versa.
   */
  _defineConverters() {
    // Converters are registered via the central editor object.
    const { conversion } = this.editor;

    // Upcast Converters: determine how existing HTML is interpreted by the
    // editor. These trigger when an editor instance loads.
    //
    // If <dl class="ckeditor-accordion"> is present in the existing markup
    // processed by CKEditor, then CKEditor recognizes and loads it as a
    // <accordion> model.
    conversion.for('upcast').elementToElement({
      model: 'accordion',
      view: {
        name: 'dl',
        classes: 'ckeditor-accordion',
      },
    });

    // If <dt> is present in the existing markup
    // processed by CKEditor, then CKEditor recognizes and loads it as a
    // <accordionTitle> model, provided it is a child element of <accordion>,
    // as required by the schema.
    conversion.for('upcast').elementToElement({
      model: 'accordionTitle',
      view: {
        name: 'dt',
        classes: '',
      },
    });

    // If <dd> is present in the existing markup
    // processed by CKEditor, then CKEditor recognizes and loads it as a
    // <accordionContent> model, provided it is a child element of
    // <accordion>, as required by the schema.
    conversion.for('upcast').elementToElement({
      model: 'accordionContent',
      view: {
        name: 'dd',
        classes: '',
      },
    });

    // Data Downcast Converters: converts stored model data into HTML.
    // These trigger when content is saved.
    //
    // Instances of <accordion> are saved as
    // <dl class="ckeditor-accordion">{{inner content}}</dl>.
    conversion.for('dataDowncast').elementToElement({
      model: 'accordion',
      view: {
        name: 'dl',
        classes: 'ckeditor-accordion',
      },
    });

    // Instances of <accordionTitle> are saved as
    // <dt>{{inner content}}</dt>.
    conversion.for('dataDowncast').elementToElement({
      model: 'accordionTitle',
      view: {
        name: 'dt',
        classes: '',
      },
    });

    // Instances of <accordionContent> are saved as
    // <dd>{{inner content}}</dd>.
    conversion.for('dataDowncast').elementToElement({
      model: 'accordionContent',
      view: {
        name: 'dd',
        classes: '',
      },
    });

    // Editing Downcast Converters. These render the content to the user for
    // editing, i.e. this determines what gets seen in the editor. These trigger
    // after the Data Upcast Converters, and are re-triggered any time there
    // are changes to any of the models' properties.
    //
    // Convert the <accordion> model into a container widget in the editor UI.
    conversion.for('editingDowncast').elementToElement({
      model: 'accordion',
      view: (modelElement, { writer: viewWriter }) => {
        const div = viewWriter.createContainerElement('div', {
          class: 'ckeditor-accordion',
        });

        return toWidget(div, viewWriter);
      },
    });

    // Convert the <accordionTitle> model into an editable <h2> widget.
    conversion.for('editingDowncast').elementToElement({
      model: 'accordionTitle',
      view: (modelElement, { writer: viewWriter }) => {
        const h2 = viewWriter.createEditableElement('div', {
          class: 'ckeditor-accordion-title',
        });
        return toWidgetEditable(h2, viewWriter);
      },
    });

    // Convert the <accordionContent> model into an editable <div> widget.
    conversion.for('editingDowncast').elementToElement({
      model: 'accordionContent',
      view: (modelElement, { writer: viewWriter }) => {
        const div = viewWriter.createEditableElement('div', {
          class: 'ckeditor-accordion-content',
        });
        return toWidgetEditable(div, viewWriter);
      },
    });
  }
}
