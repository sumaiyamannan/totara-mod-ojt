<?php
/*

Totara LMS Changelog

Release 9.6 (26th April 2017):
==============================


Security issues:

    TL-5678        Fixed sesskey handling within Hierarchy ajax scripts
    TL-13932       Fixed a security issue within TeX notation filtering

                   This fixes a regression introduced through changes made to make TeX
                   notation and MathJax filtering compatible with each other when both were
                   enabled.

                   The original compatibility fix lead to a security hole that could be
                   exploited in any content passed through the TeX filter.
                   The security vulnerability has been fixed, MathJax and TeX will no longer
                   fail over to the other. Sites using both filters should choose one or the
                   other.

Improvements:

    TL-12251       Improved the performance of adding and removing enrolled learning for an audience

                   This change improves the performance of adding and removing enrolled
                   learning by making adjustments to how the process occurs.
                   The changes can be summarised as follows:

                   * When adding one or more courses as enrolled learning to an audience, only
                   the courses that are being added are synchronised. Previously all courses,
                   including already existing courses, were synchronised.
                   * When adding or removing courses from a dynamic audience, an adhoc task is
                   used to offset the processing to the server. This means that changes will
                   happen the next time cron runs and that the user will not be forced to wait
                   for the synchronisation to complete.

    TL-12591       Email address validation is now inline with the WHATWG recommendation and webkit operation

                   Previously a custom regular expression was used to validate email addresses
                   in Totara.
                   This was not consistent with current recommendations or browser operation.
                   With this change we now use the regular expression recommended by WHATWG in
                   their HTML living standard specification.

                   You can find the regular expression we use at
                   https://html.spec.whatwg.org/multipage/forms.html#e-mail-state-(type=email]).
                   This is the same regular expression used by WebKit browsers to validate
                   their HTML5 email inputs.

    TL-12869       Improved the confirmation message shown when deleting a block
    TL-13882       Improved HTML of the progress bars in the last course accessed block and record of learning
    TL-14011       Lowered memory usage when running PHPUnit tests
    TL-14220       Updated Certificate Authority fallback bundle for Windows servers

Bug fixes:

    TL-12417       Fixed user enrolment into courses via competencies

                   Assigning and unassigning users from programs based on competencies now
                   correctly suspends and unsuspends users from the underlying courses

    TL-12600       Fixed HTML parsing for 'body' and 'manager prefix' fields in Seminar notification templates when the 'enable trusted content' setting is enabled
    TL-12641       Fixed a scheduling issue in HR Import where the sync was being triggered more times than required.
    TL-12684       Removed quiz report option "all users who have attempted the quiz" when separate group is selected as it does not make sense
    TL-12736       Added a sanity check for the Auth field in HR Import to ensure the specified authentication type exists
    TL-12773       Fixed a bug when setting SCORM attribute values
    TL-12802       Fixed the display of the grade percentage within the Record of Learning reports when max grade is not 100
    TL-12866       Fixed a bug whereby managers could not remove seminar allocations once learners had already self booked
    TL-12873       Fixed help string for report export settings
    TL-12891       Fixed and improved RTL languages support in Report Builder export formats
    TL-12892       Ensured HR Import manages special characters correctly when used with Menu custom user profile fields
    TL-12947       Fixed step, min and max attributes not being set in number form elements
    TL-12966       Added framework information to Hierarchy rules in dynamic audiences
    TL-12973       Fixed HTML validation in the current learning block when a user does not have any current learning
    TL-13881       Fixed Report builder side bar filters for multi-check customfields
    TL-13887       Fixed form parameters when expanding courses within the enhanced course catalog
    TL-13901       Fixed the validation of Seminar event custom fields configured to require unique values
    TL-13909       Fixed RTL CSS cascading

                   Previously if a theme used Basis or Roots as a parent theme, the RTL CSS
                   from these themes was not sent. This patch resolves that problem. If you
                   are using less compilation of CSS, and have included totara.less from these
                   themes, to avoid css duplication you may wish to exclude the totara and
                   totara-rtl css from the parent theme.

    TL-13911       Fixed incorrect availability of certification reports when programs are disabled
    TL-13915       Removed space between filters and content of Report Builder reports in IE

                   TL-12451 introduced a large visual gap between Report Builder filters and
                   the Report Builder content in IE. This fix removes that gap.

    TL-13924       Fixed warnings when viewing Appraisal previews
    TL-13953       Fixed a typo in the Seminar activity 'userwillbewaitlisted' string
    TL-14064       Fixed the Record of Learning: Competencies report when Global Report Restrictions are enabled
    TL-14145       Fixed a bug occuring when trying to move Course sections multiple times without refreshing

Contributions:

    * Richard Eastbury at Think Associates - TL-13911


Release 9.5 (22nd March 2017):
==============================


Security issues:

    TL-2986        Added checks for the moodle/cohort:view capability to the audience filter in user, course, and program report sources
    TL-12452       Added validation to the background colour setting for TeX notation
    TL-12733       Email self-registration now validates recaptcha first and hides error messages relating to username and email if they exist
    TL-12907       Fixed user preference handling to prevent malicious serialised object attacks

Improvements:

    TL-11292       Added accessible text to the "Number of Attendees (linked to attendee page)" column when viewing Seminar events embedded report
    TL-11311       Added labels to the default messages page to improve accessibility
    TL-11320       Added accessible text when exporting a hierarchy
    TL-12366       Improved the usability of the program assignments interface

                   There were some totals in the program assignments interface which could be
                   misleading given that they may not take into account whether the program is
                   active or not and may count users multiple times if they are in multiple
                   assigned groups. The number of assigned learners is now only shown while a
                   program is active (within available from and until dates, if they are
                   enabled).

    TL-12396       Upgraded jQuery to 2.2.4 and jQuery Migrate to 1.2.1
    TL-12398       Created a new plaintext display class to ensure correct formatting in Report Builder exports

                   A new Report builder display class "plaintext" has been introduced to serve
                   two specific functions:

                   1. Ensure that plaintext columns such as "idnumber" are correctly formatted
                      in Report builder exports to formats such as Excel and ODS.
                   2. To improve the rendering performance of the above columns by avoiding
                       unnecessary formatting applied to text content by default.

    TL-12402       Added a CLI script to automatically fix scheduled reports without recipients

                   Prior to Totara 2.7.2 scheduled reports which were configured without any
                   recipients would be emailed to the creator despite them not being an actual
                   recipient.
                   In Totara 2.7.2 this was fixed and the scheduled report was sent
                   recipients.
                   This change in behaviour left some sites with scheduled reports that were
                   not being sent to the original creator.
                   To aid those affected by this behaviour we have created a script that will
                   find scheduled reports that have no recipients and add the creator of the
                   report as a recipient.
                   To run this report simply execute "php admin/cli/fix_scheduled_reports.php"
                   as the web user on your Totara installation.

    TL-12637       Introduced a new capability allowing users to view private custom field data within user reports

                   TL-9405 fixed a bug in user reports in which the users themselves could not
                   see custom field values when the visibility of the custom field was set to
                   "visible to user". In the original code however, while the users themselves
                   could not see the values, their managers could.

                   This patch creates a new capability
                   "totara/core:viewhiddenusercustomfielddata" to allow the code to work like
                   the original but with the fix from TL-9045. Now not only can the users
                   themselves see the values, everyone with the new capability can also do so.

    TL-12662       Ensured users with program management capabilities can always access management interface

                   Previously, users could have capabilities to modify various aspects of
                   programs, such as assigning users. They could access the relevant page by
                   entering the correct url but could not access them via the interface if
                   they did not have 'totara/program:configuredetails'. That capability was
                   only necessary to use the 'Edit' tab and should not prevent other access.

                   Users may now see the 'Edit program details' button when they have any
                   program edit capabilities for a given program. They may also access the
                   overview page via that button and the tabs they have access to from there.

    TL-12665       Added a page title when completing a learning plan
    TL-12689       Removed 'View' button for appraisal stages without any visible pages
    TL-12745       Added unit tests to report builder date filter.

Bug fixes:

    TL-11255       Fixed incorrect indication that manager must complete an appraisal after completion
    TL-12451       Fixed the display of graphs within Report Builder when using the sidebar filter
    TL-12454       Corrected handling when organisation parentid equals 0

                   Before the fix, if a parentidnumber of 0 was used when importing
                   organisation data using HR Import it would be ignored and treated as an
                   empty value. Consequently, if you had an organisation structure where
                   idnumber for the top most level was 0, when the second level of
                   organisations are imported, they would be added at the top level (because
                   HR import consider them to have no parent). This has now been fixed.

    TL-12615       Stopped managers receiving Program emails for their suspended staff members
    TL-12621       Fixed navigation for multilevel SCORM packages
    TL-12643       Fixed guest access throwing error when using the enhanced catalog
    TL-12645       Fixed cache warnings on Windows systems occurring due to fopen and rename system command
    TL-12669       Ensured Evidence Custom Fields unique and locked setting worked correctly
    TL-12681       Replaced duplicate 'Draft' Plan Status filter option with 'Pending Approval' in learning plan report source.
    TL-12696       Ensured that read only evidence displays the "Edit details" button only when the user has the correct edit capability
    TL-12721       Fixed misspelt URL when adding visible learning to an audience
    TL-12734       Fixed how room conflicts are handled when restoring a Seminar activity
    TL-12739       Improved performance when using the progress column within a Certification overview report
    TL-12747       Ensured User Profile fields set as unique do not include empty values when determining uniqueness
    TL-12762       Prevented appraisal messages from being sent to unassigned users
    TL-12774       Added validation to prevent invalid Assignment grade setting combination

                   You must now select a Grade Type or the default Feedback Type if you want
                   to enable the 'Student must receive a grade to complete this activity'
                   completion setting.

    TL-12778       Fixed the display of the "add another option" link in Appraisals and 360 Feedback multi-choice questions

                   Previously the "add another option" link would correctly be removed when
                   you added the tenth option to a multi-choice question, but would be
                   displayed again when you edited the question. Clicking the link when you
                   already had the maximum amount of options would make the link disappear
                   again without doing anything, now the link will not be displayed at all.

    TL-12787       Added new capability: totara/program:markcoursecomplete

                   From 2.9.0 onwards, if a user had the capability moodle/course:markcomplete
                   set to allow in course or system contexts, they were able to mark courses
                   complete when viewing a users program page (accessed via required
                   learning). This was incorrect use of this capability, as that action would
                   only be valid if marking complete was enabled in course completion
                   criteria. This capability no longer allows marking complete via the program
                   page.

                   To allow for use cases described above, a new capability,
                   totara/program:markcoursecomplete, was added. This will allow marking a
                   course complete on a user's program page, regardless of course completion
                   criteria. This capability is checked in the course and system contexts. The
                   Site Manager role will receive this capability following upgrade.

    TL-12793       Fixed a bug when trying to remove a regular expression validation from a text custom field
    TL-12795       Fixed 'Program Name and Linked Icon' report column when exporting

                   The "Program Name and Linked Icon" report column, contained in several
                   report sources, now only contains the program name when exporting. Also,
                   the "Record of Learning: Certifications" report source had two columns
                   named "Certification name". One of them has now been renamed to
                   "Certification Name and Linked Icon", and likewise only contains the
                   certification name when exporting.

    TL-12798       Fixed the display of description for personal goals on the My Goals page
    TL-12801       Fixed exporting course completion reports to excel after filtering by organisation

Contributions:

    * Russell England - TL-12669


Release 9.4 (27th February 2017):
=================================


Security issues:

    TL-6810        Added sesskey checks to the programs complete course code

Improvements:

    TL-11291       Replaced the input button with text when editing a users messaging preferences
    TL-11317       Added labels to the add rule dropdown when editing the rules of a dynamic audience
    TL-11318       Added accessibility labels to Hierarchy framework searches and bulk actions
    TL-12314       Improved HTML validation when searching within a Hierarchy framework
    TL-12594       Added default html clean up to the static_html form element

                   Developers need to use
                   \totara_form\form\element\static_html::set_allow_xss(true) if they want to
                   include JavaScript code in static HTML forms element.

Bug fixes:

    TL-8375        Fixed issues with audiences in the table for restricting access to a menu item

                   Added the correct module to the url when rendering the table rows through
                   ajax. Also, when the form is saved, if "Restrict access by audience" is not
                   checked then it will remove all audience restrictions from the database so
                   they will not be incorrectly loaded later.

    TL-9264        Fixed a fatal error encountered in the Audience dialog for Program assignments
    TL-10082       Fixed the display of description images in the 360° Feedback request selection list
    TL-10871       Fixed duplicated error message displayed when creating Seminar sessions with multiple dates
    TL-11062       Seminar events that are in progress are now shown under the upcoming sessions tab

                   Previously events that were in progress were being shown under the previous
                   events tab. This lead to them being easily lost, and after a UX review it
                   was decided that this was indeed the wrong place to put them and they were
                   moved back to the upcoming events until the event has been completed.

                   In the course view page, if "sign-up for multiple events" is disabled, then
                   users who are signed-up will see only the event where they are signed-up to
                   as they won't be able to sign-up for another event within that Seminar. If
                   "sign-up for multiple events" is enabled, then the signed-up users will see
                   all upcoming events ("in progress" and "upcoming" ones).

    TL-11106       Fixed row duplication of Seminar events within the Seminar events report source
    TL-11186       Changed user completion icons into font icons
    TL-11230       Fixed disabled program course enrolments being re-enabled on cron

                   The clean_enrolment_plugins_task scheduled task now suspends and re-enables
                   user enrolments properly

    TL-12252       Disabled selection dialogs for Hierarchy report filters when the filter is set to "is any value"
    TL-12286       Corrected the table class used in Course administration > Competencies
    TL-12298       Fixed RTL CSS flipping in Appraisals

                   Previously there were a number of anomalies when viewing appraisals in
                   right to left languages such as Hebrew. This fixes the CSS so that they are
                   now displayed correctly.

    TL-12341       Removed unnecessary code to prevent page jump on click of action menu

                   Removed a forced jQuery repaint of the action menu which was originally
                   required to work around a Chrome display bug, but which is no longer
                   required.

    TL-12342       Moved the block hide icon to the right in Roots and Basis themes
    TL-12443       Fixed RTL CSS flipping in 360° Feedback

                   Previously there were a number of anomalies when viewing 360° feedback in
                   right to left languages such as Hebrew. This issue alters CSS so that they
                   are now displayed correctly.

    TL-12445       Fixed completion recording for some SCORMs with deep navigation structure (3+ levels)
    TL-12455       Backport TL-11198 - Added support for add-on report builder sources in column tests

                   Add-on developers may now add phpunit_column_test_add_data() and
                   phpunit_column_test_expected_count() methods to their report sources to
                   pass the full phpunit test suit with add-ons installed.

    TL-12458       Fixed the visibility permissions for images in the event details field
    TL-12463       Prevented the submission of text longer than 255 characters on Appraisal and 360° Feedback short text questions
    TL-12464       Fixed a HTML validation issue on the user/preferences.php page
    TL-12465       Fixed the display of multi-lang custom field names on the edit program and certification forms
    TL-12585       Fixed a fatal error when trying to configure the Stats block without having staff
    TL-12593       Fixed double escaping in the select and multiselect forms elements
    TL-12596       Reverted change which caused potential HR Import performance cost

                   A change in TL-12262 made it likely that imported Positions and
                   Organisations in a Hierarchy framework would be processed multiple times,
                   rather than just once each. No data problems were caused, but the
                   additional database operations were unnecessary. That change has been
                   reverted.

    TL-12603       Course reminders are no longer sent to unenrolled users

                   Email reminders for course feedback activities were previously being sent
                   to users who were unenrolled or whose enrolments had been suspended.

    TL-12606       Fixed resending certification course set messages

                   The course set Due, Overdue and Completed messages were only being sent the
                   first time that they were triggered on each certification path. Now, they
                   will be triggered when appropriate on subsequent recertifications,
                   including after a user has expired.

    TL-12616       Fixed the Certification window open transaction log entry

                   It was possible that the Certification window opening log entry was being
                   recorded out of order, could be recorded even if the window open function
                   did not complete successfully, and could contain incorrect data. These
                   problems have now been fixed by splitting the window open log entry into
                   two parts.

    TL-12649       Fixed the rendering of Totara form errors when get_data() is not called
    TL-12656       Remove incorrect quotations from mustache template strings

                   Quotations around template strings have been removed to avoid prevention of
                   key usage in string arrays.

    TL-12680       Made the user menu hide languages when the "Display language menu" setting is disabled

API changes:

    TL-10990       Ensured JS Flex Icon options are equivalent to PHP API

                   The core/templates function renderIcon may alternatively be called with two
                   parameters, the second being a custom data object.

Contributions:

    * Eugene Venter, Catalyst - TL-12596


Release 9.3 (25th January 2017):
================================


Security issues:

    TL-10773       Added safeguards to protect user anonymity when providing feedback within 360 Feedback
    TL-12322       Improved validation within the 360° Feedback request confirmation form

                   Previously, if a user manipulated the HTML of the form for confirming
                   requests for feedback in 360° Feedback, they could change emails to an
                   invalid format or, in some cases, alter requests they should not have
                   access to.
                   Additional validation following the submission of the confirmation form now
                   prevents this.

    TL-12327       Added a setting to prevent the malicious deletion of files via the Completion Import tool

                   When adding completion records for courses and certifications via CSV, a
                   pathname can be specified instead of uploading a file. After the upload
                   occurs, the target file is deleted. Users with the capability to upload
                   completion records may have been able to delete other files aside from
                   those related to completion import. In some cases they were also being
                   shown the first line of the file. By default, only site managers have the
                   capability to upload completion records.
                   Additionally in order to exploit this the web server would need to have
                   been configured to permit read/write access on the targeted files.

                   There is now a new setting ($CFG->completionimportdir) for specifying how
                   the pathname must begin in order to add completion records with this
                   method. This setting can only be added via the config.php file. When a
                   directory is specified in this setting, files immediately within it, as
                   well as within its subdirectories, can be used for completion import.

                   If the setting is not added, completion imports can no longer be performed
                   via this method. They can still be performed by uploading a file using the
                   file picker.

    TL-12411       MDL-56225: Removed unnecessary parameters when posting to a Forum

                   Previously it was possible to maliciously modify a forum post form
                   submission to fake the author of a forum post due to the presence of a
                   redundant input parameter and poor forum post submission handling.
                   The unused parameter has been removed and the post submission handling
                   improved.

    TL-12412       MDL-57531: Improved email sender handling to prevent PHPMailer vulnerabilities from being exploited
    TL-12413       MDL-57580: Improved type handling within the Assignment module

                   Previously loose type handling when submitting to an assignment activity
                   could potentially be exploited to perform XSS attacks, stricter type
                   handling has been implemented in order to remove this attack vector.

Improvements:

    TL-9016        Added content restrictions to the Goal custom fields report source

                   Content restrictions for restricting records by management, organisation
                   and position have been added to the Goal custom fields report source.

    TL-9756        Removed an HTML table when viewing a Learning plan that has been changed after being approved
    TL-10849       Improved the language strings used to describe Program and Certification exception types and actions
    TL-11074       Added additional text to the manager and approver copies of original Seminar notifications
    TL-12261       Improved code exception validation in several unit tests

Bug fixes:

    TL-10416       Fixed an error when answering appraisal competency questions as the manager's manager or appraiser
    TL-10945       Prevented loops in management job assignments in HR Import

                   Previously, if a circular management assignment was imported, HR Import
                   would fail without sensible warning. Now, if a circular management is found
                   when importing a manager with HR Import, then one or more of the users
                   forming the circular reference will fail to have their manager assigned,
                   with a notice explaining why. When importing, as many manager assignments
                   as possible will be assigned.

    TL-11150       Fixed an undefined property error in HR Import on the CSV configuration page
    TL-11238       Fixed the Seminar name link column within the Seminar sessions report
    TL-11270       Fixed Course Completion status not being set to "Not yet started" when removing RPL completions

                   Previously, when you removed RPL completion using the Course administration
                   -> Reports -> Course completion report, it would set the record to "In
                   progress", regardless of whether or not the user had actually done anything
                   that warranted being marked as such. If the user had already met the
                   criteria for completion, the record would not be updated until the
                   completion cron task next ran.

                   Now, the records will be set to "Not yet started". Reaggregation occurs
                   immediately, and may update the user to "In progress" or "Complete"
                   depending on their progress. Note that if a course is set to "Mark as In
                   Progress on first view" and the user had previously viewed the course but
                   made no other progress, then their status will still be "Not yet started"
                   after reaggregation.

    TL-11316       Fixed an error when cloning an Appraisal containing aggregated questions
    TL-12243       Fixed a Totara menu issue leading to incorrectly encoded ampersands
    TL-12256       Prevented an incorrect redirect occurring when dismissing a notification from within a modal dialog
    TL-12263       Fixed an issue with the display of assigned users within 360° Feedback

                   The assigned group information is no longer shown for 360° Feedback in the
                   Active or Closed state. In these states, the pages always reflect actual
                   assigned users.

    TL-12277       Corrected an issue where redirects with a message did not have a page URL set
    TL-12280       Fixed a bug preventing block weights being cloned when a dashboard is cloned
    TL-12283       Fixed several issues on the waitlist page when Seminar approval type is changed

                   The waitlist page showed the wrong approval date (1 Jan 1970) and debug
                   messages when a seminar changed its approval type from no approval required
                   to manager approved.

    TL-12284       Fixed an upgrade error due to an incorrectly unique index in the completion import tables on SQL Server

                   Previously, if a site running SQL Server had imported course or
                   certification completions, there could have been an error when trying to
                   upgrade to Totara 9. This has been fixed. Sites that had already
                   successfully upgraded will have the unique index replaced with a non-unique
                   equivalent.

    TL-12287       Ensured Hierarchy 'ID number' field type is set as string in Excel and ODS format exports to avoid incorrect automatic type detection
    TL-12297       Removed options from the Reportbuilder "message type" filter when the corresponding feature is disabled
    TL-12299       Fixed an error on the search page when setting Program assignment relative due dates
    TL-12301       Fixed the replacement of course links from placeholders in notifications when restoring a Seminar

                   Previously when a course URL was embedded in a seminar notification
                   template, it would be changed to a placeholder string when the seminar was
                   backed up. Restoring the seminar would not change the placeholder back to
                   the proper URL. This fix ensures it does.

    TL-12303       Fixed the HTML formatting of Seminar notification templates for third-party emails
    TL-12305       Fixed incorrect wording in Learning Plan help text
    TL-12311       Fixed the "is after" criteria in the "Start date" filter within the Course report source

                   The "is after" start date filter criteria now correctly searching for
                   courses starting immediately after midnight in the users timezone.

    TL-12315       Waitlist notifications are now sent when one message per date is enabled

                   If a Seminar event was created with no dates, people could still sign up
                   and be waitlisted.
                   However, they would only receive a sign up email if the "one message per
                   date" option was off.
                   Now, the system will send the notification regardless of this setting.

    TL-12323       Removed references to the SCORM course format from course format help string
    TL-12325       Fixed the Quick Links block to ensure it decodes URL entities correctly
    TL-12333       Made improvements to the handling of invalid job assignment dates
    TL-12337       Fixed the formatting of event details placeholder in Seminar notifications
    TL-12339       Reverted removal of style causing regression in IE

                   TL-11341 applied a patch for a display issue in Chrome 55.
                   This caused a regression for users of Edge / IE browsers making it
                   difficult and in some cases impossible to click grouped form elements.
                   The Chrome rendering bug has since been addressed.

    TL-12344       Fixed an error message when updating Competency scale values
    TL-12352       Fixed a bug in the cache API when fetching multiple keys having specified MUST_EXIST

                   Previously when fetching multiple entries from a cache, if you specified
                   that the data must exist, in some circumstances the expected exception was
                   not being thrown.
                   Now if MUST_EXIST is provide to cache::get_many() an exception will be
                   thrown if one or more of the requested keys cannot be found.

    TL-12369       Marked class totara_dialog_content_manager as deprecated

                   This class is no longer in use now that Totara has multiple job
                   assignments. Class totara_job_dialog_assign_manager should be used instead.

Miscellaneous Moodle fixes:

    TL-12406       MDL-57100: Prevented javascript exceptions from being displayed during an AJAX request
    TL-12407       MDL-56948: Fixed Assignment bug when viewing a submission with a grade type of "none"
    TL-12409       MDL-57170: Fixed fault in legacy Dropbox API usage
    TL-12410       MDL-57193: Fixed external database authentication where more than 10000 users are imported

Contributions:

    * David Shaw at Kineo UK - TL-12243


Release 9.2.2 (23rd December 2016):
===================================


Bug fixes:

    TL-12312       Fixed HR Import User Link to job assignment invalid settings

                   In HR Import, after setting "Link job assignment" to "using the user's job
                   assignment ID number", and then successfully performing an import, the
                   setting was supposed to become locked. This is to prevent problems which
                   could occur, where imported data is written into the wrong job assignment
                   records.

                   Due to a bug, it was possible that the setting would change to link "to the
                   user's first job assignment" and remain locked on this setting.

                   This patch ensures that, after doing an import with the setting set to link
                   to "using the user's job assignment ID number", it will always link this way in
                   future. The cause of the bug has been fixed, extra checks have been
                   implemented to ensure that imports will be prevented if the settings are in
                   an invalid state, and invalid settings were fixed on sites affected by this
                   problem.

    TL-12316       Fixed missing include in Hierarchy unit tests covering the moving of custom fields


Release 9.2.1 (22nd December 2016):
===================================


Bug fixes:

    TL-12309       Fixed the display of aggregated questions within Appraisals

                   This was a regression from TL-11000, included in the 2.9.14 and 9.2
                   releases.
                   The code in that fix used functionality first introduced in PHP 5.6, and
                   which is not compatible with PHP5.5.
                   The effect of the resulting bug was purely visual.
                   We've now re-fixed this code in order to ensure it is compatible with all
                   supported versions of PHP.


Release 9.2 (21st December 2016):
=================================


Important:

    TL-11333       Fixes from Moodle 3.0.7 have been included in this release

                   Information on the issues included from this Moodle release can be found
                   further on in this changelog.

    TL-11369       Date related form elements exportValue() methods were fixed to return non array data by default

                   All custom code using MoodleQuickForm_date_time_selector::exportValue() or
                   \MoodleQuickForm_date_selector::exportValue() must be reviewed and fixed if
                   necessary.

Security issues:

    TL-5254        Improved user verification within the Quick Links block
    TL-11133       Fixed Seminar activities allowing sign up even when restricted access conditions are not met
    TL-11194       Fixed get_users_by_capability() when prohibit permissions used
    TL-11335       MDL-56065: Fixed the update_users web service function
    TL-11336       MDL-53744: Fixed question file access checks
    TL-11338       MDL-56268: Format backtrace to avoid displaying private data within web services

Improvements:

    TL-7221        Added time selectors to Before and After date criteria in dynamic audience rules
    TL-10952       Links that should be styled as buttons now look like buttons in Basis & Roots themes
    TL-10971       Improved Feedback activity export formatting

                   The following improvements were made to the exported responses for feedback
                   activities:
                   * Newlines in Long Text responses are no longer replaced with the html
                   <br/> tag
                   * The text wrap attribute is set for all response cells
                   * Long text, Short text and Information responses are no longer exported in
                   bold

    TL-11054       Only the available regions are shown when configuring a block's position on the current page

                   Previously, when configuring blocks, all possible regions were shown when
                   setting the region for a block on the current page. This setting now only
                   has the options that exist on the page

    TL-11056       Added phpunit support for third party modules that use "coursecreator" role
    TL-11075       Improved inline help for Seminar's "Manager and Administrative approval" option
    TL-11117       Removed unused, redundant, legacy hierarchy code
    TL-11145       Newly created learning plans now include competencies from all of a user's job assignments
    TL-11261       Converted folder and arrow icon in file form control to flex icons
    TL-11273       Removed an unnecessary fieldset surrounding admin options
    TL-11289       Dropping a file onto the course while editing now has alternative text

                   This also converts the image icon to a flex icon.

Bug fixes:

    TL-4912        Fixed the missing archive completion option in course administration menu
    TL-7666        Images used in hierarchy custom fields are now displayed correctly when viewing or reporting on the hierarchy
    TL-9500        Fixed "View full report" link for embedded reports in the Report table block
    TL-9988        Fixed moving hierarchy custom fields when multiple frameworks and custom fields exist
    TL-10054       Ensured that the display of file custom fields in hierarchies link to the file to download
    TL-10101       Removed unnecessary permission checks when accessing hierarchies
    TL-10744       Fixed footer navigation column stacking in the Roots and Basis themes
    TL-10915       Ensured that courses are displayed correctly within the Current Learning block when added via a Certification
    TL-10953       Fixed Learning Plans using the wrong program due date

                   Previously, given some unlikely circumstances, when viewing a program in a
                   learning plan, it was possible that the program due date could have been
                   displaying the due date for one of the course sets instead.

    TL-11000       When calculating the Aggregate rating for appraisal questions, not answered questions and zero values may now be included in aggregate calculations

                   Two new settings have been added to Aggregate rating questions within
                   Appraisals.
                   These can be used in new aggregate rating questions to indicate how the
                   system must handle unanswered questions, as well as questions resulting in
                   a zero score during the calculations.

    TL-11063       Fixed a PHP error in the quiz results statistics processing when a multiple choice answer has been deleted
    TL-11072       Administrative approver can do final approval of seminar bookings in two stage approvals prior to manager
    TL-11076       Fixed the display of the attendee name for Seminar approval requests in the Task/Alert report
    TL-11110       Added validation warning when creating management loops in job assignments

                   Previously, if you tried to assign a manger which would result in a
                   circular management structure, it would fail and show an error message. Now
                   it shows a validation warning explaining the problem.

    TL-11124       Treeview controls in dialogs now display correctly in RTL languages
    TL-11126       Fixed HR Import data validation being skipped in some circumstances

                   If the source was an external database, and the first record in the import
                   contained a null, then the data validation checks on that column were being
                   skipped. This has been fixed, and the data validation checks are now fully
                   covered by automated tests.

    TL-11129       Fixed url parameters not being added in pagination for the enrolled audience search dialog
    TL-11130       Fixed how backup and restore encodes and decodes links in all modules
    TL-11137       Courses, programs and certifications will always show in the Record of Learning if the user has made progress or completed the item

                   The record of learning is intended to list what the user has achieved.
                   Previously, if a user had completed an item of learning, this may sometimes
                   have been excluded due to visibility settings (although not in all cases
                   with standard visibility). The effect of audience visibility settings and
                   available to/from dates have been made consistent with that of standard
                   visibility. The following are now show on their applicable Record of
                   Learning embedded reports, regardless of enrolment status and current
                   visibility of the item elsewhere.

                   Courses:  Any course where a user's status is greater than 'Not yet
                   started'. This includes 'In-progress' and 'Complete'.

                   Programs: Any program where the user's status is greater than 'Incomplete'.
                   In existing Totara code, this will only be complete programs. This applies
                   to the status of the program only and does not take into account program
                   course sets. If just a course set were complete, and not the program, the
                   program would not show on the Record of Learning if it should not otherwise
                   be visible.

                   Certifications: Any certification where the user's status is greater than
                   'Newly assigned'. This includes 'In-progress', 'Certified' and 'Expired'.

    TL-11139       Fixed report builder access permissions for the authenticated user role

                   The authenticated user role was missed out when a report's access
                   restriction was "user role in any context" - even if this role was ticked
                   on the form. The fix now accounts for the authenticated user.

    TL-11148       Fixed suspended course enrolments not reactivating during user program reassignment
    TL-11191       Ensured the calendar block controls are displayed correctly in RTL languages
    TL-11200       Fixed the program enrolment plugin which was not working for certifications when programs had been disabled
    TL-11203       Allowed access to courses via completed programs consistently

                   Previously if a user was complete with a due date they could not access any
                   courses added to the program after completion, but users without a due date
                   could access the new courses. Now any user with a valid program assignment
                   can access the courses regardless of their completion state.

    TL-11208       Fixed unnecessary comma appearing after user's name in Seminar attendee picker

                   When only "ID Number" is selected in the showuseridentity setting and a
                   user does not have an ID number an extra comma was displayed after the
                   user's name in the user picker when adding / removing Seminar attendees.

    TL-11209       Fixed errors in some reports when using report caching and audience visibility
    TL-11213       Fixed undefined index warnings while updating a Seminar event without dates
    TL-11216       Fixed incorrect use of userid when logging a program view from required learning
    TL-11217       Flex icons now use the title attribute correctly
    TL-11237       Deleting unconfirmed users no longer deletes the user record

                   Previously when unconfirmed users were deleted by cron the user record was
                   deleted from the database immediately after the standard deletion routines
                   were run.
                   Because it is possible to include unconfirmed users in dynamic audiences
                   they could end up with traces in the database which may not be cleaned up
                   by the standard deletion routines.
                   The deletion of the user record would then lead to these traces becoming
                   orphaned.
                   This behaviour has been changed to ensure that the user record is never
                   deleted from the database, and that user deletion always equates to the
                   user record being marked as deleted instead.

    TL-11239       Fixed type handling within the role_assign_bulk function leading to users not being assigned in some situations
    TL-11246       Added default sort order of attendees on the Seminar sign-in sheet

                   The sort order was the order in which the attendees was added. This patch
                   adds a default sort order to the embedded report so that users are listed
                   in alphabetical order. Note: for existing sites the sign-in sheet embedded
                   report will need to be reset on the manage reports page (doing this will
                   reset any customisations to this report)

    TL-11263       Loosened cleaning on Program and Certification summary field making it consistent with course summary
    TL-11272       Fixed inaccessible files when viewing locked appraisal questions
    TL-11309       HR Import now converts mixed case usernames to lower case

                   Now when you import a username with mixed case you will receive a warning,
                   the username will be converted to lower case and the user will be
                   imported.
                   This patch brings the behaviour in Totara 9 in line with Totara 2.9.

    TL-11329       Fixed program course sets being marked complete due to ignoring "Minimum score"

                   When a program or certification course set was set to "Some courses" and
                   "0", the "Minimum score" was being ignored. Even if a "Minimum score" was
                   set and was not reached, the course set was being marked complete. Now, if
                   a "Minimum score" is set, users will be required to reach that score before
                   the course set is marked complete, in combination with completing the
                   required number of courses.

                   If your site has a program or certification configured in this way, and you
                   find users who have been incorrectly marked complete, you can use the
                   program or certification completion editor to change the records back to
                   "Incomplete" or "Certified, window is open". You should then wait for the
                   "Program completions" scheduled task (runs daily by default) to calculate
                   which stage of the program the user should be at.

    TL-11331       Fixed HTML and multi language support for general and embedded reports
    TL-11341       Fixed report builder filter display issue in chrome 55

                   Previously there was a CSS statement adding a float to a legend which
                   appears to be ignored by most browsers. With the release of chrome 55, this
                   style was being interpreted.

    TL-12244       Fixed 'Allow extension request' setting not being saved when adding programs and certifications
    TL-12246       Fixed MSSQL query for Course Completion Archive page
    TL-12248       Fixed layout of Totara forms when using RTL languages

API changes:

    TL-8423        Changed course completion to only trigger processing of related programs

                   Previously, course completion caused completion of all of a user's programs
                   and certifications to be re-processed. Now, only programs which contain
                   that course are processed.

    TL-11225       \totara_form\model::get_current_data(null) now returns all current form data

Miscellaneous Moodle fixes:

    TL-11337       MDL-51347: View notes capability is now checked using the course context
    TL-11339       MDL-55777: We now check libcurl version during installation
    TL-11342       MDL-55632: Tidy up forum post messages
    TL-11343       MDL-55820: Use correct displayattempt default options in SCORM settings
    TL-11344       MDL-55610: Improved cache clearing
    TL-11345       MDL-42041: Added "Turn Editing On" to page body to Book module
    TL-11346       MDL-55874: Fixed html markup in participation report
    TL-11347       MDL-55862: The database module now uses the correct name function for display
    TL-11348       MDL-55505: Fixed editing of previous attempt in Assignment module
    TL-11349       MDL-53893: Fixed awarding of badges with the same criteria
    TL-11351       MDL-55654: Added multilang support for custom profile field names and categories
    TL-11352       MDL-55626: Added desktop-first-column to legacy themes
    TL-11353       MDL-29332: Fixed unique index issue in calculated questions when using MySQL with case insensitive collation
    TL-11358       MDL-55957: Fixed the embedded files serving in Workshop module
    TL-11359       MDL-55987: Prevent some memory related problems when updating final grades in gradebook
    TL-11360       MDL-55988: Prevent autocomplete elements triggering warning on form submission
    TL-11361       MDL-55602: Added redis session handler with locking support
    TL-11362       MDL-56019: Fixed text formatting issue in web services
    TL-11363       MDL-55776: Fixed group related performance regression
    TL-11364       MDL-55876: Invalid low level front page course updates are now prevented
    TL-11368       MDL-55911: Improved Quiz module accessibility
    TL-11371       MDL-56069: Fixed scrolling to questions in Quiz module
    TL-11372       MDL-56136: Improved error handling of file operations during restore
    TL-11373       MDL-56181: Updated short country names
    TL-11374       MDL-56127: Fixed a regression in form element dependencies
    TL-11376       MDL-55861: Fixed displaying of activity names during drag & drop operations
    TL-11379       MDL-52317: Fixed visual issues when inserting oversized images
    TL-11384       MDL-55597: Fixed support for templates in subdirectories
    TL-11385       MDL-51633: Restyled ADD BLOCK to remove max-width in legacy themes
    TL-11386       MDL-51584: Improved performance when re-grading
    TL-11387       MDL-56319: Fixed the handling of default blocks when an empty string is used to specify there should be no default blocks
    TL-11388       MDL-52051: Correct code that relies on the expires_in optional setting within OAuth
    TL-11389       MDL-56050: Fixed missing context warning on the maintenance page
    TL-11390       MDL-36611: Fixed missing context warning when editing outcomes
    TL-11392       MDL-51401: Improved the ordering of roles on the enrolled users screen
    TL-11393       MDL-55345: Fixed links to IP lookup in user profiles
    TL-11394       MDL-56062: Standardised display of grade decimals in Assignment module
    TL-11395       MDL-56345: Fixed alt text for PDF editing in Assignment module
    TL-11396       MDL-56439: Added missing include in course format code
    TL-11397       MDL-56328: Improved activity indentation on the course page in legacy themes
    TL-11398       MDL-56368: Fixed Restrict Access layout issue in legacy themes
    TL-11399       MDL-43796: Fixed Reveal identities issue during restore
    TL-11400       MDL-56131: Added checks to prevent the Choice module becoming locked for a long periods of time
    TL-11401       MDL-55143: Fixed detection of version bumps in phpunit
    TL-11402       MDL-29774: Group membership summaries are now updated on AJAX calls
    TL-11403       MDL-55456: Fixed context warning when assigning roles
    TL-11404       MDL-56275: Removed repository options when adding external blog
    TL-11405       MDL-55858: Removed subscription links when not relevant in Forum module
    TL-11406       MDL-56250: mforms now support multiple validation calls
    TL-11407       MDL-53098: Fixed form validation issue when displaying confirmation
    TL-11408       MDL-56341: Fixed Quote and Str helpers collisions in JS Mustache rendering
    TL-11411       MDL-48350: Fixed action icons placement in docked blocks in legacy themes
    TL-11412       MDL-56347: Added diagnostic output for alt cache store problems in phpunit
    TL-11414       MDL-56354: All debugging calls now fail phpunit execution
    TL-11415       MDL-54112: Fixed Required grading filtering
    TL-11416       MDL-56615: Fixed PHP 7.0.9 warning in Portfolio
    TL-11417       MDL-56673: Fixed minor problems in template library tool
    TL-11418       MDL-47500: Improved SCORM height calculation

                   Please note that Totara already contained a similar patch. This change
                   added minor changes from upstream only.

    TL-11419       MDL-55249: Fixed status in feedback activity reports
    TL-11420       MDL-55883: Fixed calendar events for Lesson module
    TL-11421       MDL-56634: Improved rendering of WS api descriptions
    TL-11423       MDL-54986: Disabled add button for quizzes with existing attempts
    TL-11426       MDL-56748: Fixed a memory leak when resetting MUC
    TL-11427       MDL-56731: Fixed breadcrumb when returning to groups/index.php
    TL-11428       MDL-56765: User preferences are reloaded in new WS sessions
    TL-11429       MDL-53718: Do not show course badges when disabled
    TL-11430       MDL-54916: Improved the performance of empty ZIP file creation
    TL-11431       MDL-56120: Calendar events belonging to disabled modules are now hidden
    TL-11432       MDL-56755: Improved documentation of assign::get_grade_item()
    TL-11433       MDL-56133: Caches are now purged after automatic language pack updates
    TL-11434       MDL-53481: Fixed sql errors within availability restrictions
    TL-11435       MDL-56753: Fixed separate group mode errors
    TL-11436       MDL-56417: Fixed ignore_timeout_hook logic in auth subsystem
    TL-11437       MDL-56623: Added a new lang string for 'addressedto'
    TL-11438       MDL-55994: Fixed warning in RSS feed generation
    TL-11439       MDL-52216: Prevented invalid view modes in Lesson module

Contributions:

    * Russell England at Kineo USA - TL-11239


Release 9.1 (22nd November 2016):
=================================


Important:

    TL-10252       Non-date picker uses of date picker strings changed to langconfig strings

                   Code unrelated to date pickers has been updated to use strings from the
                   langconfig language pack. Date picker strings should only be used in
                   relation to date pickers. Code now using the langconfig strings will
                   benefit from customisations made to those strings.

                   Additionally, the lang string customfieldtextdateformat was added in
                   totara_customfield. If you have customised the lang string
                   datepickerlongyearregexphp then after upgrading you should change
                   customfieldtextdateformat to your custom regular expression.

    TL-11112       The default encoding is now consistently set to UTF-8

                   Totara now sets UTF-8 as default encoding for PHP scripts to prevent hard
                   to detect problems on sites with non-standard php.ini settings. There are
                   no known problems in Totara, but this change may help with compatibility in
                   external libraries and 3rd party plugins.

    TL-11114       Incompatible plugin updates and installer code was removed

                   Totara LMS does not include an add-on installer, all additional plugins
                   must be installed manually by server administrators.

                   Before installing any additional plugins please make sure the code was
                   tested with Totara LMS, is secure, is maintained by authors and contains
                   phpunit and behat tests.

                   Totara Learning Solutions support does not cover plugins that are not
                   included in the standard distribution.

    TL-11157       Fixed data loss bug when learning plans are deleted under certain conditions

                   This bug occurs under very specific circumstances.

                   Due to the structure of the repository table involved, it is possible to
                   have relationship data from different learning plans and even different
                   components within the same learning plan co-existing within the same table.
                   Originally, the system deleted relationships between learning plan
                   components (e.g. course and objectives) using just the component identifier
                   e.g. objective ID.

                   However, in very rare situations, it is possible for the table to hold
                   values from unrelated components which use the same identifier. When the
                   system deleted a component using this identifier value alone *all*
                   components associated with it were removed. Hence the data loss.

                   The system now checks component type in addition to ID to prevent this
                   happening.

Security issues:

    TL-5178        Added a missing sesskey check to feedback/assignments.php
    TL-6615        Added a check for HTTP only cookies to the security report

                   The HTTP only cookies setting restricts access to cookies by client side
                   scripts in supported browsers making it more difficult to exploit any
                   potential XSS vulnerabilities.

    TL-8849        Improved validation when managing Seminar custom fields

                   Previously it was possible to view custom fields from areas outside of
                   Seminars through the Seminar custom field management page.
                   This page now properly verifies that the custom fields being requested
                   belong to a Seminar area.

    TL-10752       Implemented additional checks within the Appraisal review ajax script

Improvements:

    TL-9325        Moved the add event link within Seminar above the upcoming events display
    TL-10038       Added a warning entry into the HR Import import log if data contains a user that has their "HR import" setting disabled
    TL-10097       Removed whitespace when editing individual feedback 360 requests
    TL-10203       Improved efficiency when importing users that include dropdown menu profile field data

                   A significant performance gain has been made when importing users through
                   HR Import on sites that use drop down menu profile custom fields.
                   The import process should now run much faster than before.

    TL-10292       Added a legend when exporting and importing questions by category or context from within the question bank
    TL-10627       Improved appraisal snapshot PDF rendering
    TL-10654       Improved display of username when viewing as another role
    TL-10681       Added an environment test for mbstring.func_overload to ensure it is not set

                   Multibyte function overloading is not compatible with Totara.

    TL-10705       Improved the help text within Seminar when uploading attendees by CSV file
    TL-10731       Added setting to allow limiting of feedback reminders sent out

                   A new setting has been added, 'reminder_maxtimesincecompletion', which can
                   be used to limit the number of days after course completion in which
                   feedback activity reminders will be sent. This may be used to prevent
                   reminders being sent for historic course completions after they are
                   imported via upload.

    TL-10782       Seminar direct enrolment instances within a course can now be manually removed when no longer wanted
    TL-10793       Improved support of RTL languages within Report builder reports in the new themes
    TL-10909       Improved wording of course activity reports visibility setting help
    TL-10917       Improved the performance of admin settings for PDF fonts
    TL-10947       Removed duplicated link in the My team block
    TL-10965       Improved program assignments to recognise changes in hierarchies related to 'all below' assignments

                   Previously, if a change was made to a lower level of a hierarchy then the
                   change did not trigger deferred program assignment update. Instead, the
                   change would not be applied until the program user assignments cron task
                   was run.
                   Now, the change immediately flags the related program for update and will
                   be processed by the deferred program assignments task.

    TL-11001       Mark completion reaggregated after each record is processed

                   Previously, completion_regular_task would first process all records which
                   had a reaggregate flag greater than one, then finally set the flags on all
                   the records to 0. Now, the reaggregate flag is set to 0 after each record
                   is processed.

    TL-11026       Improved move left and move right functionality when editing a course
    TL-11041       Site level administrative approvers setting in Seminars has been relocated to Seminars > Global settings
    TL-11045       Seminar upcoming and previous headings are now the correct level
    TL-11051       The Seminar event "Add approver" button is now disabled when it is not relevant
    TL-11052       Changed text when removing users from a seminar event

Bug fixes:

    TL-7752        Fixed problems with program enrolment messages

                   Program enrolment and unenrolment messages are now resent each time a user
                   is assigned or unassigned, rather than just the first time either of those
                   events occur.
                   All program messages are now covered by automated tests.

    TL-9301        Fixed Seminar event functionality when the cancellationnote default custom field has been deleted
    TL-9846        Removed reference to deprecated variable when in a chat activity
    TL-9993        Fixed the display of images within textareas in Learning Plans and Record of Learning Evidence
    TL-9994        Stopped the actions column from being included when exporting Other Evidence report in the Record of Learning
    TL-10108       Prevented program due messages being sent when the user is already complete

                   This fix affects several messages: program due, program overdue, course set
                   due and course set overdue. In programs and certifications, just before one
                   of these messages is sent, a check is performed to ensure that the user
                   hasn't completed the program or certification in the mean time.

    TL-10213       Reduced the number of joins in appraisal details report with scale value questions

                   Multi-choice, single answer questions no longer need a join, while
                   multi-choice, multi-select questions now require just one join per role per
                   question (down from two).
                   A consequence of this change is that multi-choice columns will no longer be
                   sorted alphabetically in this report. Instead, if you sort a multi-choice
                   column, the records will be shown in the same order as the options are
                   defined and as they appear when completing the appraisal.
                   MySQL is inherently limited to 61 joins, but now more questions can be
                   added before this limit is reached.

    TL-10244       Removed unnecessary italic format from the my team block
    TL-10273       Removed unnecessary fieldset around forum search
    TL-10311       Controls in the element library now link to the same page
    TL-10320       Corrected the accessibility link between the Seminar event export label and it's select input
    TL-10331       Ensured URL custom fields are cleaned using PARAM_URL when uploaded via HR Import
    TL-10332       Added default behaviour of do not open in new window for URL custom fields when added or updated via HR Import
    TL-10360       Competency completion calculations now correctly look at previously completed courses

                   Courses completed before the last time a competency is modified are now
                   correctly considered for competency assignment

    TL-10687       Dock action icons now use the same colour as block actions in basis
    TL-10766       Fixed colour of legends and help icons in Kiwifruit responsive
    TL-10787       Fixed a php notice generated when a competency is added to a learning plan with optional courses
    TL-10819       Added code to re-run an upgrade step to delete report data for deleted users

                   The issue was caused by TL-8711 and fixed by TL-10804

    TL-10837       Added workaround for iOS 10 bug causing problems with video playback
    TL-10853       Ensured consistent spacing around the login info within the Basis theme footer
    TL-10891       Fixed overactive validation of Seminar cutoff against dates

                   Previously when editing a Seminar event in which the current date was
                   already within the cutoff period, if you attempted to edit the event you
                   could not save because the cutoff was too close, even in situations when
                   you were not changing the dates or the cutoff.
                   Cutoff validation is now only applied when the dates are changing, or when
                   the cutoff period is changing.

    TL-10901       Fixed missing course events from calendar when viewing all

                   Previously, many events were being excluded from the calendar when being
                   viewed by a user with the capability, moodle/calendar:manageentries, while
                   the site setting, 'calendar_adminseesall' was turned on. The process of
                   selecting events from courses to show in the calendar to fix this has been
                   improved. However, for performance reasons, there is still a limit on how
                   many courses have events shown in the calendar. This limit has been set at
                   50 courses by default. The limit can be adjusted using a new setting,
                   calendar_adminallcourseslimit. See config-dist.php for more information on
                   that setting.

    TL-10905       Stopped a duplicate error message from being displayed on the login screen when the session has expired
    TL-10910       Fixed required permissions for appraisals aggregate questions
    TL-10916       Fixed a debug error within the Current Learning block when images are added to the summary of a program or certification
    TL-10946       Removed false deprecation message for the viewmyteam string
    TL-10955       Fixed database error when generating a report with search columns
    TL-10956       Fixed  the display of the marking guide editing interface

                   Missing selectors from Totara's new themes have been added to now catch
                   each type of advanced grading form; marking guide & Rubric.

                   As themes continue to prefer CSS applied without the use of the 'style'
                   attribute, the maximum grade form input has also had its explicit width
                   removed.

                   The Javascript calculation of textarea widths inside the form have also
                   been simplified, with height now being the only value calculated & set.

    TL-10963       Added tabs to the seminar events and session report pages and ensured bookmarking of both pages can be achieved
    TL-10972       Deleting a Seminar now correctly removes orphaned notification records
    TL-10979       Ensured certification messages can be resent on subsequent recertifications

                   This patch ensures that all applicable certification messages are reset
                   when a user's recertification window opens, allowing them to be triggered
                   again for that user.

    TL-10998       Removed inaccessible options in Program Administration block
    TL-11009       Fixed the display of learning plan courses within the Current Learning block after being enrolled in a course
    TL-11010       Fixed emails being sent to declined users when an event is closed
    TL-11020       Caused program completion to be checked on assignment

                   Now, when users are assigned to programs and certifications, completion
                   will immediately be calculated. If the user has already completed the
                   courses required for program completion or certification, they will be
                   marked complete. Previously, the user would have had to wait for the
                   Program Completions scheduled task to run, which occurs once each night by
                   default.

                   This change also causes the first course set completion record to be
                   correctly created. Previously, it was not created until the first course
                   set was completed. Because it is being created at the correct time, course
                   set due and overdue messages related to the first course set will now be
                   correctly triggered.

    TL-11047       Fixed an incorrect capability check made when checking whether a user can manage dashboards
    TL-11060       Fixed a php notice generated within HR Sync when using the organisation or position elements
    TL-11087       Ensured that IE9 chunked stylesheet paths are correctly generated
    TL-11102       Fixed a timing issue in totara_core_webservice PHPUnit tests
    TL-11138       Provided an IE9 compatible fallback for the loading icon

API changes:

    TL-9726        Added the system requirements for upgrades to Totara 10dev

Contributions:

    * Davo Smith at Synergy Learning - TL-10917
    * Jo Jones at Kineo - TL-11157


Release 9.0 (19th October 2016):
================================


New features:

    TL-6874        Introduction of Font Icons

                   Font Icons have been made available through a new "flex icon" API.
                   Additionally the majority of icons used within Totara have been converted to use font icons.

                   Font icons bring exciting new possibilities when styling an icon.
                   With them you can use any standard CSS font styling to customise the icon, including changing its colour and size.
                   Additionally the font icons are fully scalable, and are not designed at a specific size.
                   Totara's font icon implementation is based upon the Font Awesome toolkit.

                   Information on using font icons, as well as how to work with them can be found in the developer documentation.

    TL-9031        Introduction of Roots, a new base theme for Totara

                   A new base theme has been created for Totara.
                   Called Roots it utilises Bootstrap 3 to produce a clean new look.

                   We strongly recommend that all new themes utilise the roots theme as a parent.

                   This theme is based on the Bootstrap theme (https://github.com/bmbrands/theme_bootstrap) produced and maintained by Bas
                   Brands, David Scotson, and other contributors.

                   For more information on the Totara 9 approach to theme management please refer to our developer documentation
                   https://help.totaralearning.com/display/DEV/Totara+LMS+9+approach+to+theme+management

    TL-7468        Introduction of Basis, the new default theme for Totara

                   Basis is a new theme added to Totara, and now set as the default theme for all new installations.

                   Designed in house by our UX experts it brings a brand new look to Totara out of the box.

                   For more information on the Totara 9 approach to theme management please refer to our developer documentation
                   https://help.totaralearning.com/display/DEV/Totara+LMS+9+approach+to+theme+management

    TL-9493        Repurpose the base theme

                   The base theme in Totara is no longer a stand alone theme.
                   It now contains only essential styles for the Font Icons.

                   It is strongly recommended that all themes use the base theme as a parent.
                   This will ensure that the new font icons will work out of the box for your theme.

                   Making your theme use the base theme is as simple adding "base" to the end of the parents array in your theme config.
                   For instance you should have something like the following:

                   $THEME->parents = array('roots', 'base');

    TL-7494        New Totara forms library

                   There is a new modern forms library available in Totara that can be used as an alternative to the current forms library.
                   The new library provides additional functionality not available in the Quick Forms based mform library.

                   This new forms library will be our library of choice for all future work.
                   It is designed to overcome the limitations of the current forms library and lend itself more towards use within the
                   current technologies embraced within Totara, including fully template driven, deliverable and translatable via
                   JavaScript and AJAX, easy introduction of new custom elements, full testable, more intuitive PHP API, and modular
                   extendable JavaScript API.

                   See the developer documentation for more details https://help.totaralearning.com/display/DEV/Totara+forms+library

    TL-8208        Added support for hooks

                   Hooks were designed to improve interactions of plugins and simplify customisation. This release contains the hook
                   infrastructure and conversion of one sample area - the course editing form. We are planning to add hook support to more
                   areas in the future and welcome code contributions inserting new hooks where required.

                   See the developer documentation for more information
                   https://help.totaralearning.com/display/DEV/Hooks+developer+documentation

    TL-8513        Added Current Learning block

                   The Current learning block is a new block which displays all learning that is active for the viewing user. This includes
                   courses, programs and certifications in a unified view, but excludes completed learning.

                   A new user_learning API has been introduced to provide a standard way to access learning data, currently four components
                   are supported:

                   *  Course
                   *  Program
                   *  Certification
                   *  Learning Plan

                   Other components and plugins can extend the user_learning classes if they wish to integrate with the current learning
                   block. Look in code for examples.

    TL-8514        Added Last Course Accessed block

                   The Last Course accessed block can be added to any page that supports blocks. It provides quick access to the course the
                   user last accessed.

    TL-7456        Added a new Location custom field

                   A new Location custom field has been added to Totara.
                   This can now be used anywhere custom fields can be used.
                   It allows a address to be entered and optionally displays a map with a pinned location that can be either the same as
                   the address or a secondary (dropped) location.

                   Please note that this location field uses the Google Map API in order to present a visual interactive map.
                   In order to use the visual map you will need to generate and provide Totara with a Google Maps API key.

    TL-7466        Added an optional regular expression validation for the "Text Input" custom field type.

                   Text Input custom fields can now be restricted to match particular format using PHP PCRE regular expressions.

    TL-7983        Moodle 3.0

                   This release of Totara contains new features and improvements introduced upstream in Moodle 3.0.0
                   Additionally fixes made in Moodle 3.0.0 through to and including 3.0.6 are included in this release.
                   For details please refer to the Moodle release notes.



New features - Seminar:

    TL-7461        The Face-to-face activity has been renamed to Seminar

                   All strings used in the interface that contained 'Face-to-face' have been updated.

                   Previously, a 'Face-to-face' activity contained a number of 'Sessions', which each contained some amount of 'Session
                   dates'.
                   'Sessions' have been renamed as 'Events' and 'Session dates' are now called 'Sessions'.
                   Strings containing these terms have been updated.

                   Face-to-face report sources and embedded reports have also had their names updated to reflect these changes, this is
                   only a display change and should not effect any existing reports.

                   There will be some language strings which still contain the old terms. These should not be used anywhere on the
                   interface. They are used to identify old notification messages and placeholders in Seminar notifications.

    TL-7453        New interface for bulk add/remove of users in Seminar events

                   Users in Seminar events are now added or removed in two steps: preparing a list of users and confirmation.

                   Custom fields have been added to the Sign-ups report source. Also, sign-up custom fields can be populated in a CSV file
                   upload for each user that will be added to the session.

                   The Attendees tab on the attendees page now also uses an editable embedded report for displaying data.

    TL-7455        Added sign-in sheet to download for Seminar events

                   A sign-in sheet can now be downloaded from the attendees tab in a Seminar event, which contains a list of learners
                   booked for that sessions along with spaces for signatures. Details such as start/end times and location are included in
                   the header of the sign-in sheet.

    TL-7458        Sign-up periods added to Seminar events

                   Sign-up start and end times can be added to a Seminar event. These restrict when a user can book into an event and when
                   managers can approve sign-up requests. If the user views the event outside of the sign-up period, dates will be shown
                   for when they will be able to sign up.

                   Setting these dates is optional. If just the end is set, then users will be able to sign up any time prior to that time.
                   If just the start is set, then users will be able to sign up any time from then until the start of the first session.

                   These settings will not prevent a Seminar admin from adding or removing users via the attendees page.

    TL-7463        Added session assets and room improvements

                   Seminar sessions can have assets associated with them. These may represent equipment or other items that are used during
                   the course of the session, such as a laptop or projector. Assets can be available globally or created and used for one
                   event. Multiple assets can be assigned to each session (formerly 'session date', of which there may be one or more in an
                   event).

                   Rooms may now be assigned per session rather than per event. During upgrade, any rooms assigned to an event will be
                   assigned to all sessions within that event.

                   The 'datetimeknown' field has been dropped as part of this process. Whether or not the date/times are known for an event
                   is determined in code by whether there are session records (in the 'facetoface_session_dates' table) associated with
                   that event. If an event had the 'datetimeknown' field set to unknown (value of 0), then any data associated with that
                   event in the 'facetoface_session_dates' table will be dropped during upgrade.

                   Custom fields have been added for rooms and assets. The fields for a room's 'building' and 'address' have also been
                   converted to default custom fields. The fields for these values have been dropped from the 'facetoface_room' table. Any
                   data in these fields will be transferred to the newly generated room custom fields during upgrade.

                   Report sources have been added for rooms, room assignments, assets and asset assignments.

                   As a result of the change from rooms being per-event to per-session, a system has been added for looping through
                   sessions within an event when substituting placeholders in a notification email.
                   Within a notification the placeholders [#sessions] and [/sessions] respectively indicate the start and end of a repeated
                   section.
                   That section of the email will be repeated for each session. Within each repeat, placeholders that are associated with
                   per-session data will be substituted with data for a given session. See our help documentation for further explanation.

                   The placeholders [alldates], [session:room], [session:venue] and [session:location] have been deprecated. These will
                   only be removed from the message when an email is generated, but no data will be substituted in. Placeholders such as
                   [session:room] can be replaced using the system above and new placeholders added for this purpose, for this case, it
                   would be [session:room:name]. In place of [alldates], a loop will need to be created which defines session start/end
                   times and other information specifically. The full list of placeholders relevant to these loops are available in the
                   help documentation.

                   Existing placeholders in notifications: If a template or specific notification exactly matches the default template for
                   that message in 2.9, it will be replaced with the new default. Otherwise, existing templates will have any [alldates]
                   placeholders replaced with an equivalent loop during upgrade, meaning the same message details should appear in
                   notifications. However, placeholders for room, location and venue cannot be replaced automatically as their relation to
                   each Seminar event is changing. If you have any notifications using room placeholders (and they are not exactly the same
                   as the 2.9 default template), you will need to update the notification to use the new tags for looping through sessions.

    TL-7465        Added new approval options to Seminars

                   Previously the approval options were limited to "No approval required", "Approval required", or in a separate setting
                   "Self approval" with terms and conditions. These have all been grouped into one setting, "Approval required" has been
                   renamed to "Manager Approval", and two new approval options "Role Approval" and "Manager and Administrative Approval"
                   have been added.

                   Role Approval:
                   To enable role approval you first need to make the desired role assignable at the event level by selecting it in the
                   "Event roles" setting on the site administration > Seminars > Global settings page. When the role is assignable a
                   corresponding option will be displayed under the available approval options setting on the same page, you may have to
                   save the page before it shows up. When a Seminar is using a role approval option, approval requests will be sent to
                   everyone assigned with the specified role in the seminar event. Any of those same users can then go to the seminars
                   pending requests tab and approve or deny the requests.

                   Manager and Administrative Approval:
                   This is a 2 step approval option. First the request must be approved by their manager, the same as "manager approval".
                   Then they must be approved by a "Seminar Administrator". Seminar administrators are a combination of site level
                   administrators and seminar (activity) level administrators. Site level administrators are users with the
                   "mod/facetoface:approveanyrequest" capability at site level, who have been selected in the "Site level administrative
                   approvers" setting on the site administration > Seminars > Global settings page. Seminar level administrators are any
                   user who has been selected with the "add approver" dialogue on the edit seminars page underneath the "admin approval"
                   option. When a user requests signup with this setting their manager and all of the administrators are sent the request,
                   the manager must first approve the request, then any one of the admins must approve it.

                   Another new setting on the site administration > Seminars > Global settings page, is the "Users select manager" setting.
                   This setting is for when manager approval is required but manager assignment data is not available. It prompts the user
                   to select their manager from a dialogue when they attempt to sign up, the selected user is then treated as their manager
                   for the rest of the approval process.

    TL-7944        Added seminar minimum bookings status and notification

                   This utilises a minimum booking setting that allows the user managing a Seminar activity to specify the minimum number
                   of attendees required for the event.
                   The value of this setting is then used when reporting on the status of the event, and can be used to trigger
                   notifications.

    TL-8187        Seminar events can now be cancelled

                   Seminar events can be cancelled by a Seminar admin. The event's information will remain, allowing it to be viewed in
                   reports. However, users will not be able to book into the event and the event's details can not be updated.

    TL-9675        Any one of a user's managers can approve their Seminar signup

                   The setting 'Select job assignment on sign-up' replaces 'Select position on sign-up'. With this setting on, a learner
                   with multiple job assignments will need to choose which job assignment they are signing up under.

                   Where learners have multiple managers (by having multiple job assignments), there are several possible scenarios for
                   sending out Seminar notifications to managers  (such as approval requests):

                   1: If the setting 'Select job assignment on sign-up' is on, notifications will go to the manager related to job
                      assignment chosen by the user.
                   2: If the setting 'Select job assignment on sign-up' is off, notifications will go to all of the user's managers.
                   3: If the 'Users select manager' site setting is turned on, then regardless of whether a job assignment was selected,
                      manager notifications will go to the manager chosen during sign-up. For more information on the 'User selects manager'
                      setting, see change log entry TL-7465.



New features - Multiple jobs:

    TL-2082        Multiple job assignments

                   Multiple job assignments can be defined for each user. Existing data in primary and secondary positions was converted to
                   job assignments. See the related changelog entries for specific uses of this feature.

    TL-9513        Added "Allow multiple job assignments" setting

                   By default, multiple job assignments are enabled.
                   By disabling this setting, only one job assignment can be created for each user.
                   HR Import will also prevent uploading multiple job assignments for a single user when disabled.
                   Any existing multiple jobs within the system when the setting is disabled will still remain, they will not be
                   automatically removed. They will continue to function until they are manually removed.

    TL-8946        HR Import now supports Job Assignments

                   The HR Import User source has been changed to import into job assignments, rather than the deprecated position
                   assignments:

                   *  The option "Link job assignments" has been added to the User source. The effects of this option are detailed below.
                   *  The column "Job assignment ID Number" has been added. This field relates to the ID Number field in the user's job
                      assignments. If "Link job assignments" is set "to the user's first job assignment" when you import user records, the
                      imported record will be linked to the user's "first" job assignment, and if the "Job assignment ID Number" field is
                      included then it will be updated with the specified value. If "Link job assignments" is "using the user's job
                      assignment ID number" when you import user records, the imported records will be linked to the user's existing job
                      assignment records using the ID Number.
                   *  If job assignment data is specified in the import and the matching job assignment record does not already exist, it
                      will be created.
                   *  The column "Manager's job assignment ID Number" has been added. This is required when "using the user's job
                      assignment ID number". If specified, the specific job belonging to the manager will be linked to the user's job
                      assignment. If "Link job assignments" is set "to the user's first job assignment", the manager's "first" job
                      assignment will be linked to the user's job assignment.
                   *  The column "Position title" has been renamed "Job assignment full name".
                   *  The columns "Position start date" and "Position end date" were renamed "Job assignment start date" and "Job
                      assignment end date".

                   Special care should be taken by sites upgrading to Totara 9.0 who used HR Import to populate the old "primary position",
                   and who wish to import multiple job assignments. During upgrade, all existing position assignments were made into job
                   assignments. Their ID Numbers were set to their location in the users' job assignment list. It is important to note that
                   after upgrading, the location in the list of job assignments is not connected to the ID Number - if a user's first and
                   second just assignments are swapped, it would result in the first job assignment having ID Number "2" and the second
                   would have "1". Therefore, after upgrading, before the site goes live, if your HR management system already has a system
                   of ID Numbers used to identify job assignments, and you want to use this value rather than the default "1" and "2", then
                   you should perform the following steps:

                   1. Upgrade to Totara 9.0 or later.
                   2. With "Link job assignments" set "to the user's first job assignment" and column "Job assignment ID Number" enabled,
                      import the ID Numbers from your HR management system into the "first" job assignments (previously primary position
                      assignments). Note that your import should only contain the "primary" job assignment for each user at this stage.
                   3. At this stage, your "first" job assignments have the ID Number from your HR management system.
                   4. If you had previously set up old "secondary position assignments", they will now exist as "second" job assignments
                      with ID number "2". You now need to manually update the ID Number in each of them to the ID number from your HR
                      management system.
                   5. Finally, set "Link job assignments" to "using the user's job assignment ID number". After the first import using this
                      setting, it will not be possible to change it back (the setting will be removed from the interface). The "Job
                      assignment ID Number" column will be required when importing job assignment data. Any number of job assignments, each
                      identified by the "Job assignment ID Number" field, can now be imported in a single upload.

    TL-8947        Updated the embedded "My team" report to work with multiple job assignments

                   The embedded "My Team" report has been updated to show all of the manager's staff members across all of their job
                   assignments.

    TL-8948        Updated the dynamic audience rules to work with multiple job assignments

                   Dynamic audience rules based on position assignments previously only applied to users primary positions, these have been
                   replaced with new job assignment rules that apply across all of a user's job assignments. This means that for sites
                   currently only using primary and not secondary position assignments, existing audience membership will not change on
                   upgrade. However sites currently using secondary position assignments might see audience memberships change on upgrade.
                   If you do not want secondary position data to be considered when reviewing dynamic audience memberships then you will
                   need to remove secondary position data from your users prior to upgrade.

                   The position and organisation audience rules have been combined under a new "All Job Assignments" header, the affected
                   rules are:

                   *  Titles (New - The job assignment full name)
                   *  Start dates (Previously: Position Start Date)
                   *  End dates (Previously: Position End Date)
                   *  Positions
                   *  Position Names
                   *  Position ID Numbers
                   *  Position Assignment Dates
                   *  Position Types
                   *  Position Custom Fields
                   *  Organisations
                   *  Organisation Names
                   *  Organisation ID Numbers
                   *  Organisation Types
                   *  Organisation Custom Fields
                   *  Managers
                   *  Has Direct Reports

    TL-8949        Updated the report builder columns, filters, and content options to work with multiple job assignments

                   Report builder columns, filters and content options based on a users position assignment previously only displayed or
                   checked the users primary positions.
                   These have been replaced with new job assignment columns, filters and content options that apply across all of a users
                   job assignments. The new columns are concatenated and display one job assignment field per line, with a spacer "-"
                   displayed for empty fields. The new filters now check whether any of the users job assignments match the specified
                   constraints, and if so will display all the data. These columns have been moved from the "User" header and are all now
                   located under a new "All User's Job Assignments" header to make them easier to find. The User, Organisation, and
                   Position content options are now applied across all of the users job assignments. This means if a report has a content
                   option set to "Show user's direct reports" and a user has three job assignments, all three managers would see the user
                   when viewing the report.

                   Note: Concatenated columns can not be sorted, and there are no concatenated text area columns or filters since there is
                   no way to display them inline. Any existing columns or filters for position and organisation text area custom fields, or
                   framework descriptions, will be lost on upgrade.

    TL-9524        Managers are selected via their job assignment

                   When assigning a manager to a staff member, both the manager and the job related to that manager-staff relationship can
                   be selected.

                   If the user has the 'totara/hierarchy:assignuserposition' capability in the manager's context or in the system context,
                   they will be able to create an empty job assignment for the manager within the selection dialog and assign that as the
                   job that the manager has this staff member under.

                   As part of this change, the selector dialog for choosing a manager or temporary manager will now return a job assignment
                   id as well as a manager id. The job assignment id can only be empty when the user has the capability described above, in
                   which case the new job assignment will be created.

                   If a user selecting a manager does not have permission to create the empty job assignment, and the manager currently has
                   no job assignments, a new job assignment must be created by someone with permission to do so before that manager can be
                   assigned a staff member.

    TL-9531        Updated program relative due dates to work with multiple job assignments

                   The relative to 'position start date' rule has been renamed to 'job assignment start date' and, along with the relative
                   to 'position assigned date' rule, has been changed to work across all of a users job assignments. When a user has the
                   same position in several job assignments, the maximum due date will be selected.

    TL-9677        Appraisals have been modified to work with the new job assignments feature.

                   Take note that the workflow for appraisals has changed:
                   1. Create appraisal; assign appraisees; fill in the content as per normal
                   2. The following has changed when an appraisal is activated:
                       *  Previously the system warned against missing roles and disallowed activation if it was a static appraisal. In 9.0,
                          there is no longer a check here for missing roles since that is tied in with the job assignment an appraisee links
                          to his appraisal.
                       *  The job assignment is linked only when the appraisee sees the appraisal for the very first time
                       *  Now, as long as the appraisal has assigned appraisees and questions, it can be activated, even if the appraisal is
                          static.
                   3. The following has changed when an appraisee opens their appraisal:
                       *  If the appraisee has multiple existing job assignments then they must now select a job assignment to link to the
                          appraisal. They cannot proceed with the appraisal until a job assignment is selected.
                       *  If the appraisee has only one job assignment then it is automatically linked to the appraisal.
                       *  If the appraisee doesn't have an existing job assignment then a "default" one is created and automatically linked to
                          the appraisal.
                       *  Only now does the system register the managers and appraisers based on the job assignment the appraisee selects.
                   4. The rest of the appraisal workflow is the same as in previous versions.

    TL-9468        Converted the user aspirational position into a user profile field

                   On upgrade to 9, aspirational position data will be migrated into the new profile field. This is because it behaves
                   differently to existing job assignments and serves a different purpose (a target position, rather than current one).

                   At present, aspirational position data is informational only, however in the future we may add gap analysis
                   functionality that makes use of this value.



Improvements:

    TL-5143        Added job assignment title as a filter for dynamic audiences

                   This was originally contributed by Aldo Paradiso to allow dynamic audiences based on primary position titles.  However,
                   with the new job assignments feature in 9.0, this has been reworked to use job assignment titles instead - specifically
                   job assignment full names.

    TL-5946        Certification block now uses its own duedate string

                   Previously the certification block was using strings belonging to programs.
                   It now has its own versions of these strings, allowing them to be translated with specific reference to the block.

    TL-6243        Added the ability to automatically create learning plans when new members are added to an audience

                   This improvement extends the manual learning plan creation within an audience by saving the creation configuration
                   settings and dynamically creating learning plans when new members are added to the audience, based on the saved
                   configuration.

    TL-6578        Fixed dynamic audiences with user profile custom fields so default field values work

                   It is possible to use checkbox, menu and text custom fields within dynamic audience rules for user profiles. However,
                   the default values for these fields have never been considered when computing the membership of the dynamic audience.
                   This fix allows the system to do so.
                   Take note: this fix may cause audience changes upon upgrading.

    TL-6643        SCORM serving scripts now have explicit HTTP headers to consistently prevent caching problems
    TL-6879        Changed HR import scheduling to use scheduled tasks scheduling

                   Scheduling now uses the core scheduled task scheduling, allowing for more fine grained control of scheduling. The UI on
                   within HR Import hasn't changed but will be disabled if the schedule is too complex to be displayed. A complex schedule
                   can be created using the scheduled task scheduler.

    TL-7060        Made appraisals CSS classes more specific
    TL-7332        Removed appraisal question options for disabled functionality

                   Previously you could add "course from plan" review questions to your appraisal even if learning plans had been disabled
                   for your site, these types of question should now only be available if the associated feature(s) are enabled.

    TL-7386        Improved the run time of report builder column unit tests
    TL-7452        Improved Seminar navigation

                   The Seminar administration menu has been moved from the plugins menu (Site administration > Plugins > Activity modules >
                   Seminar) to the Site administration root (Site administration > Seminar).
                   Additionally the settings have been regrouped depending upon the scope of their influence, for example the "Available
                   approval options" setting is located under "Global settings" as it determines the available options for all Seminars.

    TL-7459        Added links to further information on each room when signing up
    TL-7460        Improved Seminar summary report source

                   Seminar summary report has a number of new columns and filters that allows the user to get more detailed information
                   about Seminar events.

    TL-7621        Seminar add/remove attendees selectors now use the user identity setting
    TL-7795        Improved the handling of signup requests when switching approval types

                   Fixed user's status code from REQUESTED to BOOKED/WAITLISTED depending from session capacity when Seminar is changed
                   from approval required to not

    TL-7914        Removed report builder filter HTML when no filters are displayed
    TL-7963        Added the ability to use custom fields within evidence

                   On upgrade the description, evidencelink, institution and datecompleted database dp_plan_evidence fields will be deleted
                   with their data being transferred into new evidence custom fields.

    TL-8023        Added a new option "All courses are optional" to program coursesets

                   You can now define program course sets that don't require any of the courses to be complete. Note that the course set
                   will automatically be marked as complete when it is reached and will potentially contribute towards the progress in the
                   program. If you use this option with OR or THEN operators, you might find that portions of the program are automatically
                   completed before the user is required to do anything.

    TL-8043        Improved capability handling on the Tag Management pages
    TL-8116        Extended the file input custom field to support multiple files
    TL-8118        Added the ability to import evidence custom field data in the completion Import tool

                   Evidence Custom field data can be included in the import by adding columns to the CSV file named with the prefix
                   'customfield_' followed by the custom field shortname.

                   Custom fields of type 'file' and 'multiselect' can not be uploaded.

                   On install or upgrade, an evidence 'Date Completed' custom field is created. This field will be used to store the
                   completiondate value from the CSV upload file.

                   Also, on install or upgrade,  an evidence 'Description' field is created. If this field is specified in the CSV upload
                   file, the content from the CSV upload file will be used. If the content is empty, the default value set in the custom
                   'Description' field will be used. If the 'Description'  field is not specified in the CSV upload file, an auto-generated
                   description will be created, based on the course completion data and stored in the 'Description' field.

    TL-8168        Improved method of sending Seminar calendar events

                   Previously the events were sent as ics file attachments, this practice is not recommended any more for security reasons.
                   The calendar event is now embedded in the email.

    TL-8181        Improved alignment in the add/edit scheduled report form
    TL-8182        Re-implemented manual course completion archiving

                   You can manually archive all completions for a course by going to Course administration > completions archive page, this
                   has been restricted to courses that are not part of programs or certifications to avoid flow on effects.

    TL-8186        Added user first_name string as placeholder for new user welcome email
    TL-8272        Dashboards can now be cloned

                   The ability to clone dashboards has been added to Totara 9.0
                   Cloning a dashboard creates a copy of the original dashboard, the blocks that have been added to it and any audience
                   assignments that have been made.
                   Cloning does not copy any user customisations to the dashboard.

    TL-8383        Activity completion reaggregated during scheduled task after unlock and delete

                   Previously, when activity completion was unlocked and deleted, completions were recalculated immediately (based on
                   grades already attained for example). However this led to performance problems given it was being recalculated for each
                   user.

                   Activity completions will now be set to incomplete (rather than deleted) immediately and will then be flagged for
                   reaggregation. The reaggregation will happen on the following run of the \core\task\completion_regular_task which, by
                   default, is run every minute.

                   With completions being set to incomplete rather than deleted, this means the record remains (where it was previously
                   just deleted). This has the advantage of retaining whether an activity was viewed by a user (if that was recorded).  If
                   an activity was manually completed, this information is still not retained.

                   When the state is set to incomplete, the timecompleted field is also set to null (if it wasn't already).

                   A new field, named 'reaggregate', has been added to the course_modules_completion table. This is an integer field for
                   storing the UTC timestamp of when it is set.

    TL-8413        Enabled URL custom fields to display as working links on view Hierarchy details page
    TL-8459        Added landing page for Session Attendees to allow user to select Seminar event attendees and then view a report.
    TL-8465        Added 'Sign-up Period' column to Seminar Events and Sessions reports
    TL-8469        Added Seminar Event registration expired notification
    TL-8497        The language menu is now displayed on the login page if multiple languages are installed
    TL-8515        Reorganised the main menu navigation defaults and dashboard default blocks

                   The main menu has been reorganised with new naming and simplified content. On upgrade the existing content will be left
                   but the new menu can be selected by clicking on "reset menu to default configuration" in "Appearance > Main menu".

                   The My Learning page has been removed. The contents has been moved into a "Legacy My Learning" Totara dashboard which is
                   hidden by default. There is a new My Learning dashboard shown to all logged in users which has new default content. You
                   can switch back to the old My learning by changing the visibility settings on "Appearance > Dashboards".

                   The defaulthomepage options have been simplified.

    TL-8516        Added "Visible to all users" option to Dashboards which makes it accessible to every logged in user
    TL-8569        Applied correct style to "Join Waitlist" link for a Seminar event.
    TL-8578        Changed Seminar signup page to show overlapping signup warning
    TL-8636        Added pagination when reviewing attendees to be added or removed from a Seminar session
    TL-8637        Removed "Face-to-face" block

                   This block's functionality is superseded by current Seminar Reportbuilder sources.

    TL-8647        Added option to specify behaviour of empty strings in HR Import

                   When importing data from CSV files, there is a new option to determine the behaviour of empty strings. Option "Empty
                   strings are ignored" means that any empty field in the import will be ignored and existing data will be unchanged, while
                   option "Empty strings erase existing data" means that any empty field in the import will cause the existing data in that
                   field to be erased.

                   External database sources are unaffected by this change. Fields set to null will leave the existing data unchanged while
                   empty strings will cause the existing data to be erased.

    TL-8652        Improved URL references in the template library

                   Previously URL references in the template library were largely dependant on the developers setup. With this change, URLs
                   are adjusted to suit whatever the setup is of the person viewing the template library (assuming the mustache template
                   uses __WWWROOT__ and __THEME__ placeholders, as created when using
                   \tool_templatelibrary\exampledata_formatter::to_json())

    TL-8713        Improved diagnostic messages in scheduled report task execution
    TL-8724        Improved the user workflow when completing actions within a Seminar activity
    TL-8767        Rephrased help text for room scheduling conflict option
    TL-8779        Removed non-standard 'back to' navigation links from Learning Plans and Record of Learning
    TL-8786        Refactored Seminar asset and room "allow conflicts" data storage
    TL-8814        Made Seminar Room name a required field
    TL-8853        Improved a number of JavaScript modules to meet current coding standards

                   The following JS modules have been improved to meet current coding standards:

                   *  Category manager
                   *  Acceptance testing JS
                   *  Reportbuilder graphical reporting

    TL-8863        Introduced a new API for adding custom role access restrictions in Reportbuilder

                   Previously the role access restrictions were implemented in Report builder lib.php file. This meant that plugin
                   developers could not add custom restrictions without modifying the report builder.

                   All access restriction code was refactored to to use new rb\access class namespace and a new discovery mechanism was
                   added to allow any plugin to define new access restriction classes for all other reports. See
                   totara/reportbuilder/classes/rb/access/base.php file for more information.

                   All pre-existing role access code customisations need to be updated to use this new API.

    TL-8871        Improved category expander JavaScript to meet current coding standards
    TL-8886        Moved Seminar Default minimum bookings setting to Event defaults
    TL-8892        Fixed sign-up time column option in Seminar events report builder
    TL-8895        Added Events report source and embedded report
    TL-8900        Added "Description of regular expression validation format" option to text input custom field
    TL-8915        Created a "booking options" section when adding Seminar event attendees
    TL-8940        Improved display of capacity for waitlisted users of waitlisted events
    TL-8950        Learning plan approval requests are now sent to all managers

                   With the ability to have several managers by having multiple job assignments, approval requests and other learning plan
                   notifications will now go to all of the learner's managers. All of the learner's managers will have the ability to
                   approve, decline or delete a user's learning plan or its components.

    TL-8968        Converted course edit form customisations to proper hooks

                   This change introduced three new hooks:

                   1. \core_course\hook\edit_form_definition_complete
                      Gets called at the end of the course_edit_form definition.
                      Through this watcher we can make any adjustments to the form definition we want, including adding Totara specific
                      elements.

                   2. \core_course\hook\edit_form_save_changes
                      Gets called after the form has been submitted and the initial saving has been done, before the user is redirected.
                      Through this watcher we can save any custom element data we need to.

                   3. \core_course\hook\edit_form_display
                      Gets called immediately before the form is displayed and is used to initialise any required JS.

    TL-9123        Added rel="noreferrer" to links being displayed by URL custom fields
    TL-9127        Added Grunt tasks for working with Less

                   Less may be used to author plugin styles by providing a less directory containing a styles.less file e.g.
                   local/myplugin/less/styles.less. Theme less files if found are compiled and a RTL equivalent generated.

    TL-9141        Added back-end validation methods to date/time custom fields.
    TL-9156        Removed the setting "Users can enter requests when signing up" from the Seminar module

                   The setting "Users can enter requests when signing up" previously governed whether the ability to add text (a 'sign-up
                   note') was available to the user when they signed up. Options like this are now based on whether there are event custom
                   fields enabled.

    TL-9237        Added support for unique index on nullable column
    TL-9256        Improved directory removal to prevent race conditions from file and directory removal
    TL-9259        Theme RTL stylesheets are now served separately

                   This update allows developers to provide a RTL equivalent for any theme stylesheet by providing a file with an -rtl
                   suffix e.g. totara.css and totara-rtl.css. Where found the RTL theme sheet will be cached and served instead for RTL
                   languages.

    TL-9267        Improved display of SCORM in popup windows
    TL-9274        Review and clean up of all old Totara update code

                   All Totara upgrade code has been reviewed and tidied up to greatly reduce the likelihood of upgrade conflicts being
                   encountered.

                   The following changes have been made:

                   *  The totara_core upgrade is now executed immediately after core upgrade and install before any other plugins
                   *  There are only two Totara pre upgrade scripts - one executed once before migration from Moodle and other gets
                      executed before every Totara upgrade
                   *  Totara blocks are using standard install.php scripts
                   *  All language packs are updated only once
                   *  The upgrade sections were renamed so that the first heading is "Totara", then "System", then  "totara_core" and then
                      the rest of plugins
                   *  Custom field capabilities were cleaned up
                   *  Migration of badge capabilities from old installations was improved
                   *  Added detection and automatic fixing of capabilities migrated from plugins to core
                   *  Removed unnecessary upgrade flags from totara_core and totara_cohort
                   *  Fixed totara_cohort upgrade.php to use $oldversion instead of previous release hack
                   *  Removed code duplication from totara_upgrade_mod_savepoint()
                   *  Moved totara_completionimport upgrade code to totara_completionimport
                   *  Fixed incorrect include in Totara menu upgrade

    TL-9322        Provided Less compilation --themedir option

                   Allows a developer compiling Less via Grunt to pass an optional parameter 'themedir' to add a custom theme directory (as
                   per $CFG->themedir) to the Less import search path.

    TL-9353        Increased the Seminars CALENDAR_MAX_NAME_LENGTH constant

                   Previously seminar names were being truncated to 32 characters while creating calendar events, this has been changed to
                   256 characters. And can now be overridden in config.php using "define('CALENDAR_MAX_NAME_LENGTH', 32);" if you want to
                   continue shortening the name.
                   Note: this will not change any existing calendar events, but can be updated by editing the associated seminar.

    TL-9430        Added the ability to include partials dynamically into a Mustache template

                   This allows a list of items to be displayed differently depending on the type of an item using partials within a
                   Mustache template.

    TL-9514        Report builder scheduled report email message string 'scheduledreportmessage' now supports markdown format including html tags
    TL-9539        Embedded reports and sources may override exported report headers

                   Developers may now customise headers in exported reports by adding new method get_custom_export_header() to the embedded
                   report or source class. Non-null value overrides the standard report header in exported reports.

    TL-9676        Updated the programs extension requests to work with multiple job assignments

                   When a user requests an extension, messages will now be sent to all of the user's managers, across all of their job
                   assignments. Any of the managers can then go and approve or deny the extension request.

    TL-9727        Manager's job assignment is selected when assigning program via management hierarchy

                   When assigning management hierarchies to a program, one or more of the manager's job assignments must be selected. Only
                   staff managed under the selected job assignments will be assigned to the program via this method.

                   Any custom code using assignment via management hierarchy must use the ASSIGNTYPE_MANAGERJA constant (which has a value
                   of 6) rather than the ASSIGNTYPE_MANAGER constant (which as a value of 4). You will need to ensure that any calls to
                   Totara functions using this assignment type use the manager's job assignment id in place of where the manager's user id
                   was being used. Data in the prog_assignment table will updated during upgrade.

    TL-9765        Improved template handling in situations where the active theme no longer exists
    TL-9774        Use system fonts

                   This change updates the Roots theme to use the appropriate sans-serif system font for the user's operating system. This
                   ensures that the font is clear, readable and available, and that languages such as Hebrew and Arabic will be displayed
                   correctly.

    TL-9791        Converted the main menu to use use renderers

                   The main menu can now be overridden using Mustache templates improving the ease of customisation

    TL-9792        Visual improvements to the admin notification page to bring it inline with the new themes
    TL-9805        Changed the default theme to Basis
    TL-9809        Updated PHP doc for has_config() method
    TL-9817        Improved displaying of custom field dates
    TL-9839        Deprecated broad CSS in Appraisals
    TL-9866        Standard Totara Responsive now aligns form labels to the left
    TL-9944        URL type custom fields can now be imported through HR Import
    TL-9979        Fixed the constructor of the QuickForm custom renderer class for PHP7
    TL-9995        Reportbuilder titles are now filtered for multilingual content
    TL-10019       Fixed visual display of SCORM reports
    TL-10032       Assignments to programs and certifications take into account all of a user's job assignments

                   With the addition of the multiple job assignments functionality, assignments to a program or certification via position,
                   organisation or management hierarchy will include users with the relevant settings (such as organisation) in any of
                   their job assignments.

                   When upgrading, be aware that any positions, organisations and manager assignments in a users second job assignment
                   (formerly called secondary position assignment) may lead to users being assigned to additional programs and
                   certifications.

    TL-10115       Improved the display of the miniature calendar used in the calendar block and calendar interface

                   Calendar plugins can now specify an abbreviated calendar name within the structure::get_weekdays method. This should be
                   the shortest possible abbreviation of the day, and is used predominantly within the calendar when displayed as a block
                   in a small space.

    TL-10130       Re-approval for a previously declined Seminar event no longer results in a debugging message
    TL-10137       A warning about deleted fields has been added to user source configuration in HR Import

                   HR Import will allow you to add job assignments via a User source. The deleted field contained in this source only
                   applies to deleting users and should not be used for attempting to delete job assignment data.

                   HR Import currently only allows job assignment records to be added or updated. It does not allow deletion of job
                   assignment records. However that can be achieved via a user's profile.

                   A warning regarding this has been added to the user source configuration in HR Import. However, anyone who may edit HR
                   Import data sources should also be made aware of this information.

    TL-10182       Fixed a number of layout and appearance issues on the course management page.
    TL-10402       Removed calendar icons from frozen form elements
    TL-10429       'Process messages and alerts' task was split in to 'Dismiss alerts and tasks after 30 days' and 'Cleanup messaging related data'
    TL-10452       Minimum required browser version were increased

                   It is recommended to use only browser that are supported by their manufacturer, minimum versions for Totara are:

                   *  recent Chrome
                   *  recent Firefox
                   *  Safari 9
                   *  Internet Explorer 9

    TL-10597       Improved program and certification progress calculation to exclude 'Optional' courses
    TL-10655       Hyphen and full-stop characters are now valid within the Reportbuilder scheduled report export path setting
    TL-10704       Ensured hierarchy AJAX is disabled when hierarchies have been disabled in Advanced features
    TL-10908       All current Bootstrap 2 themes have been deprecated

                   With the arrival of the new Bootstrap 3 themes we have deprecated the current Bootstrap 2 themes.
                   This includes the following themes:

                   *  Bootstrap Base
                   *  Standard Totara Responsive
                   *  Custom Totara Responsive
                   *  Kiwifruit Responsive

                   Deprecation is the first step towards eventual removal. These themes will remain in core for one more major release and
                   may then be moved out of Totara core.
                   They will still be made available for those who want to use them, but will not longer receive regular updates.


Accessibility improvements:

    TL-6292        Improved the HTML markup used when viewing Reportbuilder reports

                   Previously Reportbuilder reports had all the controls inside an HTML table, and when there were no results, it was
                   displayed as a table with one cell. This improvement moves the controls outside of the report table, and replaces the
                   table when there are no results. Themes may have to have their CSS changed to accommodate this.

    TL-7740        The add/remove site administrators user interface no longer uses HTML tables for layout
    TL-7742        The lanugage pack importer user interface no longer uses HTML tables for layout
    TL-7837        The add/remove role user interface no longer uses HTML tables for layout
    TL-7840        The user interface for assigning user's to audiences no longer uses HTML tables for layout
    TL-7843        The user interface for managing group membership no longer uses HTML tables for layout
    TL-7861        The Seminar event allocation user interface no longer uses HTML tables for layout
    TL-7863        The Seminar add/remove attendees user interface no longer uses HTML tables for layout
    TL-7875        The user interface for managing groupings within a course no longer uses HTML tables for layout
    TL-7877        The Feedback activity add/remove user's user interface no longer uses HTML tables for layout
    TL-7880        The user selector used for enrolling users into a course no longer uses HTML tables for layout
    TL-7882        The user interface for managing webservice user's no longer uses HTML tables for layout
    TL-7885        The user interface for awarding badges no longer uses an HTML table for layout
    TL-7890        The user interface for managing Forum subscribers no longer uses HTML tables for layout
    TL-7924        The audience rules user interface no longer uses HTML tables for layout
    TL-7996        Removed duplicate HTML id's when adding linked competencies
    TL-8530        Removed tables for layout when viewing items from a learning plan
    TL-8598        Improved Aria accessibility experience within Seminar
    TL-8913        Improved accessibility when removing users from a feedback360 request
    TL-9164        Linked custom URL field text to their appropriate input elements
    TL-10241       Added alternative text to badge images
    TL-10259       Improved accessibility when using multi check filters in report builder reports
    TL-10262       Improved the HTML markup on the My Badges page
    TL-10263       Added a legend when searching your badges
    TL-10264       Linked reason input field in a learning plan to a label
    TL-10265       Added a legend to the report builder export functionality
    TL-10269       The completion status interface within the course completion status block no longer uses an HTML table for layout
    TL-10271       Improved accessibility around creating a calendar event
    TL-10272       Added a label to the move forum post dropdown
    TL-10277       Removed labels that were not required when displaying date fields
    TL-10279       Improved accessibility when adding groups of users to a goal
    TL-10283       Replaced unnecessary headings in badges.
    TL-10309       Added accessible label when viewing custom checkboxes on a users profile


Technology conversions:

    TL-7947        Converted totara_program_completion/block.js to an AMD module

                   The M.block_totara_program_completion JavaScript code has been converted from a statically loaded JS file to an AMD
                   module.
                   This allows the JS to be loaded dynamically with much greater ease and unlocks the benefits AMD brings such as
                   minification and organisation.

                   This change removes the block/totara_program_completion/block.js file.

    TL-7950        The JavaScript loaded by the report table block has been converted to an AMD module and is only loaded when required
    TL-7951        The JavaScript loaded by element library has been converted to an AMD module
    TL-7959        The instance filter as used in the report builder been converted to an AMD module
    TL-7961        The cache now JavaScript as used in the report builder been converted to an AMD module
    TL-7987        Converted block rendering to use templates
    TL-8009        Converted totara_print_active_users to templates
    TL-8011        Converted is_registered method to use templates
    TL-8012        Converted print_totara_search method to use templates
    TL-8013        Converted print_totara_notifications method to use templates
    TL-8014        Converted print_totara_progressbar method to use templates
    TL-8015        Converted comment_template method to use templates
    TL-8016        Converted print_toolbars method to use templates
    TL-8017        Converted print_totara_menu method to use templates
    TL-8018        Converted totara_print_errorlog_link method to use templates
    TL-8019        Converted display_course_progress_icon to use templates.
    TL-8031        Converted print_my_team_nav to use templates.
    TL-8034        Converted print_report_manager to use templates
    TL-8035        Converted print_scheduled_reports to use templates.
    TL-8036        Converted print_icons_list to use templates.
    TL-8084        Converted html_table and html_writer::table to use templates
    TL-8200        Converted the My Reports page to use templates
    TL-8304        Converted Manage types pages to Mustache templates
    TL-8305        Converted manage frameworks to Mustache templates
    TL-8330        Converted print_competency_view_evidence to use a Mustache template
    TL-8331        Converted competency_view_related to use a template
    TL-8332        Converted goal assignment to Mustache templates
    TL-8334        Converted assigned goals to use a Mustache template
    TL-8335        Converted mygoals_company_table to use Mustache templates
    TL-8337        Converted mygoals_personal_table to use a Mustache template
    TL-8448        Converted render_single_button function to use templates
    TL-9070        Converted filter_dialogs to an AMD module
    TL-9192        Converted competency JavaScript functionality to AMD modules


API changes:

    TL-7473        Custom menu has been deprecated

                   The custom menu that could be edited through Site Administration > Appearance > Themes > Theme settings has been
                   deprecated. You may need to convert this into the functionality as provided by Site Administration > Appearance > Main
                   menu

    TL-7817        Added support for AMD modules to the jQuery DataTables plugin

                   In Totara 2.9, a small customisation was introduced to the jQuery DataTables library removing AMD support. This
                   customisation has now been removed (and updated the library to 1.10.11 in the process) as we have updated our JavaScript
                   to AMD modules.

                   To convert JavaScript functionality to support jQuery DataTables, ensure your JavaScript is written as an AMD module and
                   require both 'jquery' and 'totara_core/datatables' in your module.

    TL-7981        Removed unused preferred_width function from blocks
    TL-7982        Removed unnecessary calls to local_js

                   Previously (in Totara 2.7 and below) making a call to local_js would load jQuery into the page. As of Totara 2.9, jQuery
                   is loaded by default on every page so this call is no longer needed if no jQuery plugins are required. This fix removed
                   all requests to local_js with no parameters.

    TL-8004        Separated Totara specific CSS from bootstrap bases theme
    TL-8027        Function function_or_method_exists() was deprecated
    TL-8055        Removed references to some deprecated functions
    TL-8057        Added element id to static and hard-frozen form field containers

                   This allows them to be addressed in JavaScript and by CSS, the same as non-static and non-hard-frozen field containers.

    TL-8071        Moved Hierarchy JavaScript from inline to an AMD module

                   Previously there was a large amount of JavaScript that was written in the HTML (instead of being in an external
                   JavaScript file) in Hierarchies. These have been replaced with 2 AMD modules

    TL-8188        Totara LMS 9 now supports PHP7

                   Note that support for PHP7 has not been backported to the stable release.
                   Any stable releases still have a maximum version of PHP5.6.

    TL-8369        html_writer should implement templatable
    TL-8405        JQuery updated to 2.2.0
    TL-8537        Fixed a redirection issue when signing up to a Seminar event
    TL-8562        Created export_for_template method on single_button class
    TL-8958        Added new API for lookup of classes in namespace
    TL-8998        Imported latest SVGGraph library
    TL-9101        Deprecated the totara_message_accept_reject_action function

                   This function is not used within core Totara code and has been deprecated. It will be removed in Totara 10.0

    TL-9826        Created new 'I switch to "tab" tab' behat step


API change details:

    TL-6292        *  totara_table::print_extended_headers() has been removed
    TL-6874        *  New \core\output\flex_icon class to allow use of font icons.
                   *  New pluginname/pix/flex_icons.php file to define and alias icons.
    TL-6879        *  admin/tool/totara_sync/lib.php tool_totara_sync_run first argument has been removed
    TL-7060        *  CSS class "stagelist" has been renamed "appraisal-stagelist"
                   *  CSS class "stagetitle" has been renamed "appraisal-stagetitle"
                   *  CSS class "stageinfo" has been renamed "appraisal-stageinfo"
                   *  CSS class "stageactions" has been renamed "appraisal-stageactions"
    TL-7452        *  Removed table facetoface_notice
                   *  Removed table facetoface_notice_data
                   *  Removed unused function facetoface_get_manageremailformat
                   *  Removed unused function facetoface_check_manageremail
                   *  Removed unused class mod_facetoface_sitenotice_form
    TL-7453        *  rb_param_option::$type is deprecated and will be removed in future versions
                   *  facetoface_print_session function added sixth argument $class
    TL-7455        *  rb_source_facetoface_summary::rb_display_link_f2f method is removed
                   *  rb_source_facetoface_summary::rb_display_link_f2f_session is removed
                   *  rb_source_facetoface_sessions::rb_display_facetoface_status method is removed
                   *  rb_source_facetoface_sessions::rb_display_link_f2f_session method is removed
    TL-7458        *  facetoface_sessions.registrationtimestart added a new database field registrationtimestart to facetoface_sessions
                   *  facetoface_sessions.registrationtimefinish added a new database field registrationtimefinish to facetoface_sessions
    TL-7461        *  Capability 'mod/facetoface:editsessions' was renamed to 'mod/facetoface:editevents'
                   *  Capability 'mod/facetoface:overbook' was renamed to 'mod/facetoface:signupwaitlist'
    TL-7463        *  rb_base_source::add_custom_fields_for added seventh argument $useshortname
                   *  $session->datetimeknown property of session objects fetched with facetoface_get_session() is removed.
                      $session->cntdates should be used instead
                   *  mod_facetoface_session_form class is removed
                   *  mod_facetoface_renderer::print_session_list_table added sixth and seventh optional arguments $currenturl and
                      $minimal
                   *  facetoface_save_session_room function is removed
                   *  facetoface_get_session_room function is replaced with facetoface_get_session_rooms
                   *  facetoface_room_html added second argument $backurl
                   *  facetoface_room_info_field added a new database table
                   *  facetoface_room_info_data added a new database table
                   *  facetoface_room_info_data_param added a new database table
                   *  facetoface_room.address removed a database field address from facetoface_room
                   *  facetoface_room.building removed a database field building from facetoface_room
                   *  facetoface_room.usercreated added a new database field usercreated to facetoface_room
                   *  facetoface_room.usermodified added a new database field usermodified to facetoface_room
                   *  facetoface_room.hidden added a new database field hidden to facetoface_room
                   *  facetoface_sessions_dates.roomid added a new database roomid hidden to facetoface_sessions_dates
                   *  facetoface_sessions.roomid removed a database field roomid from facetoface_sessions
                   *  facetoface_sessions.datetimeknown removed a database field datetimeknown from facetoface_sessions
                   *  facetoface_asset added a new database table
                   *  facetoface_asset_info_field added a new database table
                   *  facetoface_asset_info_data added a new database table
                   *  facetoface_asset_info_data_param added a new database table
                   *  facetoface_asset_dates added a new database table
                   *  mod_facetoface\rb\display\link_f2f_session class removed
                   *  mod_facetoface\rb\display\link_f2f class removed
                   *  mod_facetoface\rb\display\f2f_status class removed
    TL-7465        *  facetoface.approvaltype added a new database field approvaltype to facetoface
                   *  facetoface.approvalrole added a new database field approvalrole to facetoface
                   *  facetoface.approvalterms added a new database field approvalterms to facetoface
                   *  facetoface.approvaladmins added a new database field approvaladmins to facetoface
                   *  facetoface_signups.managerid added a new database field managerid to facetoface_signups
                   *  MDL_F2F_CONDITION_BOOKING_REQUEST this global constant has been replaced by
                      MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER
                   *  facetoface_message_substitutions added a 7th argument $approvalrole
                   *  facetoface_approval_required argument two has been removed and is no longer used
                   *  facetoface_user_signup there have been several changes to the function signature. Any uses in custom code should be
                      reviewed to ensure they are still compatible
    TL-7473        *  class custom_menu_item has been deprecated and will be removed in a future version
                   *  class custom_menu has been deprecated and will be removed in a future version
                   *  core_renderer::custom_menu() has been deprecated and will be removed in a future version
                   *  core_renderer::render_custom_menu() has been deprecated and will be removed in a future version
                   *  core_renderer::render_custom_menu_item() has been deprecated and will be removed in a future version
    TL-7944        *  facetoface_sessions.sendcapacityemail added a new database field sendcapacityemail to facetoface_sessions
                   *  facetoface_sessions.mincapacity field must be not null with default value 0
    TL-7963        *  dp_plan_evidence.description removed the database field description in dp_plan_evidence
                   *  dp_plan_evidence.evidencelink removed the database field evidencelink in dp_plan_evidence
                   *  dp_plan_evidence.institution removed the database field institution in dp_plan_evidence
                   *  dp_plan_evidence.datecompleted removed the database field datecompleted in dp_plan_evidence
    TL-7981        *  block_totara_alerts::preferred_width() has been removed.
                   *  block_totara_quicklinks::preferred_width() has been removed
                   *  block_totara_stats::preferred_width() has been removed
                   *  block_totara_tasks::preferred_width() has been removed
    TL-7982        *  local_js() with no arguments now shows a debugging message.
    TL-8009        *  totara_core_renderer::totara_print_active_users has been deprecated and will be removed in a future version
    TL-8012        *  totara_core_renderer::print_totara_search has been deprecated and will be removed in a future version
    TL-8013        *  totara_core_renderer::print_totara_notifications has been deprecated and will be removed in a future version
    TL-8014        *  totara_core_renderer::print_totara_progressbar has been deprecated and will be removed in a future version
    TL-8016        *  totara_core_renderer::print_toolbars has been deprecated and will be removed in a future version
    TL-8017        *  totara_core_renderer::print_totara_menu has been deprecated and will be removed in a future version
    TL-8018        *  totara_core_renderer::totara_print_errorlog_link has been deprecated and will be removed in a future version
    TL-8019        *  totara_core_renderer::display_course_progress_icon has been deprecated and will be removed in a future version
    TL-8023        *  Global constant COMPLETIONTYPE_OPTIONAL added for setting program course sets as optional
    TL-8031        *  totara_core_renderer::print_my_team_nav has been deprecated and will be removed in a future version
    TL-8034        *  totara_core_renderer::print_report_manager has been deprecated and will be removed in a future release
    TL-8035        *  totara_core_renderer::print_scheduled_reports has been deprecated and will be removed in a future release
    TL-8036        *  totara_core_renderer::print_icons_list has been deprecated and will be removed in a future release
    TL-8084        *  html_writer::table has been deprecated and will be removed in a future release
                   *  html_table::$tablealign has been deprecated and will be removed in a future release
                   *  html_table::$captionhide has been deprecated and will be removed in a future release
    TL-8118        *  totara_compl_import_course.customfields added a new database field customfields to totara_compl_import_course
                   *  totara_compl_import_course.evidenceid added a new database field evidenceid to totara_compl_import_course
                   *  totara_compl_import_cert.customfields added a new database field customfields to totara_compl_import_cert
                   *  totara_compl_import_cert.evidenceid added a new database field evidenceid to totara_compl_import_cert
                   *  totara/completionimport/lib.php check_fields_exist added a third argument $customfields
                   *  totara/completionimport/lib.php import_csv added a third argument $customfields
    TL-8187        *  facetoface_sessions.cancelledstatus added a new database field cancelledstatus to facetoface_sessions
                   *  facetoface_sessioncancel_info_data added a new database table
                   *  facetoface_sessioncancel_info_data_param added a new database table
                   *  facetoface_sessioncancel_info_field added a new database table
    TL-8330        *  totara_hierarchy_renderer::print_competency_view_evidence has been deprecated and will be removed in a future
                      release
    TL-8331        *  totara_hierarchy_renderer::print_competency_view_related has been deprecated and will be removed in a future release
    TL-8332        *  totara_hierarchy_renderer::print_goal_view_assignments has been deprecated and will be removed in a future release
    TL-8334        *  totara_hierarchy_renderer::print_assigned_goals has been deprecated and will be removed in a future version
    TL-8383        *  course_modules_completion.reaggregate added a new database field reaggregate to course_modules_completion
    TL-8513        *  a new user_learning API has been introduced, see changelog description for details
                   *  totara/program/lib.php prog_get_all_programs added a ninth argument $onlycertifications
    TL-8537        *  session_options_signup_link has a new fourth argument, true by default, if true the user is redirected to the view
                      all sessions page after completing a signup or cancellation action.
                   *  print_session_list_table has a new eighth argument, true by default, if true the user is redirected to the view all
                      sessions page after completing a signup or cancellation action.
    TL-8636        *  addconfirm_form::get_user_list($userlist, $offset, $limit) added new function
                   *  removeconfirm_form::get_user_list($userlist, $offset, $limit) added new function
    TL-8779        *  dp_base_component::display_back_to_index_link deprecated.
    TL-8786        *  facetoface_asset renamed database field "type" to "allowconflicts" and changed data type from char(10) to int(1)
                   *  facetoface_room renamed database field "type" to "allowconflicts" and changed data type from char(10) to int(1)
    TL-8892        *  rb_source_facetoface_sessions::define_columnoptions time of signup value is changed
    TL-8895        *  rb_facetoface_base_source::add_facetoface_common_to_columns added a second argument $joinsessions
                   *  rb_facetoface_base_source::add_session_status_to_joinlist second argument $sessionidfield was split into two
                      arguments $join and $field
    TL-2082        *  After upgrade, Primary position assignment records will be the users' first job assignment (sortorder = 1) and
                      Secondary records will be the users' second job assignment (sortorder = 2). Aspirational records are moved to the
                      new gap_aspirational table.
                   *  Strings relating to position assignments were deprecated from totara_hierarchy and totara_core and added into the
                      totara_job language file.
                   *  Moved config variables allowsignupposition, allowsignuporganisation and allowsignupmanager from totara_hierarchy to
                      the totara_job plugin.
                   *  The position_updated and position_viewed events have been changed to job_assignment_updated and
                      job_assignment_viewed. The create_from_instance functions now take a job_assignment object, the object id is
                      available to event observers in $data['objectid'], the userid is available in $data['relateduserid'].
                   *  Several behat data generators and steps relating to old position assignments have been removed. "the following job
                      assignments exist" should now be used to define manager, position, organisation, temp manager, etc.
                   *  The create_primary_position phpunit test generator was renamed to create_job_assignments.
                   *  The positionsenabled totara_hierarchy setting is deprecated and no longer used.

                   *  totara_is_manager has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Instead, use job_assignment::is_managing.
                   *  totara_get_staff has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Instead, use job_assignment::get_staff() or job_assignment::get_staff_userids(), depending on the
                      situation.
                   *  totara_get_manager has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Code using this function should be redesigned to either use job_assignment::get_all() with the
                      $managerreqd parameter set to true, or use job_assignment::get_all_manager_userids(), depending on the situation. It
                      is also possible to use job_assignment::get_first(), but this is discouraged, because job assignment order can
                      easily be changed, so this is effectively an arbitrary selection.
                   *  totara_get_most_primary_manager has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. Instead, use job_assignment::get_first(), or, preferably, redesign your code to work
                      with multiple managers and call job_assignment::get_all() with the $managerreqd parameter set to true, or
                      job_assignment::get_all_manager_userids().
                   *  totara_update_temporary_manager has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. Instead, use job_assignment::update_temporary_managers().
                   *  totara_unassign_temporary_manager has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. This functionality is now part of the job_assignment class - load the job_assignment
                      object and call update() with an empty tempmanagerjaid.
                   *  totara_get_teamleader has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Instead, load the job_assignment object and access the teamleaderid property.
                   *  totara_get_appraiser has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. Instead, load the job_assignment object and access the appraiserid property.
                   *  totara_update_temporary_managers has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version
                   *  build_nojs_positionpicker has been deprecated but maintains a level of backwards compatibility and will be removed
                      in a future version
                   *  development_plan::get_manager has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version
                   *  development_plan::send_alert has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version
                   *  rb_source_facetoface_sessions::rb_filter_position_types_list has been deprecated but maintains a level of backwards
                      compatibility and will be removed in a future version
                   *  rb_source_facetoface_sessions::rb_display_position_type has been deprecated but maintains a level of backwards
                      compatibility and will be removed in a future version
                   *  appraisal::get_live_role_assignments has been deprecated but maintains a level of backwards compatibility and will
                      be removed in a future version
                   *  The file totara/hierarchy/prefix/position/assign/manager.php has been deprecated but maintains a level of backwards
                      compatibility and will be removed in a future version. Instead use totara/job/dialog/assign_manager_html.php.
                   *  The file totara/hierarchy/prefix/position/assign/tempmanager.php has been deprecated but maintains a level of
                      backwards compatibility and will be removed in a future version. Instead use
                      totara/job/dialog/assign_tempmanager_html.php.
                   *  pos_can_edit_position_assignment has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. Instead, use totara_job_can_edit_job_assignments.
                   *  pos_get_current_position_data has been deprecated but maintains a level of backwards compatibility and will be
                      removed in a future version. Instead, load the relevant job_assignment object and access the positionid and
                      organisationid properties.
                   *  pos_get_most_primary_position_assignment has been deprecated but maintains a level of backwards compatibility and
                      will be removed in a future version. Instead, it is possible to use job_assignment::get_first(), but this is
                      discouraged, because job assignment order can easily be changed, so this is effectively an arbitrary selection.
                   *  get_position_assignments has been deprecated but maintains a level of backwards compatibility and will be removed in
                      a future version. Instead, use job_assignment::get_all().
                   *  totara_core\task\update_temporary_managers_task has been deprecated but maintains a level of backwards compatibility
                      and will be removed in a future version. Instead, use totara_job\task\update_temporary_managers_task.
                   *  user/positions.php has been deprecated but maintains a level of backwards compatibility and will be removed in a
                      future version. This functionality is now provided in totara/job/jobassignment.php.

                   *  assign_user_position has been deprecated and throws an exception if called. This functionality is now being
                      performed when a job_assignment is created or updated.
                   *  appraisal::get_missingrole_users has been deprecated and throws an exception if called
                   *  appraisal::get_changedrole_users has been deprecated and throws an exception if called
                   *  position::position_label has been deprecated and throws an exception if called. Instead, use
                      position::job_position_label which returns a string formatted " ()".
                   *  The position_assignment class no longer contains any methods and an exception will be thrown from its constructor if
                      instantiated. Instead, job assignments should be managed through the job_assignment class interfaces.
                   *  prog_store_position_assignment has been deprecated and throws an exception if called. Now, when a user's position
                      changes in one of their job assignments, the positionassignmentdate is automatically updated.

                   *  appraisal::validate_roles argument one has been deprecated and is no longer used. Behaviour is now to compare
                      required appraisal roles against corresponding roles in appraisees' job assignments.
                   *  totara_appraisal_renderer::display_appraisal_header argument three has been deprecated and is no longer used. Role
                      assignments are now loaded within this method.
                   *  profile_signup_manager argument three has been changed - this now must be a manager's job assignment id, rather than
                      a manager id.

                   *  pos_add_node_positions_links has been removed. This functionality is now part of totara_job_myprofile_navigation.
                   *  totara_hierarchy_myprofile_navigation has been removed. Instead, use totara_job_myprofile_navigation.
                   *  totara_program_observer::position_updated has been removed. Instead use job_assignment_updated, which is called when
                      the new job_assignment_updated event is triggered.
                   *  mod_facetoface_signup_form::add_position_selection_formelem has been removed
                   *  The JavaScript function rb_load_hierarchy_multi_filters has been removed
                   *  totara/core/js/position.user.js has been removed
                   *  totara/core/classes/event/position_updated.php has been removed, including the the class
                      totara_core\event\position_updated
                   *  totara/core/classes/event/position_viewed.php has been removed, including the the class
                      totara_core\event\position_viewed
                   *  mod/facetoface/classes/event/attendee_position_updated.php has been removed, including the class
                      mod_facetoface\event\attendee_position_updated
                   *  mod/facetoface/attendee_position_form.php has been removed, including the class attendee_position_form
                   *  totara/hierarchy/prefix/position/forms.php has been removed, including the class position_settings_form
                   *  totara/hierarchy/prefix/position/settings.php has been removed, including the function update_pos_settings
                   *  user/positions_form.php has been removed, including the class user_position_assignment_form

                   *  The pos_assignment table was renamed to job_assignment. This maintains record ids, so if you have a table which
                      referenced pos_assignment.id, e.g. "posassignid", then that field does not need to be updated (although you
                      probably want to change the field name and fix the foreign key specification). Several other changes were made to
                      this table, including making idnumber user-unique and cannot be null (and it is editable in the user interface),
                      added fields for temporary manager, added a field for position assignment date (indicates when position was last
                      changed), timevalidfrom and timevalidto renamed to startdate and enddate, managerid and managerpath changed to
                      managerjaid and managerjapath (note that these new fields store job assignment data, not manager ids), and fullname
                      can now be empty (if accessed through the job_assignment class, a default string containing the user-unique idnumber
                      will be returned).
                   *  The pos_assignment_history table was unused so was removed, but only if it contained no data (in case it was in use
                      by some customisation).
                   *  The temporary_manager table has been removed. Temporary manager data was moved into the job_assignment table.
                   *  The prog_pos_assignment table has been removed. The data in this table, which indicates on which date a user was
                      last assigned to their current position, has been moved into the job_assignment table, in the positionassignmentdate
                      field.

                   *  Global constants POSITION_TYPE_PRIMARY, POSITION_TYPE_SECONDARY and POSITION_TYPE_ASPIRATIONAL have all been
                      deprecated, as have the global variables $POSITION_TYPES and $POSITION_CODES.
                   *  The global constant ASSIGNTYPE_MANAGER has been deprecated. This was used for program assignments via manager
                      hierarchy. Custom code using this assignment type will need to be updated to use the managers' job assignments. The
                      new constant used for those assignments is ASSIGNTYPE_MANAGERJA.
    TL-8949        *  Any custom report sources using the old position functions will need to be updated to use the corresponding job
                      assignment function, also any custom columns or filters using the position assignment joins or fields will need to
                      be updated. The add_basic_user_content_options() function is an easy way to add the "show by user", "show by users
                      current position", and "show by users current organisation" content options to a report, just call it inside the
                      sources define_contentoptions() function (see totara/reportbuilder/rb_sources/rb_source_user.php for an example)
                   *  Function add_basic_user_content_options() added to the base source
                   *  Function add_job_assignment_tables_to_joinlist() added to the base source
                   *  Function add_job_assignments_fields_to_columns() added to the base source
                   *  Function add_job_assignments_fields_to_filters() added to the base source
                   *  Function add_position_tables_to_joinlist() has been deprecated, please use  add_job_assignment_tables_to_joinlist()
                      instead
                   *  Function add_position_fields_to_columns() has been deprecated, please use  add_job_assignment_fields_to_columns()
                      instead
                   *  Function add_position_fields_to_filters() has been deprecated, please use  add_job_assignment_fields_to_filters()
                      instead
    TL-9141        *  customfield_datetime::edit_validate_field new method to override parent
                   *  customfield_datetime::edit_save_data new method to override parent
    TL-9156        *  facetoface.allowsignupnotedefault removed unused database field allowsignupnotedefault from facetoface
                   *  facetoface_sessions.availablesignupnote removed unused database field availablesignupnote from facetoface_sessions
    TL-9677        *  totara_appraisal_renderer::display_appraisal_header: argument 3 has been deprecated
                   *  appraisal::validate_roles: argument has been deprecated
                   *  appraisal::get_missingrole_users: has been deprecated and will be removed in a future version
                   *  appraisal::get_changedrole_users: has been deprecated and will be removed in a future version
                   *  appraisal::get_live_role_assignments: has been deprecated and will be removed in a future version
                   *  mdl_appraisal_userassignment.jobassignmentid: new database field to track a linked job assignment to an appraisal
                   *  mdl_appraisal_userassignment.jobassignmentlastmodified: new database field to track if a linked job assignment has
                      be updated
    TL-10597       *  prog_content::get_courseset_groups added a second argument $trimoptional


Database changes:

    TL-6243        Added the ability to automatically create learning plans when new members are added to an audience
    TL-7452        Improved Seminar navigation
    TL-7458        Sign-up periods added to Seminar events
    TL-7459        Added links to further information on each room when signing up
    TL-7463        Added session assets and room improvements
    TL-7465        Added new approval options to Seminars
    TL-7944        Added seminar minimum bookings status and notification
    TL-7963        Added the ability to use custom fields within evidence
    TL-8118        Added the ability to import evidence custom field data in the completion Import tool
    TL-8187        Seminar events can now be cancelled
    TL-8383        Activity completion reaggregated during scheduled task after unlock and delete
    TL-8786        Refactored Seminar asset and room "allow conflicts" data storage
    TL-9156        Removed the setting "Users can enter requests when signing up" from the Seminar module


Deleted files:

    TL-7452        * mod/facetoface/sitenotice.php
                   * mod/facetoface/sitenotice_form.php
                   * mod/facetoface/sitenotices.php
    TL-7453        * mod/facetoface/bulkadd_attendees.php
                   * mod/facetoface/editattendees.html
                   * mod/facetoface/editattendees.php
    TL-7459        * mod/facetoface/amd/build/addpdroom.min.js
                   * mod/facetoface/amd/src/addpdroom.js
                   * mod/facetoface/classes/rb/display/link_f2f_session.php
                   * mod/facetoface/room/ajax/room_save.php
    TL-7465        * mod/facetoface/tests/behat/managerapproval_facetoface.feature
                   * mod/facetoface/tests/behat/selfapproval_facetoface.feature
    TL-7817        * totara/core/js/lib/build/jquery.dataTables.min.js
    TL-7947        * blocks/totara_program_completion/block.js
    TL-7950        * blocks/totara_report_table/module.js
    TL-7951        * elementlibrary/js/competency.item.js
    TL-7959        * totara/reportbuilder/js/instantfilter.js
    TL-7961        * totara/reportbuilder/js/cachenow.js
    TL-7963        * totara/plan/tests/behat/evidence_link.feature
    TL-8188        * totara/core/lib/phpseclib/System/SSH_Agent.php
    TL-8405        * lib/jquery/jquery-1.11.3.min.js
                   * lib/jquery/jquery-2.1.4.min.js
                   * lib/jquery/jquery-migrate-1.2.1.min.js
    TL-8515        * totara/core/classes/totara/menu/mylearning.php
                   * totara/dashboard/tests/behat/dashboard_disable.feature
                   * totara/dashboard/tests/behat/dashboard_home.feature
    TL-8637        * blocks/facetoface/ChangeLog.txt
                   * blocks/facetoface/README.txt
                   * blocks/facetoface/block_facetoface.php
                   * blocks/facetoface/bookinghistory.php
                   * blocks/facetoface/calendar.php
                   * blocks/facetoface/db/access.php
                   * blocks/facetoface/export_form.php
                   * blocks/facetoface/index.php
                   * blocks/facetoface/lang/en/block_facetoface.php
                   * blocks/facetoface/lib.php
                   * blocks/facetoface/mysessions.php
                   * blocks/facetoface/mysignups.php
                   * blocks/facetoface/renderer.php
                   * blocks/facetoface/sessionfilter_form.php
                   * blocks/facetoface/styles.css
                   * blocks/facetoface/tabs.php
                   * blocks/facetoface/tests/behat/mysessions.feature
                   * blocks/facetoface/version.php
    TL-9070        * totara/reportbuilder/filter_dialogs.js
    TL-9192        * totara/core/js/competency.add.js
                   * totara/core/js/competency.item.js
                   * totara/core/js/competency.template.js
    TL-9274        * totara/core/db/pre_any_upgrade.php
    TL-9493        * theme/base/layout/embedded.php
                   * theme/base/layout/frontpage.php
                   * theme/base/layout/report.php
                   * theme/base/pix/fp/add_file.png
                   * theme/base/pix/fp/add_file.svg
                   * theme/base/pix/fp/alias.png
                   * theme/base/pix/fp/alias_sm.png
                   * theme/base/pix/fp/check.png
                   * theme/base/pix/fp/create_folder.png
                   * theme/base/pix/fp/create_folder.svg
                   * theme/base/pix/fp/cross.png
                   * theme/base/pix/fp/dnd_arrow.gif
                   * theme/base/pix/fp/download_all.png
                   * theme/base/pix/fp/download_all.svg
                   * theme/base/pix/fp/help.png
                   * theme/base/pix/fp/help.svg
                   * theme/base/pix/fp/link.png
                   * theme/base/pix/fp/link_sm.png
                   * theme/base/pix/fp/logout.png
                   * theme/base/pix/fp/logout.svg
                   * theme/base/pix/fp/path_folder.png
                   * theme/base/pix/fp/path_folder_rtl.png
                   * theme/base/pix/fp/refresh.png
                   * theme/base/pix/fp/refresh.svg
                   * theme/base/pix/fp/search.png
                   * theme/base/pix/fp/search.svg
                   * theme/base/pix/fp/setting.png
                   * theme/base/pix/fp/setting.svg
                   * theme/base/pix/fp/view_icon_active.png
                   * theme/base/pix/fp/view_icon_active.svg
                   * theme/base/pix/fp/view_list_active.png
                   * theme/base/pix/fp/view_list_active.svg
                   * theme/base/pix/fp/view_tree_active.png
                   * theme/base/pix/fp/view_tree_active.svg
                   * theme/base/pix/horizontal-menu-submenu-indicator.png
                   * theme/base/pix/progress.gif
                   * theme/base/pix/sprite.png
                   * theme/base/pix/vertical-menu-submenu-indicator.png
                   * theme/base/pix/yui2-treeview-sprite-rtl.gif
                   * theme/base/style/admin.css
                   * theme/base/style/autocomplete.css
                   * theme/base/style/blocks.css
                   * theme/base/style/calendar.css
                   * theme/base/style/core.css
                   * theme/base/style/course.css
                   * theme/base/style/dock.css
                   * theme/base/style/editor.css
                   * theme/base/style/filemanager.css
                   * theme/base/style/grade.css
                   * theme/base/style/group.css
                   * theme/base/style/message.css
                   * theme/base/style/pagelayout.css
                   * theme/base/style/question.css
                   * theme/base/style/tabs.css
                   * theme/base/style/templates.css
                   * theme/base/style/totara.css
                   * theme/base/style/totara_jquery_datatables.css
                   * theme/base/style/totara_jquery_treeview.css
                   * theme/base/style/totara_jquery_ui_dialog.css
                   * theme/base/style/user.css
                   * theme/base/templates/core/notification_message.mustache
                   * theme/base/templates/core/notification_problem.mustache
                   * theme/base/templates/core/notification_redirect.mustache
                   * theme/base/templates/core/notification_success.mustache
                   * theme/bootstrapbase/style/font-totara.css


Contributions:

    * Aldo Paradiso at MultaMedio - TL-5143
    * Anthony McLaughlin at Learning Pool - TL-7944
    * Chris Wharton at Catalyst EU - TL-6243
    * Eugene Venter at Catalyst NZ - TL-8023
    * Keelin Devenney at Learning Pool - TL-7458, TL-8187
    * Learning Pool - TL-7466
    * Lee Campbell at Learning Pool - TL-7455
    * Ryan Adams at Learning Pool - TL-7456, TL-7459


*/
