/**
 * @file defines DeleteAccordionRowCommand.
 */
// cSpell:ignore accordionediting

import { Command } from 'ckeditor5/src/core';

/**
 * The delete accordion row command.
 *
 * @extends module:core/command~Command
 */
export default class DeleteAccordionRowCommand extends Command {
  /**
   * Creates a new `DeleteAccordionRowCommand` instance.
   *
   * @param {module:core/editor/editor~Editor} editor The editor on which this command will be used.
   * @param {Object} options
   */
  constructor( editor, options = {} ) {
    super( editor );
  }

  execute() {
    const editor = this.editor;
    const selection = editor.model.document.selection;
    let elToDelete = null;

    selection.getFirstPosition().getAncestors().forEach(ancestor => {
      if(ancestor.name == 'accordionContent' || ancestor.name == 'accordionTitle') {
        elToDelete = ancestor;
      }
    });

    if(elToDelete != null) {
      // Command is being run from a correct context.
      editor.model.change((writer) => {
        let siblingElToDelete, siblingIndex;
        if(elToDelete.name == 'accordionContent') {
          // Sibling is accordionTitle.
          siblingIndex = elToDelete.index - 1;
        }
        else {
          // Sibling is accordionContent.
          siblingIndex = elToDelete.index + 1;
        }
        siblingElToDelete = elToDelete.parent.getChild(siblingIndex);

        // Remove elements.
        writer.remove(elToDelete);
        writer.remove(siblingElToDelete);
      });
    }
  }

  refresh() {
    this.isEnabled = true;
  }

}
