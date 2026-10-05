# Live Demo Script (two computers, about 5 minutes)
Setup before class: server PC running XAMPP (Apache + MySQL, SQL imported), second PC opens `http://SERVER_IP/farmtrack/`. Test the day before.

| Step | Who | Action | What to say (rubric link) |
|---|---|---|---|
| 1 | Buyer PC | Open market board as a guest | Public page; guests cannot enquire (access control) |
| 2 | Farmer PC | Sign up as a new farmer (or log in as Ram), add a harvest | Client-side validation + PHP validation + PDO INSERT (SLO-B, D) |
| 3 | Buyer PC | Sign up as buyer, refresh board: new produce is listed; type kg in the calculator | Data shared through MySQL; live cost calculator (JavaScript) |
| 4 | Farmer PC | My Harvests: change the price | Buyer's board updates within ~8 s without reload (AJAX) |
| 5 | Buyer PC | Press Enquire, send message | Redirected to chat |
| 6 | Farmer PC | Chats > open chat > reply | Message appears on buyer PC in ~3 s (client/server interaction) |
| 6b | Buyer PC | Stay on any page while the farmer replies | Badge on the chat icon and a pop-up appear within ~5 s; open the message panel (AJAX polling) |
| 7 | Farmer PC | Dashboard | SQL aggregation, seasonal analysis |
| 8 | Either | Try opening `chat.php?enquiry_id=1` as another account | "Access denied": authorisation (SLO-E) |
| 9 | Either | Log out; press Back; page requires login again | Sessions destroyed |

Backup plan: keep screenshots of each step; if the network fails, show both roles in two browsers (normal + private window) on the server PC.
