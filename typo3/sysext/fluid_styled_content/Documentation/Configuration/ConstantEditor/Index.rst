..  include:: /Includes.rst.txt

..  _constant-editor:

===============
Constant Editor
===============

..  versionchanged:: 13.1
    :ref:`Site sets <site-sets>` have been introduced. The
    :ref:`settings of the site set <site-set-fluid-styled-content-settings>`
    superseded using TypoScript constants. Using TypoScript constants is
    still possible for compatibility reasons.

..  tip::
    If :ref:`Site sets <t3coreapi:site-sets>` are used, the constant editor might be disabled. You can then edit
    the settings in the :ref:`settings-editor`.

..  include:: /Images/AutomaticScreenshots/TypoScript/ConstantEditor.rst.txt

..  rst-class:: bignums-xxl

1.  The :guilabel:`Constant Editor` can be found in the
    :guilabel:`Site Management > TypoScript` module.

2.  Select the page in the page tree which contains the root Typoscript record
    of your website.

3.  Select :guilabel:`Constant Editor` in the dropdown at the top of the
    :guilabel:`Site Management > TypoScript` module.

4.  In the dropdown list select the category :guilabel:`CONTENT`.

5.  This will give you a list with all the constants of this extension.
    All constants are described and can be edited by clicking the pencil in
    front of the current value or by editing the available field.

6.  Do not forget to save the new values. The new values will be stored in the
    "Constants" field of the root template of your website.

..  note::
    If you use the :guilabel:`Constant Editor` the configuration gets written
    to the database and cannot be kept under version control. You can cut all
    values from the constants field of the root TypoScript record and move them
    to a file in your site package extension. This way you can keep the values
    under version control.
