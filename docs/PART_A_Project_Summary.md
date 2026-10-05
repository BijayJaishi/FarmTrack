# Part A - Website Development Project Summary (Group, ~300 words)

**FarmTrack** is a web-based information system that replaces paper record-keeping for small and medium-scale farmers and connects them with buyers. It addresses lost handwritten records, informal market access and no historical view of harvests.

**System.** Users register as farmers or buyers and log in; sessions and role-based access control decide what each can do. Farmers record harvests, update prices, mark produce sold and view a dashboard of seasonal trends. A public market board shows produce with **live prices** and a **cost calculator**. Logged-in buyers send enquiries and continue in a **live chat** with unread badges and pop-up notifications. Data is stored in MySQL in six related tables: farmers, buyers, harvests, enquiries, messages and price_history. Users have editable profiles, and farms have public pages. All users share one server database, so people on different computers interact in real time.

**Tools (SLO-A).** HTML5 and CSS3 for semantic, responsive pages; JavaScript (ES6) for validation and AJAX; PHP 8 with PDO for server logic and sessions; MySQL for storage; XAMPP, VS Code and GitHub for development and version control.

**Client vs server (SLO-B).** JavaScript gives instant feedback and, through `fetch()`, updates prices and chat without reloading. Because browser code can be bypassed, PHP re-validates input and enforces authentication, authorisation and data integrity.

**Method.** Agile, feature-based sprints: foundation, core CRUD features, then authentication, live features, integration and testing. Rubin built the interface, Bijay the PHP and database logic, and Sandesh validation, testing and repository management.

**Evaluation (SLO-E).** Security: bcrypt password hashing, session regeneration, idle timeout, login lockout, ownership checks, prepared statements, output escaping, CSRF tokens. Accessibility: labelled fields, skip link, focus styles, contrast, ARIA-announced errors. Results are in `docs/TESTING_EVALUATION.md`.

**Documentation (SLO-C).** The README covers installation, accounts and two-computer setup. Future work: HTTPS, password reset, WebSockets and notifications.
