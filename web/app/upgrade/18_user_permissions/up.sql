ALTER TABLE `problems_solutions` ADD UNIQUE KEY `unique__blog_id` (`blog_id`);
ALTER TABLE `user_info` MODIFY `usertype` enum('student','teacher','system','banned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student';
