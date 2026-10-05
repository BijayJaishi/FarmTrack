# Testing & Evaluation (SLO-E)
Complete the "Result" column when you run each test on your own machine, then screenshot key ones for the presentation.

## Functional tests
| ID | Test | Expected | Result |
|---|---|---|---|
| F1 | Register farmer with valid data | Saved, redirects to Add Harvest with success message | |
| F2 | Register farmer with duplicate email | Error "already registered" | |
| F3 | Add harvest with quantity 0 or -5 | Rejected by JS and PHP | |
| F4 | Add harvest with future date | Rejected | |
| F5 | View harvests, filter by crop and dates | Only matching rows, total kg updates | |
| F6 | Mark harvest sold | Disappears from market board | |
| F7 | Delete harvest | Confirm prompt, record and its enquiries removed | |
| F8 | Market board search "tom" | Instantly shows Tomatoes only | |
| F9 | Register buyer, then send enquiry | Row added to `enquiries` | |
| F10 | Enquiry message under 10 characters | Rejected | |

## Version 2 feature tests
| ID | Test | Expected | Result |
|---|---|---|---|
| N1 | Send an enquiry from `contact.php` | Redirects to chat with your message shown first | |
| N2 | Open the same chat as farmer in a second browser window and reply | Reply appears in the buyer window within ~3 s, no reload | |
| N3 | Send a message containing `<b>hi</b>` | Shown as plain text | |
| N4 | Disable JavaScript and send a chat message | Still saved (form fallback) | |
| N5 | Farmer changes a price on My Harvests while Market Board is open | Price updates with an arrow within ~8 s; row added to `price_history` | |
| N6 | Farmer marks item sold while board is open | Row disappears from the board | |
| N7 | Enter 10 kg on a $4.50 item | Cost shows $45.00; more than stock shows "Only X kg available" | |
| N8 | Open Dashboard for a farm | Totals, crop bars, monthly bars and price changes are correct | |

## Authentication and session tests
| ID | Test | Expected | Result |
|---|---|---|---|
| A1 | Register with a short password (5 chars) or mismatched confirm | Rejected by JS and PHP | |
| A2 | Log in with correct demo account | Redirected; menu matches role | |
| A3 | Log in with wrong password / unknown email | Same generic error for both | |
| A4 | 5 wrong passwords in a row | "Too many failed attempts" for 1 minute | |
| A5 | Open `add_harvest.php` while logged out | Redirected to login, then returned after login | |
| A6 | Log in as buyer, open `add_harvest.php` | 403 Access denied | |
| A7 | Log in as farmer A, change `harvest_id` in a POST to another farmer's harvest | Nothing changes (query limited to own rows) | |
| A8 | Log in as buyer Tom, open `chat.php?enquiry_id=1` (Anna's chat) | 403 no access | |
| A9 | Log out, press Back, refresh | Page requires login | |
| A10 | In phpMyAdmin check `farmers.password_hash` | Long `$2y$...` hash, no plain text | |
| A11 | Two computers: farmer and buyer chat via server IP | Messages arrive both ways | |

## Profile tests
| ID | Test | Expected | Result |
|---|---|---|---|
| P1 | Open My Profile as farmer and as buyer | Role-specific statistics and details | |
| P2 | Edit name, location, bio and save | Saved; name in menu updates | |
| P3 | Enter an invalid phone or 400-character bio | Error shown, nothing saved | |
| P4 | Upload a valid JPG/PNG | Avatar shows on profile and in menu | |
| P5 | Upload a .php file renamed to .jpg, or a file over 2 MB | Rejected | |
| P6 | Remove photo | Initials avatar returns, file deleted from `uploads/avatars` | |
| P7 | Change password with wrong current password / correct one | Rejected / accepted, can log in with new password | |
| P8 | Click a farm name on the market board | Public farm page shows products, no email or phone | |
| P9 | Profile completeness bar | Updates after adding photo, bio, first harvest | |

## Notification tests
| ID | Test | Expected | Result |
|---|---|---|---|
| M1 | Buyer sends an enquiry while farmer is on another page | Within ~5 s farmer sees a red badge on the chat icon and a pop-up | |
| M2 | Farmer replies while buyer is on the market board | Buyer gets badge + pop-up with "Open chat" | |
| M3 | Click the chat icon | Panel lists recent conversations, unread ones highlighted | |
| M4 | Keep panel open and receive a message | Panel updates within 5 s ("Updated hh:mm:ss") | |
| M5 | Open the chat from the pop-up | Badge count drops; message no longer unread | |
| M6 | Receive a message while reading that same chat | Message appears in chat, no pop-up, no stuck badge | |
| M7 | Press Esc / click outside the panel | Panel closes, focus returns to icon | |
| M8 | Log out (or wait 30 min idle) | Badge stops; no data returned (401) | |
| M9 | Open `php/api_notifications.php` in a logged-out browser | `{"error":"Not logged in"}` | |

## Security tests
| ID | Test | Expected | Result |
|---|---|---|---|
| S1 | Enter `' OR 1=1 --` in crop filter | No SQL error, no extra data (prepared statements) | |
| S2 | Enter `<script>alert(1)</script>` as crop name | Displayed as text, not executed (`e()`) | |
| S3 | Disable JavaScript and submit empty form | PHP still rejects it | |
| S4 | Submit POST without CSRF token | HTTP 403 | |
| S6 | POST to `php/api_chat.php` with a bad role or empty body | HTTP 422 / 403, nothing saved | |
| S5 | `market_board.php?sort=x;DROP TABLE harvests` | Falls back to default sort | |

## Accessibility & usability checklist
| Check | Evidence in code |
|---|---|
| Every input has a `<label for>` | `field()` and `select_field()` in `php/functions.php` |
| Skip-to-content link, `lang="en"`, landmarks | `php/header.php` |
| Visible keyboard focus | `:focus-visible` in `css/style.css` |
| Errors announced (`role="alert"`, `aria-invalid`) | `php/functions.php`, `js/validate.js` |
| Table headers with `scope`, captions | `view_harvests.php`, `market_board.php` |
| Responsive layout (test at 375px, 768px, 1200px) | CSS grid/flexbox, `overflow-x` on tables |
| Colour contrast >= 4.5:1 | Dark green/white palette; check with WebAIM Contrast Checker |
| Feedback after each action | Flash messages |

## Compliance with specification
Seven proposal pages plus login/chat/dashboard, six tables, sessions and role-based access, HTML5/CSS3/JS/PHP/MySQL, PDO prepared statements, README, public GitHub repository: all met.
