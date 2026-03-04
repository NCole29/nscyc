/**
 * @file registers the accordion toolbar button and binds functionality to it.
 */

import { Plugin } from 'ckeditor5/src/core';
import {
  ButtonView,
  ContextualBalloon,
} from 'ckeditor5/src/ui';
import icon from '../../../../icons/ckeditor-accordion.svg';
import iconAddAbove from '../../../../icons/ckeditor-accordion-add-above.svg';
import iconAddBelow from '../../../../icons/ckeditor-accordion-add-below.svg';
import iconDelete from '../../../../icons/ckeditor-accordion-remove.svg';

export default class AccordionUI extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [ContextualBalloon];
  }

  init() {
    const editor = this.editor;
    const t = this.editor.t;

    // This will register the accordion toolbar button.
    editor.ui.componentFactory.add('accordion', (locale) => {
      const command = editor.commands.get('insertAccordion');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: t('Accordion'),
        icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      // Execute the command when the button is clicked (executed).
      this.listenTo(buttonView, 'execute', () =>
        editor.execute('insertAccordion'),
      );

      return buttonView;
    });

    editor.ui.componentFactory.add( 'accordionAddAbove', (locale) => {
      const command = editor.commands.get('insertAccordionRowAbove');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: t('Insert row above'),
        iconAddAbove,
        tooltip: false,
        withText: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      // Execute the command when the button is clicked (executed).
      this.listenTo(buttonView, 'execute', () =>
        editor.execute( 'insertAccordionRowAbove' ),
      );

      return buttonView;
    } );

    editor.ui.componentFactory.add( 'accordionAddBelow', (locale) => {
      const command = editor.commands.get('insertAccordionRowBelow');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: t('Insert row below'),
        iconAddBelow,
        tooltip: false,
        withText: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      // Execute the command when the button is clicked (executed).
      this.listenTo(buttonView, 'execute', () =>
        editor.execute( 'insertAccordionRowBelow' ),
      );

      return buttonView;
    } );

    editor.ui.componentFactory.add( 'accordionRemove', (locale) => {
      const command = editor.commands.get('deleteAccordionRow');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: t('Delete row'),
        iconDelete,
        tooltip: false,
        withText: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      // Execute the command when the button is clicked (executed).
      this.listenTo(buttonView, 'execute', () =>
        editor.execute('deleteAccordionRow'),
      );

      return buttonView;
    } );

  }

}
