/**
 * @file defines InsertAccordionCommand, which is executed when the accordion
 * toolbar button is pressed.
 */
// cSpell:ignore accordionediting

import { Command } from 'ckeditor5/src/core';

export default class InsertAccordionCommand extends Command {
  execute() {
    const { model } = this.editor;

    model.change((writer) => {
      // Insert <accordion>*</accordion> at the current selection position
      // in a way that will result in creating a valid model structure.
      model.insertContent(createAccordion(writer));
    });
  }

  refresh() {
    const { model } = this.editor;
    const { selection } = model.document;

    // Determine if the cursor (selection) is in a position where adding a
    // accordion is permitted. This is based on the schema of the model(s)
    // currently containing the cursor.
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'accordion',
    );

    // Additional check: if we're inside accordionContent, explicitly allow it
    const isInAccordionContent = selection.getFirstPosition().findAncestor('accordionContent');

    // If the cursor is not in a location where a accordion can be added, return
    // null so the addition doesn't happen.
    this.isEnabled = allowedIn !== null || isInAccordionContent !== null;
  }
}

function createAccordion(writer) {
  // Create an accordion.
  const accordion = writer.createElement('accordion');
  const accordionTitle = writer.createElement('accordionTitle');
  const accordionContent = writer.createElement('accordionContent');

  writer.append(accordionTitle, accordion);
  writer.append(accordionContent, accordion);

  writer.appendElement('paragraph', accordionContent);

  // Return the element to be added to the editor.
  return accordion;
}
