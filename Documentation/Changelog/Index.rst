.. _changelog:

.. image:: ../Images/logo_dataviewer.png

.. note::
        If you like the Extension and want to support the development, please `donate now`_.
        
        .. _donate now: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HQP7AJZXJEWMQ&item_name=DataViewer-Support

Changelog
---------

**2.1.0**

- [FEATURE] Selectable Templates for Form Plugin
- [TASK] Language compatibility (still in progress)
- [BUGFIX] Missing Storage Page Message correctly implemented
- [TASK] Removed Sorting by Fields in List Module because Bug #80874
- [TASK] Composer preparation
- [BUGFIX] Icons declaration for Plugins
- [TASK] Smaller Box in FieldValue content field
- [BUGFIX] Now showing records on Pid 0
- [BUGFIX] Corrected Pid of new file relations when creating a new record
- [BUGFIX] Created TcaFactory for generating field tca
- [TASK] RecoryFactory::update now can update only single items
- [BUGFIX] Corrected default 'disallowed' on file fields
- [BUGFIX] Multiselect has now 'size' configuration
- [TASK] Added useful information to the TypoScript/UserFunc
- [TASK] Generated Tca is now stored to GLOBALS except RTE-fields
- [TASK] Record-DataHandler is now using TcaFactory
- [TASK] Signal/Slot in RecordFactory when Creating or Updating Records
- [TASK] Disabling Regeneration of Fluid Fields now possible

**2.0.2**

- [BUGFIX] Inline Elements won't work

**2.0.1**

- [BUGFIX] Tab corrections for inline elements
- [TASK] Compatibility to TYPO3 8 LTS 8.7.2
- [TASK] Usage of ConnectionPool in Database UserFunc
- [TASK] Better Record Title creation

**2.0.0**

- [TASK] Merged from Github Branch master-7 -> 1.7.5
- [TASK] Compatibilty to TYPO3 8 LTS
- [TASK] Multiselect Configuration now with different render types
- [TASK] Better handling for the FormDataProvider 'PrepareDataviewerTca'
- [BUGFIX] Deleting Records in Record List is now working correctly
- [BUGFIX] Fallback to current page id when no storage pids are selected in INLINE Field
- [BUGFIX] Installed extension without configuration caused missing template errors in backend
- [BUGFIX] DataViewer Logo in Toolbar Item was not shown under certain circumstances
- [TASK] Rewriting uploadFolder values with correct structure
- [FEATURE] Variables also can now be created directly in the Record Plugin
- [TASK] Quoting in RecordRepository
- [TASK] Removed usage of deprecated methods/settings
- [TASK] Using ConnectionPool instead of GLOBALS['TYPO3_DB']
- [FEATURE] New Editor Field for adding an editor to your forms. (Supports html,php,javascript,typoscript,css,sparql)
- [FEATURE] Casting/Evaluating Variable Type for GET/POST Variables
- [TASK] Using DataMapper directly in the findByAdvancedConditions Method
- [TASK] Created ValueFactory for faster generation of the value elements
- [BUGFIX] Added correct indexes on tx_dataviewer_domain_model_recordvalue
- [FEATURE] Filter-Plugin has now the option to show or hide active filters
- [BUGFIX] Initialization of language in FormController (#15)
- [TASK] Limitation of records in Record Plugin is now numeric, 0 and <empty value for all records>
- [TASK] Overhauled of the Ajax Request in Record Plugin
- [BUGFIX] Form Plugin Redirects had a wrong record parameter
- [TASK] Sorting of Datatype Icons in the Docheader is now made by sorting of Datatypes
- [BUGFIX] Loading sequence of fields restricted rendering of fluid fields
- [BUGFIX] Saving values into RTE Field in Frontend Environment now works correctly
- [FEATURE] Parent Record is now attached on inline records and is available through {record.parent}
- [BUGFIX] Backend User is now temporarily initialized in the FormController
- [BUGFIX] Default PerPage Setting on initial session is now working
- [FEATURE] Added UserFunc for manipulating TypoScript 

**To-Do-/Wish- List**

- Different Data-Sources for a Datatype/or Field (Webservice, XML, External Database)
- Record Injector Service for Extensions
- Access Rights for Datatypes
- Fluid Field as UserFunc for displayCond compatibility
- (Filter with direct record selection)
- Ajax Autocomplete functionality
- Change Record DataHandler to use RecordFactory
- Include MagicSuggest Into Filter Fields
- Full Workspaces support
- Fluid Fieldtype - Selectable Template File
- Record Validation as separate validation class
- FieldValue Type for different data sources
- Add Records to other external records (e.g. a fe_user gets an additional tab with the form)
- Generators, that run, when a record is saved to modify the information that is stored
