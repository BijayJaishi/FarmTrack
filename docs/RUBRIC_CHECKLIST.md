# Rubric Checklist (highest priority)
The Canvas rubric grades three criteria on Exceeds / Mastery / Near / Below / No Evidence:
**Part A report**, **Working system or source code**, **Presentation** (plus your individual 600-word reflection).
The exact descriptors are on Canvas - compare this table with them before submitting.

| SLO | Where it is evidenced | Aim for |
|---|---|---|
| A - Tools and technologies | README section 1, Part A "Tools", proposal table | Justify each tool (why PHP/PDO, why XAMPP) |
| B - Client vs server scripting | `js/validate.js` vs PHP validation, README section 4 | Show one example live: disable JS, PHP still blocks bad data |
| C - Documentation | README (install + operation), code comments, docs/ | Someone else can install it in 5 minutes |
| D - Working client/server system | 7 pages, 4 tables, full CRUD | Every proposal feature works with no errors |
| E - Evaluate usability, accessibility, security | docs/TESTING_EVALUATION.md | Fill in results with screenshots |

## Version 2 features and the rubric
- **Live chat and live prices** demonstrate client/server interaction (SLO-B) with AJAX: JavaScript requests JSON, PHP queries MySQL, the page updates without reload. This is the best live-demo moment: open two browser windows.
- **Dashboard** demonstrates database design and SQL aggregation (SLO-D) and delivers the proposal's seasonal-analysis goal.
- Tell the examiner these go beyond the original seven pages and how the core scope is still fully met.

## Version 3: login, sessions, real multi-user demo
- Authentication (bcrypt, sessions, roles) and authorisation (ownership checks) are strong SLO-E evidence and make it a genuine multi-user system (SLO-D).
- Use `docs/DEMO_SCRIPT.md` for the two-computer demo and README section 4 for setup. Remember two separate XAMPP installs cannot share data: one PC must be the server.

## Before submitting (Canvas, Week 12 Friday)
- [ ] Import `sql/farmtrack_db.sql` on a fresh machine and click through every page
- [ ] Push code to public GitHub; each member commits (contribution history counts)
- [ ] Part A is ~300 words, Word format (`docs/PART_A_Project_Summary.md` -> paste into Word)
- [ ] Testing table results filled in
- [ ] Report submitted BEFORE the presentation
- [ ] Each member writes their own 600-word reflection

## Presentation (max 12 min incl. Q&A): suggested 10 slides
1 Title/team - 2 Problem - 3 Solution and scope - 4 Database ER diagram - 5 Tech stack - 6 Client vs server - 7 **Live demo** (register > harvest > market board with live price change > enquiry > two-window chat > dashboard) - 8 Security and accessibility - 9 Testing and teamwork - 10 Future work.
Each member presents their own part (Rubin: UI/CSS and market board; Bijay: PHP/database; Sandesh: validation, testing, GitHub). Likely questions: why PDO? what stops SQL injection? what if JavaScript is off? why a `status` column?

## Part C reflection (individual, 600 words - write in your own words)
Suggested structure: your role and contribution (~150) - a technical challenge you solved, e.g. reserved word `condition`, responsive tables (~150) - teamwork: communication, Git, sprints, conflicts (~150) - what you learned about client vs server scripting (~100) - what you would improve (~50).
