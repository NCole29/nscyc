/**
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module accordion/accordiontoolbar
 */

import { Plugin } from 'ckeditor5/src/core';
import { WidgetToolbarRepository } from 'ckeditor5/src/widget';
import { getSelectedAccordionWidget, getAccordionWidgetAncestor } from './utils/ui/widget';

/**
 * The accordion toolbar class. It creates toolbars for the accordion feature and its content (for now only for the accordion cell content).
 *
 * @extends module:core/plugin~Plugin
 */
export default class AccordionToolbar extends Plugin {
  /**
   * @inheritDoc
   */
  static get requires() {
    return [ WidgetToolbarRepository ];
  }

  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'AccordionToolbar';
  }

  /**
   * @inheritDoc
   */
  afterInit() {
    const editor = this.editor;
    const t = editor.t;
    const widgetToolbarRepository = editor.plugins.get( WidgetToolbarRepository );

    const accordionContentToolbarItems = editor.config.get( 'accordion.contentToolbar' );

    const accordionToolbarItems = editor.config.get( 'accordion.tableToolbar' );

    if ( accordionContentToolbarItems ) {
      widgetToolbarRepository.register( 'accordionContent', {
        ariaLabel: t( 'Accordion toolbar' ),
        items: accordionContentToolbarItems,
        getRelatedElement: getAccordionWidgetAncestor
      } );
    }

    if ( accordionToolbarItems ) {
      widgetToolbarRepository.register( 'accordion', {
        ariaLabel: t( 'Accordion toolbar' ),
        items: accordionToolbarItems,
        getRelatedElement: getSelectedAccordionWidget
      } );
    }
  }
}
