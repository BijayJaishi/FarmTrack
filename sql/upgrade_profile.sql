-- Run ONCE on an existing farmtrack_db (keeps all your data). New installs do not need this file.
USE farmtrack_db;
ALTER TABLE farmers ADD COLUMN bio VARCHAR(300) NOT NULL DEFAULT '' AFTER password_hash;
ALTER TABLE farmers ADD COLUMN avatar VARCHAR(60) NULL AFTER bio;
ALTER TABLE buyers  ADD COLUMN bio VARCHAR(300) NOT NULL DEFAULT '' AFTER password_hash;
ALTER TABLE buyers  ADD COLUMN avatar VARCHAR(60) NULL AFTER bio;
UPDATE farmers SET bio='Third-generation vegetable growers in the Riverina. We pick to order and sell direct to local grocers.' WHERE email='ram@greenvalley.example';
UPDATE farmers SET bio='Family-run orchard growing apples and stone fruit. Chemical-free and picked fresh each week.' WHERE email='sita@sunrise.example';
UPDATE buyers SET bio='Neighbourhood grocer looking for fresh, local produce delivered weekly.' WHERE email='anna@freshcorner.example';
