# Careers Portal

The Careers Portal runs separately from eAdmissions at
`http://localhost/careers_portal/`. Keep this folder directly under
`C:\xampp\htdocs\`; it should not be placed inside `eAdmissions_cbetplus`.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Ensure the configured school database and active Headteacher account are
   available for the existing school profile and Headteacher sign-in.
3. For a fresh install, import this portal's `database\careers.sql`, which
   creates and uses the separate `e_careers` database. For an existing Careers
   Portal installation, run the SQL files in `database\migrations\` in filename
   order against `e_careers`. Careers tables are kept separate from admissions
   tables.
4. The portal uses the database credentials in
   `C:\xampp\htdocs\eAdmissions_cbetplus\includes\config.php` and reads the
   school profile and Headteacher identity from the existing school database.
5. Visit `http://localhost/careers_portal/`.

Use the **Login** button to open the shared sign-in form:

- Applicants create an account, submit a CV and cover letter, and check their
  application status and feedback. Applications include one PDF containing all
  academic qualifications.
- The Headteacher signs in with the existing active school account to manage
  vacancies, review applications (including viewing uploaded PDFs in-browser),
  and share hiring decisions.

Applications move through four statuses: Under review, Shortlisted,
Interviewed, and Hired.

Sign-in is a single form: the portal checks the credentials and assigns the
applicant or Headteacher role automatically. Applicant accounts can be created
and recovered from the applicant links. Headteacher accounts cannot be created
or reset here; their credentials continue to be managed by the school.

Headteachers must enter a valid, monitored recruitment contact email for each
job listing, along with the number of people needed for the role. The contact
email and vacancy count are shown with the public job details. Applicants can
browse all public jobs, but must create an account or sign in before submitting
an application from the applicant portal. The applicant dashboard's **Browse
available jobs** button opens a dedicated opportunities page, from which
applicants can open role details and start an application.

The school crest and campus images in `assets\` are used for portal branding.
Candidate CVs are stored in the protected `storage\` folder, delivered only
through the authenticated Headteacher route, and limited to PDF/DOC/DOCX up to
5 MB. Academic qualifications must be combined into one PDF, also up to 5 MB;
both documents are available only to the authenticated Headteacher.

Applicant password-reset messages use the shared SMTP configuration or
`AD_MAIL_*` environment variables. Set `CAREER_PORTAL_BASE_URL` to the full
public URL (for example `https://example.org/careers_portal`) when links should
not use the current request host.
