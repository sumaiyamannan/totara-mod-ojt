# Totara OJT activity
This is a module plug-in for the Totara Learning Management System (https://www.totaralms.com/solutions/totara-lms). It adds functionality for defining, tracking and signing-off on on-job-training (OJT).  As well
as user and manager interfaces, reports are also available to provide an organisational view of overall progress.

The OJT module allows a course administrator to create a checklist of topics and topic items. As soon as all required topic items are 'checked' the topic becomes complete.

Each topic can be configured to:
* be optional
* have current course competencies linked to it
* have overall comments

Each topic item can be configured to:
* be optional
* allow file uploads by evaluator
* allow file uploads by OJT owner

An OJT activity can then be further configured to have:
* manager signoff (a manager will get a task notification - upon topic completion - to go and sign off on a topic)
* and/or topic item completion witnessing

Activity completion can also be enabled, based on the completion of all topics (and, if enabled, item witnessing).

The following capabilities currently exist for the module:
* View
* Evaluate
* Evaluate self
* Manage
* Sign-off
* Witness topic completion

From the above, it becomes clear that quite a few work flows can be configured for this activity.

The actual evaluation page then allows the relevant user to either view completion, or evaluate/witness by checking the topic item checkboxes. Each topic item also has a 'notes' box, that can be used by the person performing the evaluation.

Lastly, an OJT Completion Totara report source exists, which is also used in an embedded form by the module.

#### Supported Versions
* 2.7 (OJT_27 branch)
* 2.9 (OJT_29 branch)

#### Installation
1. Download a .zip of the version branch you require and extract locally
2. Copy the contents of mod/ into your Totara's mod/ folder
3. Copy the contents of totara/reportbuilder/embedded/ into your Totara's totara/reportbuilder/embedded/ folder
4. Visit /admin/index.php on your Totara system to install

#### Credits
* Contributed to the open source community through development commissioned by Customs New Zealand :)
* Developed and maintained by the Catalyst IT Elearning team (https://catalyst.net.nz)

### Help
See http://catalyst.github.io/totara-mod-ojt
