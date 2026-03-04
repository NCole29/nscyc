/**
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @module ckeditor_accordion/utils/ui/widget
 */

import { isWidget } from 'ckeditor5/src/widget';

/**
 * Returns a ckeditor_accordion widget editing view element if one is selected.
 *
 * @param {module:engine/view/selection~Selection|module:engine/view/documentselection~DocumentSelection} selection
 * @returns {module:engine/view/element~Element|null}
 */
export function getSelectedAccordionWidget( selection ) {
  const viewElement = selection.getSelectedElement();

  if ( viewElement && isAccordionWidget( viewElement ) ) {
    return viewElement;
  }

  return null;
}

/**
 * Returns a ckeditor_accordion widget editing view element if one is among the selection's ancestors.
 *
 * @param {module:engine/view/selection~Selection|module:engine/view/documentselection~DocumentSelection} selection
 * @returns {module:engine/view/element~Element|null}
 */
export function getAccordionWidgetAncestor( selection ) {
  const selectionPosition = selection.getFirstPosition();

  if ( !selectionPosition ) {
    return null;
  }

  let parent = selectionPosition.parent;
  while ( parent ) {
    if ( parent.is( 'element' ) && isAccordionWidget( parent ) ) {

      return parent;
    }

    parent = parent.parent;
  }

  return null;
}

// Checks if a given view element is a ckeditor_accordion widget.
//
// @param {module:engine/view/element~Element} viewElement
// @returns {Boolean}
function isAccordionWidget( viewElement ) {
  return !!viewElement.hasClass( 'ckeditor-accordion' ) && isWidget( viewElement );
}
