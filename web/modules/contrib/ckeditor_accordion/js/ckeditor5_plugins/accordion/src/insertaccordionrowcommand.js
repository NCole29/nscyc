/**
 * @file defines InsertAccordionRowCommand.
 */
// cSpell:ignore accordionediting

import { Command } from 'ckeditor5/src/core';

/**
 * The insert accordion row command.
 *
 * @extends module:core/command~Command
 */
export default class InsertAccordionRowCommand extends Command {
  /**
   * Creates a new `InsertAccordionRowCommand` instance.
   *
   * @param {module:core/editor/editor~Editor} editor The editor on which this command will be used.
   * @param {Object} options
   * @param {String} [options.order="below"] The order of insertion relative to the row in which the caret is located.
   * Possible values: `"above"` and `"below"`.
   */
  constructor( editor, options = {} ) {
    super( editor );

    /**
     * The order of insertion relative to the row in which the caret is located.
     *
     * @readonly
     * @member {String} module:accordion/commands/insertaccordionrowcommand~InsertAccordionRowCommand#order
     */
    this.order = options.order || 'below';
  }

  execute() {
    const editor = this.editor;
    const selection = editor.model.document.selection;
    let commandEl = null;

    selection.getFirstPosition().getAncestors().forEach(ancestor => {
      if(ancestor.name == 'accordionContent' || ancestor.name == 'accordionTitle') {
        commandEl = ancestor;
      }
    });

    if(commandEl != null) {
      // Command is being run from a correct context.
      editor.model.change((writer) => {
        let position;
        if(this.order == 'below') {
          let insertAfterIndex = (commandEl.name == 'accordionContent') ? commandEl.index : commandEl.index + 1;
          if(insertAfterIndex < 0) {
            insertAfterIndex = 0;
          }
          // Add row below this row's accordionTitle.
          position = writer.createPositionAfter(commandEl.parent.getChild(insertAfterIndex));
        }
        else {
          let insertBeforeIndex = (commandEl.name == 'accordionContent') ? commandEl.index - 1 : commandEl.index;

          if(insertBeforeIndex < 0) {
            insertBeforeIndex = 0;
          }
          // Add row above this row's accordionTitle.
          position = writer.createPositionBefore(commandEl.parent.getChild(insertBeforeIndex));
        }

        // Create the accordion title and content and add em.
        const accordionTitle = writer.createElement('accordionTitle');
        const accordionContent = writer.createElement('accordionContent');

        // Create some default title.
        writer.insertText('Accordion title', accordionTitle);

        // Do the insert.
        writer.insert(accordionContent, position);
        writer.insert(accordionTitle, position);

        // Create some default content.
        const accordionContentParagraph = writer.createElement('paragraph');
        writer.appendText('Accordion content.', accordionContentParagraph);
        writer.insert(accordionContentParagraph, accordionContent);

        // Select the inserted title row.
        // TODO.
      });
    }
  }

  refresh() {
    this.isEnabled = true;
  }

}
