-- Kenya Public Holidays Seed Data
-- Run this after the main database.sql to populate default holidays.
-- Fixed-date holidays are set as recurring (yearly).
-- Variable religious holidays use 2026 tentative dates (not recurring).

INSERT INTO holidays (title, description, date, type, is_recurring) VALUES

-- National public holidays (fixed date, recur yearly)
('New Year''s Day', 'Start of the calendar year', '2026-01-01', 'public', 1),
('Labour Day', 'International Workers'' Day', '2026-05-01', 'public', 1),
('Madaraka Day', 'Commemorates Kenya attaining internal self-rule in 1963', '2026-06-01', 'public', 1),
('Mazingira Day', 'National environment conservation day', '2026-10-10', 'public', 1),
('Mashujaa Day', 'Heroes'' Day – honours all who contributed to Kenya''s independence', '2026-10-20', 'public', 1),
('Jamhuri Day', 'Kenya''s Independence Day / Republic Day', '2026-12-12', 'public', 1),
('Christmas Day', 'Christian celebration of the birth of Jesus Christ', '2026-12-25', 'public', 1),
('Boxing Day', 'Day after Christmas', '2026-12-26', 'public', 1),

-- Religious holidays (variable dates – 2026 tentative; not recurring)
('Idd-ul-Fitr', 'Eid al-Fitr – marks the end of Ramadan (subject to moon sighting)', '2026-03-20', 'public', 0),
('Good Friday', 'Christian observance of the crucifixion of Jesus Christ', '2026-04-03', 'public', 0),
('Easter Monday', 'Day after Easter Sunday', '2026-04-06', 'public', 0),
('Idd-ul-Azha', 'Eid al-Adha – Feast of Sacrifice (subject to moon sighting)', '2026-05-27', 'public', 0),
('Diwali', 'Hindu festival of lights', '2026-11-08', 'public', 0);

-- School holidays (examples – adjust per academic calendar)
INSERT INTO holidays (title, description, date, type, is_recurring) VALUES
('Term 1 Break', 'End of Term 1 school holiday', CONCAT(YEAR(CURDATE()), '-04-10'), 'school', 0),
('Term 2 Break', 'Mid-term school holiday', CONCAT(YEAR(CURDATE()), '-08-14'), 'school', 0),
('Term 3 Break', 'End of year school holiday', CONCAT(YEAR(CURDATE()), '-11-20'), 'school', 0);
