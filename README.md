# FarmTrack - Farm Produce Tracking & Market Access System
MIT122 Interactive Web Design and Development - Assessment 2
Team: Rubin Chaulagain (Frontend), Bijay Jaishi (Backend), Sandesh Chiluwal (Full-Stack / QA)

## 1. Requirements
XAMPP (Apache + PHP 8.x + MySQL/MariaDB), a modern browser, VS Code (optional).

## 2. Installation (about 5 minutes)
1. Install XAMPP and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy the `farmtrack` folder into `C:\xampp\htdocs\` (macOS: `/Applications/XAMPP/htdocs/`).
3. Open `http://localhost/phpmyadmin` > **Import** > choose `sql/farmtrack_db.sql` > **Go**. This creates `farmtrack_db`, six tables and demo accounts. (Re-importing resets all data.)
4. Check `php/config.php` (defaults: user `root`, empty password, matching XAMPP).
5. Open `http://localhost/farmtrack/`.

## 3. Accounts and login
Demo accounts (password for all: `Password123!`):
| Role | Email |
|---|---|
| Farmer | `ram@greenvalley.example`, `sita@sunrise.example` |
| Buyer | `anna@freshcorner.example`, `tom@regionalcafe.example` |

Anyone can also create a real account with **Farmer Sign-up** or **Buyer Sign-up**. Farmers and buyers see different menus and pages:
| Role | Can do |
|---|---|
| Guest | View home page and market board, register, log in |
| Farmer | Add harvests, view/filter/update price/mark sold/delete **own** harvests, dashboard, chat about enquiries on their produce |
| Buyer | Send enquiries, chat with the farm, use the cost calculator |

## 4. Running the live demo on two computers (farmer on one PC, buyer on another)
Each PC running its own XAMPP has its **own separate database**, so two local installs can never chat with each other. Use **one computer as the server** and the other as a client:
1. **Server PC** (e.g. Rubin): install as above, start Apache + MySQL, import the SQL.
2. Connect both PCs to the **same Wi-Fi** (a phone hotspot is the safest choice; some university Wi-Fi blocks devices from talking to each other).
3. On the server PC find its IP address: Windows `ipconfig` (IPv4 Address), macOS `ipconfig getifaddr en0` (e.g. `192.168.1.25`).
4. Allow Apache through the firewall if asked (Windows: "Allow access" for `httpd.exe`).
5. On the **second PC** open `http://192.168.1.25/farmtrack/` (use the server's real IP).
6. Server PC logs in as a farmer, second PC logs in as a buyer. The buyer presses Enquire on the market board; the farmer opens **Chats** and replies. Messages appear within about 3 seconds. The farmer changes a price and the buyer's board updates within about 8 seconds.
7. Test this the day before the presentation. If the network blocks it, use a tunnel on the server PC as a backup: install ngrok, run `ngrok http 80`, and share the `https://...ngrok-free.app/farmtrack/` address (public URL, so use demo data only).

Only Apache is shared. MySQL stays private on the server PC (`localhost`), and `php/config.php` needs no change.

## 5. Pages
| Page | Access | Purpose |
|---|---|---|
| `index.php` | everyone | Overview and live statistics |
| `register_farmer.php`, `register_buyer.php` | guests | Create an account (password stored as a bcrypt hash) |
| `login.php`, `logout.php` | everyone | Start / end a session |
| `add_harvest.php` | farmer | Record a harvest |
| `view_harvests.php` | farmer | History with filters; update price, mark sold, delete |
| `dashboard.php` | farmer | Totals, per-crop and monthly analysis, price changes |
| `market_board.php` | everyone | Live prices, search/sort, cost calculator |
| `profile.php` | logged in | Own profile: view, edit details, photo, password |
| `farm.php` | everyone | Public farm profile with live products |
| `contact.php` | buyer | Send an enquiry |
| `enquiries.php`, `chat.php` | logged in | Inbox and live chat (only the two people involved) |

## 5b. User profiles
- **My Profile** (`profile.php`, click your name in the menu): cover banner, avatar, role badge, member-since date, personal statistics, an About card, a profile-completeness bar with tips, and edit forms for details, profile photo and password.
- **Public farm page** (`farm.php?id=...`): click any farm name on the market board to see the farm's story, location, stock summary and live products with Enquire buttons. Email and phone are not shown publicly.
- **Photos:** JPG, PNG or WebP up to 2 MB. The server checks the real file type, renames the file randomly and stores it in `uploads/avatars/`, where scripts cannot run (`uploads/.htaccess`). Without a photo, a coloured circle with the user's initials is shown.
- **Upgrade an existing database (keeps your data):** import `sql/upgrade_profile.sql` once in phpMyAdmin. New installs already include the columns.
- **macOS/Linux upload permission:** if the photo upload says it cannot save the file, run `chmod -R 777 /Applications/XAMPP/htdocs/farmtrack/uploads` in Terminal on the host (demo only).

## 5c. Message notifications
- **Badge:** a red counter appears on the chat icon (💬) and on the Chats link when you have unread messages. The browser tab title also shows `(2) FarmTrack`.
- **Pop-up:** when the other person sends a message, a toast appears at the bottom right on whichever page you are on, with an **Open chat** button. It disappears after 8 seconds. No pop-up is shown for the chat you are already reading.
- **Message panel:** click the 💬 icon to open a short panel with your 6 most recent conversations, previews, times and unread counts. It refreshes every 5 seconds (and stops at once on Esc or an outside click).
- **How it works:** `js/notify.js` calls `php/api_notifications.php` every 5 s (AJAX). Messages have a `read_at` column; opening a chat marks the other person's messages as read, which clears the badge. Background polling does not extend the 30-minute session timeout.
- **Upgrade an existing database (keeps your data):** import `sql/upgrade_notifications.sql` once in phpMyAdmin. New installs already include the columns.

## 6. Folder structure
```
farmtrack/
  *.php               the pages above
  css/style.css       presentation
  js/validate.js      client-side validation
  js/live.js          AJAX: live prices, cost calculator, chat
  js/notify.js        AJAX: unread badge, pop-up notifications, message panel
  php/                config (PDO), functions (security, sessions, helpers), header, footer, api_prices, api_chat
  sql/farmtrack_db.sql  schema + demo data
  docs/               Part A summary, testing/evaluation, demo script, rubric checklist
```

## 7. Database (6 tables)
`farmers`, `buyers` (both with `password_hash`, `bio`, `avatar`), `harvests`, `enquiries`, `messages`, `price_history`. Foreign keys use `ON DELETE CASCADE`.
Changes from the proposal: `harvests.status`, `password_hash` on users, `messages`, `price_history`; `condition` is written with backticks because it is a reserved word.

## 8. Client-side vs server-side (SLO-B)
| Client-side (JavaScript) | Server-side (PHP) |
|---|---|
| Instant validation (required, email, phone, numbers, password match) | Re-validates every rule; browser checks can be bypassed |
| Live prices, cost calculator, chat via `fetch()` (AJAX) | Sessions, login, roles, prepared-statement SQL, JSON APIs |
| Delete confirmation | CSRF tokens, output escaping, ownership checks |

## 9. Security features (SLO-E)
- **Passwords:** `password_hash()` (bcrypt) and `password_verify()`; never stored or shown in plain text
- **Sessions:** `session_regenerate_id()` on login (stops session fixation), HttpOnly + SameSite cookies, strict session mode, 30-minute idle timeout, full destroy on logout
- **Login protection:** same error for wrong email or wrong password; 5 failed attempts lock the login for 1 minute
- **Access control:** page guards by role; farmers can only change their own harvests; only the two people in an enquiry can read or send chat messages; sender role comes from the session, not the browser
- **Uploads:** size limit, real MIME check with `finfo`, `getimagesize()`, random file names, script execution blocked in `uploads/`, only whitelisted file names are ever output
- PDO prepared statements everywhere (SQL injection), `htmlspecialchars` on output (XSS), CSRF token on every POST including logout, whitelisted `ORDER BY`, safe `next=` redirect (local pages only), DB errors logged not shown

## 10. Limitations / future work
Chat uses polling (3 s) instead of WebSockets; no email verification or password reset; the login lockout is per browser session (a production system would track attempts in the database); HTTP only (production would use HTTPS and the `secure` cookie flag); email/SMS notifications, payments and delivery tracking.
